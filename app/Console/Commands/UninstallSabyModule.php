<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class UninstallSabyModule extends Command
{
    protected $signature = 'saby:uninstall
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--purge : удалить и данные (таблицы saby_config, saby_waybills)}';

    protected $description = 'Удалить модуль «Транспортные накладные Saby»: метаданные полей, при --purge — и таблицы';

    private const FIELDS = [
        'routes' => ['receiver_company_id', 'request_number', 'request_date', 'saby_waybills'],
        'companies' => ['inn', 'kpp', 'address'],
        'cars' => ['ownership_type', 'number'],
        'employees' => ['inn'],
        'products' => ['packing_method', 'tare_type'],
    ];

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->uninstallFrom(\DB::connection('seeds'), 'admin_seeds');
            $this->info('Готово: admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->uninstallFrom(\DB::connection(), (string) $tenant->id));
                    $this->info("  ✓ {$tenant->id}");
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

        $tenant->run(fn () => $this->uninstallFrom(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function uninstallFrom(ConnectionInterface $db, string $label): void
    {
        foreach (self::FIELDS as $entity => $fields) {
            $dataType = $db->table('data_types')->where('slug', $entity)->first();
            if (!$dataType) {
                continue;
            }
            $deleted = $db->table('data_rows')
                ->where('data_type_id', $dataType->id)
                ->whereIn('field', $fields)
                ->delete();
            if ($deleted) {
                $this->line("    [{$label}] удалено полей {$entity}: {$deleted}");
            }
        }

        if ($this->option('purge')) {
            $sb = $db->getSchemaBuilder();
            foreach (['saby_waybills', 'saby_config'] as $table) {
                if ($sb->hasTable($table)) {
                    $sb->drop($table);
                    $this->line("    [{$label}] таблица {$table} удалена");
                }
            }
        }

        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
