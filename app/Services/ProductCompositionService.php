<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductCompositionService
{
    public const COLUMN = 'products';
    public const RECALC_DAYS = 30;

    public static function onSaved($product): void
    {
        if ($product->wasRecentlyCreated) {
            return;
        }
        try {
            if ($product->wasChanged('name')) {
                self::renameInCompositions((int) $product->id, \Modules\Bitrix24\Services\B24ProductSync::nameText($product->name));
            }
            if ($product->wasChanged('product_type') && (string) $product->product_type === '1') {
                self::recalcServicePrices((int) $product->id);
            }
        } catch (\Throwable $e) {
            Log::warning('product composition sync failed', ['product_id' => $product->id, 'error' => $e->getMessage()]);
        }
    }

    public static function tables(): array
    {
        $database = DB::connection()->getDatabaseName();

        return DB::table('information_schema.columns')
            ->where('table_schema', $database)
            ->where('column_name', self::COLUMN)
            ->whereNotIn('table_name', ['histories', 'products'])
            ->selectRaw('table_name as t')
            ->pluck('t')
            ->map(fn ($t) => (string) $t)
            ->all();
    }

    public static function rowsWith(string $table, int $productId)
    {
        return DB::table($table)
            ->where(function ($q) use ($productId) {
                $q->where(self::COLUMN, 'like', '%"id":' . $productId . '%')
                  ->orWhere(self::COLUMN, 'like', '%"id": ' . $productId . '%')
                  ->orWhere(self::COLUMN, 'like', '%"id":"' . $productId . '"%');
            });
    }

    public static function hasProduct($json, int $productId): bool
    {
        $lines = json_decode((string) $json, true);
        if (!is_array($lines)) {
            return false;
        }
        foreach ($lines as $line) {
            if (is_array($line) && (int) ($line['id'] ?? 0) === $productId) {
                return true;
            }
        }

        return false;
    }

    public static function renameInCompositions(int $productId, string $name): int
    {
        if (!$productId || $name === '') {
            return 0;
        }
        $updated = 0;
        foreach (self::tables() as $table) {
            $touched = false;
            foreach (self::rowsWith($table, $productId)->get(['id', self::COLUMN]) as $row) {
                $lines = json_decode((string) $row->{self::COLUMN}, true);
                if (!is_array($lines)) {
                    continue;
                }
                $changed = false;
                foreach ($lines as $i => $line) {
                    if (!is_array($line) || (int) ($line['id'] ?? 0) !== $productId) {
                        continue;
                    }
                    $current = is_array($line['name'] ?? null) ? ($line['name'][0] ?? '') : (string) ($line['name'] ?? '');
                    if ($current === $name) {
                        continue;
                    }
                    $lines[$i]['name'] = $name;
                    $changed = true;
                }
                if (!$changed) {
                    continue;
                }
                DB::table($table)->where('id', $row->id)->update([self::COLUMN => json_encode($lines, JSON_UNESCAPED_UNICODE)]);
                $updated++;
                $touched = true;
            }
            if ($touched) {
                $slug = str_ends_with($table, ProductionService::MATERIALS_SUFFIX)
                    ? substr($table, 0, -strlen(ProductionService::MATERIALS_SUFFIX))
                    : $table;
                DB::table('local_cache')->where('url', 'like', 'objects/' . $slug . '%')->update(['updated_at' => now()]);
            }
        }

        return $updated;
    }

    public static function syncAllNames(bool $dryRun = false): array
    {
        $names = [];
        foreach (DB::table('products')->get(['id', 'name']) as $product) {
            $name = \Modules\Bitrix24\Services\B24ProductSync::nameText($product->name);
            if ($name !== '') {
                $names[(int) $product->id] = $name;
            }
        }
        $result = [];
        foreach (self::tables() as $table) {
            $updated = 0;
            DB::table($table)->whereNotNull(self::COLUMN)->where(self::COLUMN, 'like', '%"id"%')->orderBy('id')
                ->chunk(500, function ($rows) use ($table, $names, $dryRun, &$updated) {
                    foreach ($rows as $row) {
                        $lines = json_decode((string) $row->{self::COLUMN}, true);
                        if (!is_array($lines)) {
                            continue;
                        }
                        $changed = false;
                        foreach ($lines as $i => $line) {
                            $id = is_array($line) ? (int) ($line['id'] ?? 0) : 0;
                            if (!$id || !isset($names[$id])) {
                                continue;
                            }
                            $current = is_array($line['name'] ?? null) ? ($line['name'][0] ?? '') : (string) ($line['name'] ?? '');
                            if ($current !== $names[$id]) {
                                $lines[$i]['name'] = $names[$id];
                                $changed = true;
                            }
                        }
                        if (!$changed) {
                            continue;
                        }
                        if (!$dryRun) {
                            DB::table($table)->where('id', $row->id)->update([self::COLUMN => json_encode($lines, JSON_UNESCAPED_UNICODE)]);
                        }
                        $updated++;
                    }
                });
            if ($updated && !$dryRun) {
                $slug = str_ends_with($table, ProductionService::MATERIALS_SUFFIX)
                    ? substr($table, 0, -strlen(ProductionService::MATERIALS_SUFFIX))
                    : $table;
                DB::table('local_cache')->where('url', 'like', 'objects/' . $slug . '%')->update(['updated_at' => now()]);
            }
            $result[$table] = $updated;
        }

        return $result;
    }

    public static function recalcServicePrices(int $productId): int
    {
        $count = 0;
        foreach (['logistic_tasks' => \App\Models\Task::class, 'deals' => \App\Models\Deal::class] as $table => $class) {
            if (!\Schema::hasTable($table) || !\Schema::hasColumn($table, self::COLUMN)) {
                continue;
            }
            $query = self::rowsWith($table, $productId);
            if (\Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            $query->where('created_at', '>=', now()->subDays(self::RECALC_DAYS));
            foreach ($query->get(['id', self::COLUMN]) as $row) {
                if (!self::hasProduct($row->{self::COLUMN}, $productId)) {
                    continue;
                }
                $model = $class::find($row->id);
                if ($model && method_exists($model, 'recalcServicesPrice')) {
                    $model->recalcServicesPrice();
                    $count++;
                }
            }
        }

        return $count;
    }
}
