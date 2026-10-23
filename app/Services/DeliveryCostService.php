<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DeliveryCostService
{
    public const FIELD = 'delivery_cost_price';
    public const TITLE = 'Себестоимость доставки';
    public const TASKS = 'logistic_tasks';
    public const ORDERS = ['deals', 'supplier_orders'];
    public const ROUTE_FIELDS = ['delivery_price', 'mileage_cost'];

    private static array $ready = [];

    public static function ready(string $slug = self::TASKS, $db = null): bool
    {
        $db = $db ?: DB::connection();
        $key = $db->getName() . ':' . (function_exists('tenant') ? (string) tenant('id') : '') . ':' . $slug;
        if (!array_key_exists($key, self::$ready)) {
            try {
                self::$ready[$key] = $db->getSchemaBuilder()->hasColumn($slug, self::FIELD);
            } catch (\Throwable $e) {
                self::$ready[$key] = false;
            }
        }

        return self::$ready[$key];
    }

    public static function forget(): void
    {
        self::$ready = [];
    }

    public static function recalcRoute(?int $routeId): void
    {
        if (!$routeId || !self::ready()) {
            return;
        }
        try {
            $route = DB::table('routes')->where('id', $routeId)->first(self::ROUTE_FIELDS);
            $tasks = DB::table(self::TASKS)->where('route_id', $routeId)->whereNull('deleted_at')->get(['id', 'weight', self::FIELD]);
            if (!$tasks->count()) {
                return;
            }
            $values = self::distribute($route, $tasks);
            $changed = [];
            foreach ($tasks as $task) {
                $value = $values[(int) $task->id] ?? null;
                if (self::differs($task->{self::FIELD}, $value)) {
                    DB::table(self::TASKS)->where('id', $task->id)->update([self::FIELD => $value]);
                    $changed[] = (int) $task->id;
                }
            }
            self::recalcOrdersForTasks($changed);
        } catch (\Throwable $e) {
            \Log::warning('delivery cost: не удалось пересчитать маршрут', ['route' => $routeId, 'error' => $e->getMessage()]);
        }
    }

    public static function clearTask(int $taskId): void
    {
        if (!$taskId || !self::ready()) {
            return;
        }
        try {
            $current = DB::table(self::TASKS)->where('id', $taskId)->value(self::FIELD);
            if ($current !== null) {
                DB::table(self::TASKS)->where('id', $taskId)->update([self::FIELD => null]);
            }
            self::recalcOrdersForTasks([$taskId]);
        } catch (\Throwable $e) {
        }
    }

    public static function recalcAround(string $slug, int $id): void
    {
        if (!$id) {
            return;
        }
        if ($slug === self::TASKS) {
            self::recalcOrdersForTasks([$id]);
        } elseif (in_array($slug, self::ORDERS, true)) {
            self::recalcOrder($slug, $id);
        }
    }

    public static function recalcOrdersForTasks(array $taskIds): void
    {
        $taskIds = array_values(array_unique(array_filter(array_map('intval', $taskIds))));
        if (!count($taskIds) || !DB::getSchemaBuilder()->hasTable('object_relations')) {
            return;
        }
        $orders = DB::table('object_relations')
            ->where('target_slug', self::TASKS)
            ->whereIn('target_id', $taskIds)
            ->whereIn('source_slug', self::ORDERS)
            ->get(['source_slug', 'source_id']);
        $seen = [];
        foreach ($orders as $order) {
            $key = $order->source_slug . ':' . $order->source_id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            self::recalcOrder((string) $order->source_slug, (int) $order->source_id);
        }
    }

    public static function recalcOrder(string $slug, int $id): void
    {
        if (!$id || !self::ready($slug) || !self::ready()) {
            return;
        }
        try {
            $taskIds = DB::table('object_relations')
                ->where('source_slug', $slug)
                ->where('source_id', $id)
                ->where('target_slug', self::TASKS)
                ->pluck('target_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();
            $total = null;
            if (count($taskIds)) {
                foreach (DB::table(self::TASKS)->whereIn('id', $taskIds)->whereNull('deleted_at')->pluck(self::FIELD) as $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $total = ($total ?? 0.0) + self::number($value);
                }
            }
            $total = $total === null ? null : round($total, 2);
            $current = DB::table($slug)->where('id', $id)->value(self::FIELD);
            if (self::differs($current, $total)) {
                DB::table($slug)->where('id', $id)->update([self::FIELD => $total]);
            }
        } catch (\Throwable $e) {
            \Log::warning('delivery cost: не удалось пересчитать заказ', ['object' => $slug . '#' . $id, 'error' => $e->getMessage()]);
        }
    }

    public static function backfill(): array
    {
        $result = ['routes' => 0, 'orders' => []];
        if (!self::ready()) {
            return $result;
        }
        $routeIds = DB::table(self::TASKS)->whereNotNull('route_id')->whereNull('deleted_at')->distinct()->pluck('route_id');
        foreach ($routeIds as $routeId) {
            if (is_numeric($routeId) && (int) $routeId > 0) {
                self::recalcRoute((int) $routeId);
                $result['routes']++;
            }
        }
        DB::table(self::TASKS)
            ->where(fn ($q) => $q->whereNull('route_id')->orWhere('route_id', 0))
            ->whereNotNull(self::FIELD)
            ->update([self::FIELD => null]);
        foreach (self::ORDERS as $slug) {
            if (!self::ready($slug)) {
                continue;
            }
            $count = 0;
            $ids = DB::table('object_relations')->where('source_slug', $slug)->where('target_slug', self::TASKS)->distinct()->pluck('source_id');
            foreach ($ids as $id) {
                self::recalcOrder($slug, (int) $id);
                $count++;
            }
            $result['orders'][$slug] = $count;
        }

        return $result;
    }

    private static function distribute($route, $tasks): array
    {
        $result = [];
        if (!$route) {
            return $result;
        }
        $hasCost = false;
        $total = 0.0;
        foreach (self::ROUTE_FIELDS as $field) {
            $raw = $route->{$field} ?? null;
            if ($raw !== null && trim((string) $raw) !== '') {
                $hasCost = true;
                $total += self::number($raw);
            }
        }
        if (!$hasCost) {
            return $result;
        }
        $warehouses = array_flip(ShipmentService::loadingWarehouseTaskIds($tasks->pluck('id')->all()));
        $weights = [];
        $totalWeight = 0.0;
        foreach ($tasks as $task) {
            if (isset($warehouses[(int) $task->id])) {
                continue;
            }
            $weight = max(0.0, self::number($task->weight));
            $weights[(int) $task->id] = $weight;
            $totalWeight += $weight;
        }
        if ($totalWeight <= 0) {
            return $result;
        }
        foreach ($weights as $id => $weight) {
            $result[$id] = round($total / $totalWeight * $weight, 2);
        }

        return $result;
    }

    private static function differs($current, $value): bool
    {
        if ($value === null) {
            return $current !== null && $current !== '';
        }
        if ($current === null || $current === '') {
            return true;
        }

        return abs(self::number($current) - (float) $value) > 0.001;
    }

    private static function number($value): float
    {
        $value = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim((string) $value));

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
