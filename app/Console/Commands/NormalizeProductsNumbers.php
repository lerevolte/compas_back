<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeProductsNumbers extends Command
{
    protected $signature = 'products:normalize-numbers
        {target=avixo : all-tenants | <tenant_id>}
        {--dry-run : показать количество без записи}';

    protected $description = 'Привести price/count/weight/volume/sum в JSON «Состав» к числам (строки вида "225.0000" из Bitrix24 → 225)';

    private const TABLES = ['payment_invoices', 'expense_invoices', 'product_returns', 'deals', 'logistic_tasks'];
    private const KEYS = ['price', 'count', 'weight', 'volume', 'sum', 'shipped'];

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->normalize((string) $tenant->id));
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        $tenant->run(fn () => $this->normalize($target));

        return self::SUCCESS;
    }

    private function normalize(string $label): void
    {
        $dry = (bool) $this->option('dry-run');

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'products')) {
                continue;
            }
            $changed = 0;
            DB::table($table)
                ->whereNotNull('products')
                ->where('products', '!=', '')
                ->orderBy('id')
                ->select(['id', 'products'])
                ->chunk(500, function ($rows) use ($table, $dry, &$changed) {
                    foreach ($rows as $row) {
                        $products = json_decode((string) $row->products, true);
                        if (!is_array($products)) {
                            continue;
                        }
                        $dirty = false;
                        foreach ($products as $i => $product) {
                            if (!is_array($product)) {
                                continue;
                            }
                            foreach (self::KEYS as $key) {
                                if (!array_key_exists($key, $product) || $product[$key] === null) {
                                    continue;
                                }
                                $normalized = $this->num($product[$key]);
                                if ($normalized !== $product[$key]) {
                                    $products[$i][$key] = $normalized;
                                    $dirty = true;
                                }
                            }
                        }
                        if (!$dirty) {
                            continue;
                        }
                        $changed++;
                        if (!$dry) {
                            DB::table($table)->where('id', $row->id)->update(['products' => json_encode($products, JSON_UNESCAPED_UNICODE)]);
                        }
                    }
                });
            $this->info("  [{$label}] {$table}: " . ($dry ? 'к обновлению ' : 'обновлено ') . $changed);
        }
    }

    private function num($value)
    {
        if (is_int($value) || is_float($value)) {
            return $value == (int) $value ? (int) $value : round($value, 4);
        }
        if (!is_string($value) || !is_numeric(str_replace(',', '.', trim($value)))) {
            return $value;
        }
        $float = round((float) str_replace(',', '.', trim($value)), 4);

        return $float == (int) $float ? (int) $float : $float;
    }
}
