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
        'logistic_tasks' => ['shipment_company_id', 'company_id', 'contact_id', 'saby_waybills'],
        'companies' => ['inn', 'kpp', 'address'],
        'cars' => ['ownership_type', 'vehicle_type', 'trailer_number', 'number'],
        'employees' => ['inn', 'snils', 'driver_license'],
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
        $this->removeModuleTab($db, $label);

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

    private function removeModuleTab(ConnectionInterface $db, string $label): void
    {
        $slug = InstallSabyModule::MODULE_SLUG;

        $sections = $db->table('field_sections')->where('module', $slug)->get(['id', 'page']);
        $sectionIds = $sections->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count($sectionIds)) {
            $rows = $db->table('data_rows')
                ->where(function ($q) use ($slug) {
                    $q->where('module', 'LIKE', "%\"{$slug}\"%")->orWhere('module', $slug);
                })
                ->orWhere(function ($q) use ($sectionIds) {
                    foreach ($sectionIds as $id) {
                        $q->orWhere('module_section_id', 'LIKE', "%{$id}%");
                    }
                })
                ->get(['id', 'module', 'module_section_id']);

            $detached = 0;
            foreach ($rows as $row) {
                $modules = $this->decodeJsonList($row->module);
                $rowSections = array_map('intval', $this->decodeJsonList($row->module_section_id));

                $newSections = array_values(array_diff($rowSections, $sectionIds));
                $newModules = array_values(array_filter($modules, fn ($m) => $m !== $slug));

                if ($newSections === $rowSections && $newModules === $modules) {
                    continue;
                }

                $db->table('data_rows')->where('id', $row->id)->update([
                    'module' => $newModules ? json_encode($newModules) : null,
                    'module_section_id' => $newSections ? json_encode($newSections) : null,
                ]);
                $detached++;
            }

            $db->table('section_fields_sort')->whereIn('section_id', $sectionIds)->delete();
            $db->table('field_sections')->whereIn('id', $sectionIds)->delete();

            $this->line("    [{$label}] отвязано полей: {$detached}, удалено секций модуля: " . count($sectionIds));
        }

        foreach ($db->table('settings')->where('type', 'menu')->get() as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs)) {
                continue;
            }

            $changed = false;
            foreach ($tabs as $k => $tab) {
                if (($tab['tab'] ?? null) !== 'modules' || !isset($tab['childs'])) {
                    continue;
                }
                $childs = array_values(array_filter($tab['childs'], fn ($child) => ($child['alias'] ?? null) !== $slug));
                if (count($childs) !== count($tab['childs'])) {
                    $tabs[$k]['childs'] = $childs;
                    $changed = true;
                }
            }

            if ($changed) {
                $db->table('settings')->where('id', $menu->id)->update([
                    'value' => json_encode($tabs, JSON_UNESCAPED_SLASHES),
                ]);
            }
        }

        $db->table('modules')->where('slug', $slug)->delete();
    }

    private function decodeJsonList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded, fn ($v) => $v !== null && $v !== ''));
        }

        return [(string) $value];
    }
}
