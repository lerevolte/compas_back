<?php

namespace App\Services;

use App\Models\ObjectRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransferService
{
    public const ORDER = 'transfer_orders';
    public const DOC = 'transfers';
    public const DATE_FIELD = 'transfer_date';
    public const DATE_TITLE = 'Дата перемещения';

    public static function isEntity(string $slug): bool
    {
        return $slug === self::ORDER || $slug === self::DOC;
    }

    public static function dealIds(string $slug, int $id): array
    {
        if (!self::isEntity($slug) || !$id || !ObjectRelation::ready()) {
            return [];
        }
        $nodes = [[$slug, $id]];
        if ($slug === self::DOC) {
            foreach (ObjectRelation::where('target_slug', self::DOC)->where('target_id', $id)->where('source_slug', self::ORDER)->get(['source_id']) as $relation) {
                $nodes[] = [self::ORDER, (int) $relation->source_id];
            }
        }
        $ids = [];
        foreach ($nodes as [$nodeSlug, $nodeId]) {
            foreach (ObjectRelation::where('source_slug', 'deals')->where('target_slug', $nodeSlug)->where('target_id', $nodeId)->pluck('source_id') as $dealId) {
                $ids[(int) $dealId] = true;
            }
            foreach (ObjectRelation::where('target_slug', 'deals')->where('source_slug', $nodeSlug)->where('source_id', $nodeId)->pluck('target_id') as $dealId) {
                $ids[(int) $dealId] = true;
            }
        }

        return array_keys($ids);
    }

    public static function compose(string $slug, int $id, array $current, array $additional = []): array
    {
        $current = ShipmentService::withoutServices($current);
        $ids = self::dealIds($slug, $id);
        $lines = [];
        if ($ids && Schema::hasTable('deals')) {
            foreach (DB::table('deals')->whereIn('id', $ids)->whereNull('deleted_at')->pluck('products') as $json) {
                foreach (ShipmentService::withoutServices(ShipmentService::decode($json)) as $line) {
                    if (!is_array($line)) {
                        continue;
                    }
                    $key = self::lineKey($line);
                    if ($key === '') {
                        continue;
                    }
                    if (!isset($lines[$key])) {
                        $lines[$key] = $line;
                        $lines[$key]['count'] = 0;
                    }
                    $lines[$key]['count'] += (float) ($line['count'] ?? 0);
                }
            }
        }
        foreach ($current as $line) {
            if (!is_array($line)) {
                continue;
            }
            $key = self::lineKey($line);
            if ($key === '') {
                continue;
            }
            $extra = $additional[$key] ?? ($line['additional_count'] ?? 0);
            if (!is_numeric($extra) || (float) $extra < 0) {
                continue;
            }
            if (!isset($lines[$key]) && (float) $extra > 0) {
                $lines[$key] = $line;
                $lines[$key]['count'] = 0;
            }
            if (isset($lines[$key])) {
                $lines[$key]['additional_count'] = (float) $extra;
            }
        }
        foreach ($lines as &$line) {
            $line['count'] += (float) ($line['additional_count'] ?? 0);
            $parts = ShipmentService::lineParts($line);
            $line['sum'] = round($parts['sum'], 2);
            $line['nds_sum'] = round($parts['vat'], 2);
            $line['total'] = round($parts['gross'], 2);
        }
        unset($line);

        return array_values($lines);
    }

    public static function lineKey(array $line): string
    {
        $id = (int) ($line['id'] ?? 0);

        return $id ? 'id:' . $id : (($name = ShipmentService::nameKey($line['name'] ?? '')) !== '' ? 'name:' . $name : '');
    }

    public static function refresh(string $slug, int $id): void
    {
        if (!self::isEntity($slug) || !Schema::hasTable($slug)) {
            return;
        }
        $class = $slug === self::ORDER ? \App\Models\TransferOrder::class : \App\Models\Transfer::class;
        $model = $class::find($id);
        if (!$model) {
            return;
        }
        $current = ShipmentService::decode($model->products);
        $next = self::compose($slug, $id, $current);
        if (json_encode($current) !== json_encode($next)) {
            $model->setProducts($next);
        }
        if ($slug === self::ORDER && ObjectRelation::ready()) {
            foreach (ObjectRelation::where('source_slug', self::ORDER)->where('source_id', $id)->where('target_slug', self::DOC)->pluck('target_id') as $docId) {
                self::refresh(self::DOC, (int) $docId);
            }
        }
    }

    public static function refreshForDeal(int $dealId): void
    {
        if (!ObjectRelation::ready()) {
            return;
        }
        foreach ([self::ORDER, self::DOC] as $slug) {
            $ids = ObjectRelation::where('source_slug', 'deals')->where('source_id', $dealId)->where('target_slug', $slug)->pluck('target_id')->all();
            $ids = array_merge($ids, ObjectRelation::where('target_slug', 'deals')->where('target_id', $dealId)->where('source_slug', $slug)->pluck('source_id')->all());
            foreach (array_unique(array_map('intval', $ids)) as $id) {
                self::refresh($slug, $id);
                if ($slug === self::ORDER) {
                    foreach (ObjectRelation::where('source_slug', self::ORDER)->where('source_id', $id)->where('target_slug', self::DOC)->pluck('target_id') as $docId) {
                        self::refresh(self::DOC, (int) $docId);
                    }
                }
            }
        }
    }

    public static function orderUsage(int $orderId): array
    {
        $result = ['id' => [], 'name' => [], 'price_id' => [], 'price_name' => []];
        if (!ObjectRelation::ready() || !Schema::hasTable(self::DOC)) {
            return $result;
        }
        $docIds = ObjectRelation::where('source_slug', self::ORDER)
            ->where('source_id', $orderId)
            ->where('target_slug', self::DOC)
            ->pluck('target_id')
            ->map(fn ($v) => (int) $v)
            ->all();
        $taskIds = ObjectRelation::where('source_slug', self::ORDER)
            ->where('source_id', $orderId)
            ->where('target_slug', 'logistic_tasks')
            ->pluck('target_id')
            ->all();
        if (count($taskIds)) {
            $docIds = array_merge($docIds, ObjectRelation::where('source_slug', 'logistic_tasks')
                ->whereIn('source_id', $taskIds)
                ->where('target_slug', self::DOC)
                ->pluck('target_id')
                ->map(fn ($v) => (int) $v)
                ->all());
        }
        $docIds = array_values(array_unique($docIds));
        if (!count($docIds)) {
            return $result;
        }
        $query = DB::table(self::DOC)->whereIn('id', $docIds);
        if (Schema::hasColumn(self::DOC, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        foreach ($query->pluck('products') as $products) {
            foreach (ShipmentService::decode($products) as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $count = (float) ($product['count'] ?? 0);
                $id = (int) ($product['id'] ?? 0);
                if ($id) {
                    $result['id'][$id] = ($result['id'][$id] ?? 0) + $count;
                }
                $name = ShipmentService::nameKey($product['name'] ?? '');
                if ($name !== '') {
                    $result['name'][$name] = ($result['name'][$name] ?? 0) + $count;
                }
            }
        }

        return $result;
    }

    public static function recalcOrder(int $orderId): void
    {
        if (!$orderId || !Schema::hasTable(self::ORDER) || !Schema::hasColumn(self::ORDER, 'shipment_status')) {
            return;
        }
        try {
            $order = \App\Models\TransferOrder::withTrashed()->find($orderId);
            if (!$order) {
                return;
            }
            ShipmentService::updateShipmentStatus(self::ORDER, $order, ShipmentService::decode($order->products), self::orderUsage($orderId));
        } catch (\Throwable $e) {
            \Log::warning('transfers: статус заказа на перемещение не обновлён', ['order_id' => $orderId, 'error' => $e->getMessage()]);
        }
    }

    public static function recalcAround(string $slug, int $id): void
    {
        if ($slug === self::ORDER) {
            self::recalcOrder($id);
            return;
        }
        if ($slug !== self::DOC || !ObjectRelation::ready()) {
            return;
        }
        $parents = ObjectRelation::whereIn('source_slug', [self::ORDER, 'logistic_tasks'])
            ->where('target_slug', self::DOC)
            ->where('target_id', $id)
            ->get(['source_slug', 'source_id']);
        foreach ($parents as $parent) {
            if ($parent->source_slug === self::ORDER) {
                self::recalcOrder((int) $parent->source_id);
            } else {
                ShipmentService::recalcForSource('logistic_tasks', (int) $parent->source_id);
            }
        }
    }
}
