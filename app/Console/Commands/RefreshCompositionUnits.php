<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Route;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefreshCompositionUnits extends Command
{
    protected $signature = 'products:refresh-composition
        {target=avixo : all-tenants | <tenant_id>}
        {--dry-run : показать, что изменится, без записи}';

    protected $description = 'Обновить вес и объём единицы товара в составах документов по текущим значениям товаров и пересчитать общий вес/объём документов';

    public const ENTITIES = ['logistic_tasks', 'pickups', 'deals', 'supplier_orders', 'payment_invoices', 'expense_invoices', 'product_returns', 'receipt_invoices', 'addresses', 'warehouses'];

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->refresh((string) $tenant->id));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function refresh(string $label): void
    {
        $dry = (bool) $this->option('dry-run');
        if (!Schema::hasTable('products')) {
            return;
        }
        $units = [];
        $routeIds = [];
        foreach (self::ENTITIES as $slug) {
            if (!Schema::hasTable($slug) || !Schema::hasColumn($slug, 'products')) {
                continue;
            }
            $hasWeight = Schema::hasColumn($slug, 'weight');
            $hasVolume = Schema::hasColumn($slug, 'volume');
            $hasRoute = $slug === 'logistic_tasks' && Schema::hasColumn($slug, 'route_id');
            $columns = array_merge(['id', 'products'], $hasRoute ? ['route_id'] : []);
            $changed = 0;
            DB::table($slug)->whereNotNull('products')->whereNotIn('products', ['', '[]'])->orderBy('id')->select($columns)
                ->chunkById(200, function ($rows) use (&$units, &$routeIds, &$changed, $slug, $hasWeight, $hasVolume, $hasRoute, $dry) {
                    $decoded = [];
                    $missing = [];
                    foreach ($rows as $row) {
                        $lines = json_decode((string) $row->products, true);
                        if (!is_array($lines)) {
                            continue;
                        }
                        $decoded[$row->id] = $lines;
                        foreach ($lines as $line) {
                            $productId = is_array($line) ? (int) ($line['id'] ?? 0) : 0;
                            if ($productId && !array_key_exists($productId, $units)) {
                                $missing[$productId] = true;
                            }
                        }
                    }
                    if (count($missing)) {
                        foreach (DB::table('products')->whereIn('id', array_keys($missing))->get(['id', 'weight', 'volume']) as $product) {
                            $units[(int) $product->id] = $product;
                        }
                        foreach (array_keys($missing) as $productId) {
                            $units[$productId] = $units[$productId] ?? null;
                        }
                    }
                    foreach ($rows as $row) {
                        if (!isset($decoded[$row->id])) {
                            continue;
                        }
                        $lines = $decoded[$row->id];
                        $dirty = false;
                        $weight = 0;
                        $volume = 0;
                        foreach ($lines as $i => $line) {
                            if (!is_array($line)) {
                                continue;
                            }
                            $unit = $units[(int) ($line['id'] ?? 0)] ?? null;
                            if ($unit) {
                                foreach (['weight', 'volume'] as $key) {
                                    $value = Product::unitValue($unit->{$key} ?? null, $line[$key] ?? null);
                                    if ((string) $value !== (string) ($line[$key] ?? null) && (float) $value !== (float) ($line[$key] ?? 0)) {
                                        $lines[$i][$key] = $value;
                                        $dirty = true;
                                    }
                                }
                            }
                            $count = (float) ($lines[$i]['count'] ?? 0);
                            $weight += $count * (float) ($lines[$i]['weight'] ?? 0);
                            $volume += $count * (float) ($lines[$i]['volume'] ?? 0);
                        }
                        if (!$dirty) {
                            continue;
                        }
                        $changed++;
                        if ($dry) {
                            continue;
                        }
                        $update = ['products' => json_encode($lines, JSON_UNESCAPED_UNICODE)];
                        if ($hasWeight) {
                            $update['weight'] = $weight;
                        }
                        if ($hasVolume) {
                            $update['volume'] = $volume;
                        }
                        DB::table($slug)->where('id', $row->id)->update($update);
                        if ($hasRoute && $row->route_id) {
                            $routeIds[(int) $row->route_id] = true;
                        }
                    }
                });
            if ($changed) {
                $this->line("    [{$label}] {$slug}: документов с устаревшим весом/объёмом — {$changed}");
            }
        }
        if (!$dry) {
            foreach (array_keys($routeIds) as $routeId) {
                try {
                    Route::find($routeId)?->recalculateTotals();
                } catch (\Throwable $e) {
                }
            }
        }
        $this->info("  ✓ {$label}" . ($dry ? ' (dry-run)' : ''));
    }
}
