<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use App\Models\Tenant;

class SyncOrderProductsColumns extends Command
{
    protected $signature = 'products-tab:sync-columns
        {target=all-tenants : all-tenants | <tenant_id> (например avixo)}
        {--dry-run : Показать изменения без записи}';

    protected $description = 'Привести столбцы вкладки «Товары и услуги» (order_products) во всех порталах 1 в 1 с seeds, сохранив кастомные поля товара';

    private const SERVICE_KEYS = [
        'isChoose', 'actions', 'iconDrag', 'iconDelete', 'clicked',
        'product_id', 'product_name', 'product_price', 'product_count',
        'product_weight', 'product_volume', 'product_sum', 'remnant_name',
    ];

    public function handle(): int
    {
        $etalon = $this->loadEtalon();
        if (!$etalon) {
            $this->error('В seeds (users.id=1) не найдена раскладка order_products');
            return self::FAILURE;
        }

        $seedsFields = $this->productFields(\DB::connection('seeds'));
        if (!count($seedsFields)) {
            $this->error('В seeds не найдена сущность products');
            return self::FAILURE;
        }

        $target = $this->argument('target');

        if ($target === 'all-tenants') {
            $tenants = Tenant::where('id', '!=', 'seeds')->get();
            $this->info('Синхронизация всех порталов: '.$tenants->count());
            foreach ($tenants as $tenant) {
                try {
                    $tenant->run(function () use ($tenant, $etalon, $seedsFields) {
                        $this->sync(\DB::connection(), (string) $tenant->id, $etalon, $seedsFields);
                    });
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: ".$e->getMessage());
                }
            }
            $this->info('Готово: all-tenants');
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $stripped = substr($target, strlen($prefix));
                $tenant = Tenant::find($stripped);
                if ($tenant) {
                    $target = $stripped;
                }
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        $this->info("Синхронизация портала {$target}…");
        $tenant->run(function () use ($target, $etalon, $seedsFields) {
            $this->sync(\DB::connection(), (string) $target, $etalon, $seedsFields);
        });
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function loadEtalon(): ?array
    {
        $tables = \DB::connection('seeds')->table('users')->where('id', 1)->value('tables');
        $tables = $tables ? json_decode($tables, true) : null;
        $etalon = is_array($tables) ? ($tables['order_products'] ?? null) : null;
        if (!is_array($etalon) || empty($etalon['fields']) || !is_array($etalon['fields'])) {
            return null;
        }
        return $etalon;
    }

    private function productFields(ConnectionInterface $db): array
    {
        $typeId = $db->table('data_types')->where('slug', 'products')->value('id');
        if (!$typeId) {
            return [];
        }
        return $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->pluck('field')
            ->filter()
            ->values()
            ->all();
    }

    private function sync(ConnectionInterface $db, string $label, array $etalon, array $seedsFields): void
    {
        $dry = (bool) $this->option('dry-run');
        $tenantFields = $this->productFields($db);
        $customFields = array_values(array_diff($tenantFields, $seedsFields));

        if ($customFields) {
            $this->line("    [{$label}] кастомные поля товара: ".implode(', ', $customFields));
        }

        foreach ($db->table('users')->get(['id', 'tables']) as $user) {
            $this->rewriteJson($db, $label, "users#{$user->id}", $user->tables, $etalon, $seedsFields, $customFields, $dry, function ($json) use ($db, $user) {
                $db->table('users')->where('id', $user->id)->update(['tables' => $json]);
            });
        }

        foreach ($db->table('roles')->get(['id', 'tables', 'display_name']) as $role) {
            $this->rewriteJson($db, $label, "roles#{$role->id}", $role->tables, $etalon, $seedsFields, $customFields, $dry, function ($json) use ($db, $role) {
                $db->table('roles')->where('id', $role->id)->update(['tables' => $json]);
            });
        }

        foreach ($db->table('settings')->where('key', 'tables')->get(['id', 'value']) as $setting) {
            $this->rewriteJson($db, $label, "settings#{$setting->id}", $setting->value, $etalon, $seedsFields, $customFields, $dry, function ($json) use ($db, $setting) {
                $db->table('settings')->where('id', $setting->id)->update(['value' => $json]);
            });
        }

        if (!$dry) {
            $db->table('local_cache')->where('url', 'tables/order_products')->update(['updated_at' => now()]);
        }
    }

    private function rewriteJson(ConnectionInterface $db, string $label, string $who, $raw, array $etalon, array $seedsFields, array $customFields, bool $dry, \Closure $write): void
    {
        $tables = $raw ? json_decode($raw, true) : [];
        if (!is_array($tables)) {
            $tables = [];
        }

        $current = $tables['order_products']['fields'] ?? [];
        $currentByKey = [];
        foreach ((is_array($current) ? $current : []) as $col) {
            if (is_array($col) && isset($col['key'])) {
                $currentByKey[$col['key']] = $col;
            }
        }

        $new = [];
        foreach ($etalon['fields'] as $col) {
            if (!is_array($col) || !isset($col['key'])) {
                continue;
            }
            $key = $col['key'];
            if (in_array($key, self::SERVICE_KEYS, true) || in_array($key, $seedsFields, true)) {
                $new[$key] = $col;
            }
        }
        foreach ($currentByKey as $key => $col) {
            if (in_array($key, $customFields, true) && !isset($new[$key])) {
                $new[$key] = $col;
            }
        }

        $i = 0;
        foreach ($new as $key => $col) {
            $new[$key]['index'] = $i++;
        }

        $result = [
            'fields' => array_values($new),
            'sort_field' => $etalon['sort_field'] ?? null,
            'sort_order' => $etalon['sort_order'] ?? null,
        ];

        $oldKeys = array_keys($currentByKey);
        $newKeys = array_keys($new);
        $added = array_values(array_diff($newKeys, $oldKeys));
        $removed = array_values(array_diff($oldKeys, $newKeys));

        if (!count($current) && !count($added) && !count($removed)) {
            return;
        }

        if ($added || $removed || json_encode($result) !== json_encode($tables['order_products'] ?? null)) {
            $msg = "    [{$label}] {$who}: ";
            $msg .= $added ? '+'.implode(',', $added).' ' : '';
            $msg .= $removed ? '-'.implode(',', $removed) : '';
            $this->line(rtrim($msg).($dry ? ' (dry-run)' : ''));

            if (!$dry) {
                $tables['order_products'] = $result;
                $write(json_encode($tables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }
    }
}
