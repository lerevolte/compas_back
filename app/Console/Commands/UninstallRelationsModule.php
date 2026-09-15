<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ModuleLayoutService;
use App\Services\RelationFieldsService;
use Illuminate\Console\Command;

class UninstallRelationsModule extends Command
{
    protected $signature = 'relations:uninstall-module
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--purge : удалить и колонки полей-связей (deal_id остаётся)}';

    protected $description = 'Удалить модуль «Связанные документы»: поля-связи, раздел модуля, пункт меню, строку modules (object_relations не трогаются)';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->uninstall(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->uninstall(\DB::connection(), (string) $tenant->id, true));
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->uninstall(\DB::connection(), (string) $tenant->id, true));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function uninstall($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        $module = RelationFieldsService::MODULE;

        foreach (RelationFieldsService::ENTITIES as $slug) {
            $type = $db->table('data_types')->where('slug', $slug)->first(['id']);
            if (!$type) {
                continue;
            }
            $sectionIds = ModuleLayoutService::moduleSectionIds($db, $slug, $module);
            foreach ($sectionIds as $sid) {
                foreach ($db->table('data_rows')->whereJsonContains('module_section_id', $sid)->get() as $row) {
                    ModuleLayoutService::detachField($db, $row, $module, $sid);
                }
                $db->table('section_fields_sort')->where('section_id', $sid)->delete();
                $db->table('field_sections')->where('id', $sid)->delete();
            }

            foreach (RelationFieldsService::ENTITIES as $target) {
                if ($target === $slug || RelationFieldsService::isSingle($slug, $target)) {
                    continue;
                }
                $field = RelationFieldsService::field($slug, $target);
                $ids = $db->table('data_rows')->where('data_type_id', $type->id)->where('field', $field)->pluck('id');
                if ($ids->count()) {
                    $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
                    $db->table('data_rows')->whereIn('id', $ids)->delete();
                }
                if ($this->option('purge') && $sb->hasTable($slug) && $sb->hasColumn($slug, $field)) {
                    $db->statement("ALTER TABLE `{$slug}` DROP COLUMN `{$field}`");
                }
            }

            $singleRow = $db->table('data_rows')->where('data_type_id', $type->id)->where('field', 'deal_id')->first();
            if ($singleRow && $singleRow->section_id === null) {
                $sectionId = $db->table('field_sections')
                    ->where('page', $slug)
                    ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                    ->orderBy('sort')
                    ->value('id');
                $db->table('data_rows')->where('id', $singleRow->id)->update(['section_id' => $sectionId]);
            }

            foreach ($db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->get() as $menu) {
                $tabs = json_decode($menu->value, true);
                if (!is_array($tabs)) {
                    continue;
                }
                $changed = false;
                foreach ($tabs as $k => $tab) {
                    if (($tab['tab'] ?? null) !== 'modules') {
                        continue;
                    }
                    $childs = array_values(array_filter($tab['childs'] ?? [], fn ($c) => ($c['alias'] ?? null) !== $module));
                    if (count($childs) !== count($tab['childs'] ?? [])) {
                        $tabs[$k]['childs'] = $childs;
                        $changed = true;
                    }
                }
                if ($changed) {
                    $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($tabs, JSON_UNESCAPED_SLASHES)]);
                }
            }

            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
        }

        $db->table('modules')->where('slug', $module)->delete();
        RelationFieldsService::forgetCache();

        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
        $this->line("    [{$label}] модуль «" . RelationFieldsService::MODULE_TITLE . "» удалён");
    }
}
