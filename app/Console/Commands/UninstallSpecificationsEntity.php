<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class UninstallSpecificationsEntity extends Command
{
    protected $signature = 'entity:uninstall-specifications
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--purge : удалить также таблицу и историю}';

    protected $description = 'Снять сущность «Спецификации»: метаданные, меню и права (данные сохраняются, --purge удаляет и их)';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->uninstallFrom(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->uninstallFrom(\DB::connection(), (string) $tenant->id, true));
                $this->info("  ✓ {$tenant->id}");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function uninstallFrom($db, string $label, bool $inTenant): void
    {
        $slug = InstallSpecificationsEntity::SLUG;
        $sb = $db->getSchemaBuilder();

        $typeIds = $db->table('data_types')->where('slug', $slug)->pluck('id');
        if ($typeIds->count()) {
            $rowIds = $db->table('data_rows')->whereIn('data_type_id', $typeIds)->pluck('id');
            if ($sb->hasTable('section_fields_sort')) {
                $db->table('section_fields_sort')->whereIn('field_id', $rowIds)->delete();
            }
            $db->table('data_rows')->whereIn('data_type_id', $typeIds)->delete();
            $db->table('permissions')->whereIn('entity_id', $typeIds)->delete();
            $db->table('data_types')->whereIn('id', $typeIds)->delete();
        }
        $sectionIds = $db->table('field_sections')->where('page', $slug)->pluck('id');
        if ($sectionIds->count()) {
            if ($sb->hasTable('section_fields_sort')) {
                $db->table('section_fields_sort')->whereIn('section_id', $sectionIds)->delete();
            }
            $db->table('field_sections')->whereIn('id', $sectionIds)->delete();
        }
        $db->table('settings')->where('entity', $slug)->delete();
        \App\Services\ReverseLinkService::removeField($db, 'products', \App\Models\Specification::PRODUCT_FIELD);
        if ($sb->hasTable('sidebar_items')) {
            $db->table('sidebar_items')->where('slug', $slug)->delete();
        }

        if ($this->option('purge')) {
            $db->statement("DROP TABLE IF EXISTS `{$slug}`");
            $db->table('histories')->where('entity', $slug)->delete();
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                $db->table('local_cache')->where('url', 'sidebar')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }

        $this->line("    [{$label}] {$slug}: удалено" . ($this->option('purge') ? ' вместе с данными' : ''));
    }
}
