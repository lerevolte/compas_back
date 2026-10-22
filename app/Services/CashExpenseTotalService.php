<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CashExpenseTotalService
{
    public const FIELD = 'cash_expenses_total';
    public const TITLE = 'Расходы по связанным документам';
    public const EXPENSES = 'cash_expenses';
    public const TREE_ROOTS = ['deals', 'supplier_orders'];

    private static array $ready = [];

    public static function ready(string $slug, $db = null): bool
    {
        $db = $db ?: DB::connection();
        $key = $db->getName() . ':' . (function_exists('tenant') ? (string) tenant('id') : '') . ':' . $slug;
        if (!array_key_exists($key, self::$ready)) {
            try {
                $sb = $db->getSchemaBuilder();
                self::$ready[$key] = in_array($slug, RelationFieldsService::CASH_TARGETS, true)
                    && $sb->hasTable('object_relations')
                    && $sb->hasTable(self::EXPENSES)
                    && $sb->hasColumn($slug, self::FIELD);
            } catch (\Throwable $e) {
                self::$ready[$key] = false;
            }
        }

        return self::$ready[$key];
    }

    public static function anyReady($db = null): bool
    {
        foreach (RelationFieldsService::CASH_TARGETS as $slug) {
            if (self::ready($slug, $db)) {
                return true;
            }
        }

        return false;
    }

    public static function forget(): void
    {
        self::$ready = [];
    }

    public static function recalcAround(string $slug, int $id, $db = null): void
    {
        if (!$id) {
            return;
        }
        $db = $db ?: DB::connection();
        try {
            if (!self::anyReady($db)) {
                return;
            }
            $targets = [];
            $start = $slug === self::EXPENSES ? self::neighbours($slug, $id, $db) : [[$slug, $id]];
            foreach ($start as [$nodeSlug, $nodeId]) {
                $targets[$nodeSlug . ':' . $nodeId] = [$nodeSlug, $nodeId];
                foreach (self::ancestors($nodeSlug, $nodeId, $db) as $key => $pair) {
                    $targets[$key] = $pair;
                }
            }
            foreach ($targets as [$targetSlug, $targetId]) {
                self::recalc($targetSlug, $targetId, $db);
            }
        } catch (\Throwable $e) {
            \Log::warning('cash totals: не удалось пересчитать расходы по связанным документам', ['object' => $slug . '#' . $id, 'error' => $e->getMessage()]);
        }
    }

    public static function recalc(string $slug, int $id, $db = null): void
    {
        $db = $db ?: DB::connection();
        if (!$id || !self::ready($slug, $db)) {
            return;
        }
        $total = self::compute($slug, $id, $db);
        $current = $db->table($slug)->where('id', $id)->value(self::FIELD);
        if ($current === null || abs((float) $current - $total) > 0.001) {
            $db->table($slug)->where('id', $id)->update([self::FIELD => round($total, 2)]);
        }
    }

    public static function compute(string $slug, int $id, $db = null): float
    {
        $db = $db ?: DB::connection();
        $nodes = in_array($slug, self::TREE_ROOTS, true) ? self::descendants($slug, $id, $db) : [$slug . ':' . $id => [$slug, $id]];
        $expenseIds = [];
        foreach ($nodes as [$nodeSlug, $nodeId]) {
            if ($nodeSlug === self::EXPENSES) {
                $expenseIds[$nodeId] = true;
                continue;
            }
            foreach (RelationFieldsService::relatedIds($nodeSlug, $nodeId, self::EXPENSES, $db) as $expenseId) {
                $expenseIds[$expenseId] = true;
            }
        }
        if (!count($expenseIds)) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($db->table(self::EXPENSES)->whereIn('id', array_keys($expenseIds))->whereNull('deleted_at')->pluck('sum') as $sum) {
            $total += self::number($sum);
        }

        return $total;
    }

    public static function backfill(string $slug, $db = null): int
    {
        $db = $db ?: DB::connection();
        if (!self::ready($slug, $db)) {
            return 0;
        }
        $count = 0;
        $query = $db->table($slug)->orderBy('id');
        if ($db->getSchemaBuilder()->hasColumn($slug, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        foreach ($query->pluck('id') as $id) {
            self::recalc($slug, (int) $id, $db);
            $count++;
        }

        return $count;
    }

    private static function neighbours(string $slug, int $id, $db): array
    {
        $result = [];
        foreach ($db->table('object_relations')->where('target_slug', $slug)->where('target_id', $id)->get(['source_slug', 'source_id']) as $row) {
            $result[] = [(string) $row->source_slug, (int) $row->source_id];
        }
        foreach ($db->table('object_relations')->where('source_slug', $slug)->where('source_id', $id)->get(['target_slug', 'target_id']) as $row) {
            $result[] = [(string) $row->target_slug, (int) $row->target_id];
        }

        return $result;
    }

    private static function ancestors(string $slug, int $id, $db): array
    {
        $result = [];
        $queue = [[$slug, $id]];
        $seen = [$slug . ':' . $id => true];
        while ($queue) {
            [$nodeSlug, $nodeId] = array_shift($queue);
            foreach ($db->table('object_relations')->where('target_slug', $nodeSlug)->where('target_id', $nodeId)->get(['source_slug', 'source_id']) as $row) {
                $key = $row->source_slug . ':' . $row->source_id;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $pair = [(string) $row->source_slug, (int) $row->source_id];
                $result[$key] = $pair;
                $queue[] = $pair;
            }
        }

        return $result;
    }

    private static function descendants(string $slug, int $id, $db): array
    {
        $result = [$slug . ':' . $id => [$slug, $id]];
        $queue = [[$slug, $id]];
        while ($queue) {
            [$nodeSlug, $nodeId] = array_shift($queue);
            foreach ($db->table('object_relations')->where('source_slug', $nodeSlug)->where('source_id', $nodeId)->get(['target_slug', 'target_id']) as $row) {
                $key = $row->target_slug . ':' . $row->target_id;
                if (isset($result[$key]) || !self::alive((string) $row->target_slug, (int) $row->target_id, $db)) {
                    continue;
                }
                $pair = [(string) $row->target_slug, (int) $row->target_id];
                $result[$key] = $pair;
                $queue[] = $pair;
            }
        }

        return $result;
    }

    private static function alive(string $slug, int $id, $db): bool
    {
        $sb = $db->getSchemaBuilder();
        if (!$sb->hasTable($slug)) {
            return false;
        }
        $query = $db->table($slug)->where('id', $id);
        if ($sb->hasColumn($slug, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    private static function number($value): float
    {
        $value = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim((string) $value));

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
