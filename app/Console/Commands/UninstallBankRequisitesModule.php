<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class UninstallBankRequisitesModule extends Command
{
    protected $signature = 'bank-requisites:uninstall
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--purge : удалить и данные (DROP таблицы bank_requisites, история)}';

    protected $description = 'Удалить модуль «Банковские реквизиты»: вкладку «Модули», сущность bank_requisites, поле привязки и незанятые поля группы «Реквизиты» у компаний';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->uninstallFrom(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->uninstallFrom(\DB::connection(), (string) $tenant->id, true));
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
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->uninstallFrom(\DB::connection(), (string) $target, true));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function uninstallFrom(ConnectionInterface $db, string $label, bool $inTenant): void
    {
        $entity = InstallBankRequisitesModule::ENTITY;

        $this->removeModuleTab($db, $label);

        $typeIds = $db->table('data_types')
            ->where('slug', $entity)->orWhere('name', $entity)
            ->pluck('id');
        if ($typeIds->isNotEmpty()) {
            $db->table('data_rows')->whereIn('data_type_id', $typeIds)->delete();
            $db->table('permissions')->whereIn('entity_id', $typeIds)->delete();
            $db->table('data_types')->whereIn('id', $typeIds)->delete();
        }
        $db->table('field_sections')->where('page', $entity)->delete();
        $db->table('sidebar_items')->where('slug', $entity)->delete();
        $db->table('settings')->where('entity', $entity)->delete();

        $companiesTypeId = $db->table('data_types')->where('slug', 'companies')->value('id');
        if ($companiesTypeId) {
            $db->table('data_rows')
                ->where('data_type_id', $companiesTypeId)
                ->where('field', 'bank_requisite_id')
                ->delete();

            $group = $db->table('data_rows')
                ->where('data_type_id', $companiesTypeId)
                ->where('type', 'text_group')
                ->where('title', InstallBankRequisitesModule::GROUP_TITLE)
                ->first();

            $removed = 0;
            $kept = [];
            foreach (array_keys(InstallBankRequisitesModule::COMPANY_FIELDS) as $field) {
                $row = $db->table('data_rows')
                    ->where('data_type_id', $companiesTypeId)
                    ->where('field', $field)
                    ->first();
                if (!$row) {
                    continue;
                }
                if (count($this->decodeJsonList($row->module))) {
                    $kept[] = (int) $row->id;
                    continue;
                }
                $db->table('data_rows')->where('id', $row->id)->delete();
                $db->table('section_fields_sort')->where('field_id', $row->id)->delete();
                $removed++;
            }

            if ($group) {
                if (count($kept)) {
                    $db->table('data_rows')->where('id', $group->id)->update(['subfields' => json_encode($kept)]);
                } else {
                    $db->table('data_rows')->where('id', $group->id)->delete();
                }
            }

            $this->line("    [{$label}] companies: удалено полей реквизитов {$removed}, оставлено занятых другими модулями " . count($kept));
        }

        InstallSaleDocsEntities::removeBankRequisiteFields($db);

        if ($this->option('purge')) {
            $db->statement('DROP TABLE IF EXISTS `bank_requisites`');
            $db->table('histories')->where('entity', $entity)->delete();
            $this->line("    [{$label}] таблица bank_requisites удалена");
        }

        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->whereIn('url', ['fields/companies', 'fields/' . $entity, 'fields/deals', 'fields/payment_invoices'])->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }

    private function removeModuleTab(ConnectionInterface $db, string $label): void
    {
        $slug = InstallBankRequisitesModule::MODULE_SLUG;

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
                $childs = array_values(array_filter($tab['childs'], fn ($item) => ($item['alias'] ?? null) !== $slug));
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
