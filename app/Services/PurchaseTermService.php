<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PurchaseTermService
{
    public const FIELD = 'avg_purchase_days';
    public const TITLE = 'Средний срок закупки';
    public const ORDERS = 'supplier_orders';
    public const RECEIPTS = 'receipt_invoices';
    public const SPECIFICATIONS = 'specifications';
    public const FACT_FIELD = 'fact_product_id';
    public const LIMIT = 10;
    public const RELATED = ['supplier_orders', 'receipt_invoices', 'logistic_tasks'];

    private static array $ready = [];

    public static function ready($db = null): bool
    {
        $db = $db ?: DB::connection();
        $key = $db->getName() . ':' . (function_exists('tenant') ? (string) tenant('id') : '');
        if (!array_key_exists($key, self::$ready)) {
            try {
                $sb = $db->getSchemaBuilder();
                self::$ready[$key] = $sb->hasColumn('products', self::FIELD)
                    && $sb->hasTable(self::ORDERS)
                    && $sb->hasTable(self::RECEIPTS)
                    && $sb->hasTable('object_relations');
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

    public static function touchedRelation(string $slugA, string $slugB): void
    {
        if (in_array(self::RECEIPTS, [$slugA, $slugB], true) && in_array($slugA, self::RELATED, true) && in_array($slugB, self::RELATED, true)) {
            self::recalcAll();
        }
    }

    public static function recalcAll(): int
    {
        if (!self::ready()) {
            return 0;
        }
        try {
            $terms = self::resolve(self::ownTerms());
            $changed = 0;
            $current = DB::table('products')->whereNotNull(self::FIELD)->pluck(self::FIELD, 'id')->all();
            foreach ($terms as $id => $days) {
                if (!array_key_exists($id, $current) || (int) $current[$id] !== $days) {
                    DB::table('products')->where('id', $id)->update([self::FIELD => $days]);
                    $changed++;
                }
            }
            $stale = array_diff(array_keys($current), array_keys($terms));
            foreach (array_chunk($stale, 500) as $chunk) {
                DB::table('products')->whereIn('id', $chunk)->update([self::FIELD => null]);
                $changed += count($chunk);
            }

            return $changed;
        } catch (\Throwable $e) {
            \Log::warning('purchase term: не удалось пересчитать средний срок закупки', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    public static function ownTerms(): array
    {
        $receiptIds = DB::table('object_relations')
            ->where('target_slug', self::RECEIPTS)
            ->distinct()
            ->pluck('target_id')
            ->map(fn ($v) => (int) $v)
            ->all();
        if (!count($receiptIds)) {
            return [];
        }
        $orderDates = [];
        $receipts = DB::table(self::RECEIPTS)
            ->whereIn('id', $receiptIds)
            ->whereNull('deleted_at')
            ->whereNotNull('created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'created_at', 'products']);
        $samples = [];
        foreach ($receipts as $receipt) {
            $orderId = self::orderFor((int) $receipt->id);
            if (!$orderId) {
                continue;
            }
            if (!array_key_exists($orderId, $orderDates)) {
                $orderDates[$orderId] = DB::table(self::ORDERS)->where('id', $orderId)->whereNull('deleted_at')->value('created_at');
            }
            if (!$orderDates[$orderId]) {
                continue;
            }
            $seconds = strtotime((string) $receipt->created_at) - strtotime((string) $orderDates[$orderId]);
            if ($seconds < 0) {
                continue;
            }
            $days = max(1, (int) ceil($seconds / 86400));
            $productIds = [];
            foreach (ShipmentService::decode($receipt->products) as $line) {
                if (is_array($line) && is_numeric($line['id'] ?? null) && (int) $line['id'] > 0) {
                    $productIds[(int) $line['id']] = true;
                }
            }
            foreach (array_keys($productIds) as $productId) {
                if (count($samples[$productId] ?? []) < self::LIMIT) {
                    $samples[$productId][] = $days;
                }
            }
        }
        $result = [];
        foreach ($samples as $productId => $list) {
            $result[$productId] = (int) ceil(array_sum($list) / count($list));
        }

        return $result;
    }

    private static function resolve(array $own): array
    {
        $sb = DB::getSchemaBuilder();
        $facts = [];
        if ($sb->hasColumn('products', self::FACT_FIELD)) {
            foreach (DB::table('products')->whereNotNull(self::FACT_FIELD)->where(self::FACT_FIELD, '!=', '')->pluck(self::FACT_FIELD, 'id') as $id => $fact) {
                $factId = self::firstId($fact);
                if ($factId && $factId !== (int) $id) {
                    $facts[(int) $id] = $factId;
                }
            }
        }
        $specs = [];
        if ($sb->hasTable(self::SPECIFICATIONS) && $sb->hasColumn(self::SPECIFICATIONS, 'finished_product_id')) {
            $query = DB::table(self::SPECIFICATIONS)->whereNotNull('finished_product_id');
            if ($sb->hasColumn(self::SPECIFICATIONS, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            foreach ($query->get(['finished_product_id', 'products']) as $spec) {
                $finished = self::firstId($spec->finished_product_id);
                if (!$finished) {
                    continue;
                }
                foreach (ShipmentService::decode($spec->products) as $line) {
                    if (is_array($line) && is_numeric($line['id'] ?? null) && (int) $line['id'] > 0 && (int) $line['id'] !== $finished) {
                        $specs[$finished][(int) $line['id']] = true;
                    }
                }
            }
        }

        $memo = [];
        $resolve = function (int $id, array $path) use (&$resolve, &$memo, $own, $facts, $specs) {
            if (array_key_exists($id, $memo)) {
                return $memo[$id];
            }
            if (isset($path[$id])) {
                return $own[$id] ?? null;
            }
            $path[$id] = true;
            if (isset($facts[$id])) {
                $value = $resolve($facts[$id], $path);
            } elseif (isset($specs[$id])) {
                $value = null;
                foreach (array_keys($specs[$id]) as $componentId) {
                    $term = $resolve($componentId, $path);
                    if ($term !== null && ($value === null || $term > $value)) {
                        $value = $term;
                    }
                }
                if ($value === null) {
                    $value = $own[$id] ?? null;
                }
            } else {
                $value = $own[$id] ?? null;
            }
            $memo[$id] = $value;

            return $value;
        };

        $result = [];
        foreach (array_unique(array_merge(array_keys($own), array_keys($facts), array_keys($specs))) as $id) {
            $value = $resolve((int) $id, []);
            if ($value !== null) {
                $result[(int) $id] = (int) $value;
            }
        }

        return $result;
    }

    private static function orderFor(int $receiptId): ?int
    {
        $queue = [[self::RECEIPTS, $receiptId]];
        $seen = [self::RECEIPTS . ':' . $receiptId => true];
        while ($queue) {
            [$slug, $id] = array_shift($queue);
            $parents = DB::table('object_relations')->where('target_slug', $slug)->where('target_id', $id)->orderBy('id')->get(['source_slug', 'source_id']);
            foreach ($parents as $parent) {
                if ($parent->source_slug === self::ORDERS) {
                    return (int) $parent->source_id;
                }
            }
            foreach ($parents as $parent) {
                $key = $parent->source_slug . ':' . $parent->source_id;
                if (isset($seen[$key]) || !in_array($parent->source_slug, self::RELATED, true)) {
                    continue;
                }
                $seen[$key] = true;
                $queue[] = [(string) $parent->source_slug, (int) $parent->source_id];
            }
        }

        return null;
    }

    private static function firstId($raw): ?int
    {
        if (is_numeric($raw)) {
            return (int) $raw > 0 ? (int) $raw : null;
        }
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (is_array($decoded)) {
            if (array_key_exists('value', $decoded)) {
                $decoded = (array) $decoded['value'];
            }
            foreach ($decoded as $value) {
                if (is_numeric($value) && (int) $value > 0) {
                    return (int) $value;
                }
            }
        }

        return null;
    }
}
