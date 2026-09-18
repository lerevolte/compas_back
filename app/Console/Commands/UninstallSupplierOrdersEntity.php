<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\RelationFieldsService;
use Illuminate\Console\Command;

class UninstallSupplierOrdersEntity extends Command
{
    protected $signature = 'entity:uninstall-supplier-orders
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--except= : при all-tenants — не трогать эти порталы (через запятую)}
        {--purge : удалить и данные (DROP таблицы supplier_orders, история, связи)}';

    protected $description = 'Удалить модуль «Заказы поставщикам»: метаданные сущности, поле и вкладку у товаров, поля-связи «Заказы поставщикам» у остальных документов; с --purge — и данные';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->uninstallFrom(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            $except = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('except')))));
            foreach (Tenant::get() as $tenant) {
                if (in_array((string) $tenant->id, $except, true)) {
                    $this->line("  — {$tenant->id}: пропущен (--except)");
                    continue;
                }
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
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->uninstallFrom(\DB::connection(), $target));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function uninstallFrom($db, string $label): void
    {
        $slug = InstallSupplierOrdersEntity::SLUG;
        $typeIds = $db->table('data_types')->where('slug', $slug)->pluck('id');

        if ($typeIds->count()) {
            $rowIds = $db->table('data_rows')->whereIn('data_type_id', $typeIds)->pluck('id');
            $db->table('section_fields_sort')->whereIn('field_id', $rowIds)->delete();
            $db->table('data_rows')->whereIn('data_type_id', $typeIds)->delete();
            $db->table('permissions')->whereIn('entity_id', $typeIds)->delete();
            $db->table('data_types')->whereIn('id', $typeIds)->delete();
        }
        $sectionIds = $db->table('field_sections')->where('page', $slug)->pluck('id');
        if ($sectionIds->count()) {
            $db->table('section_fields_sort')->whereIn('section_id', $sectionIds)->delete();
            $db->table('field_sections')->whereIn('id', $sectionIds)->delete();
        }
        $db->table('settings')->where('entity', $slug)->delete();
        if ($db->getSchemaBuilder()->hasTable('sidebar_items')) {
            $db->table('sidebar_items')->where('slug', $slug)->delete();
        }

        $productsTypeId = $db->table('data_types')->where('slug', 'products')->value('id');
        if ($productsTypeId) {
            $ids = $db->table('data_rows')->where('data_type_id', $productsTypeId)->where('field', InstallSupplierOrdersEntity::PRODUCT_FIELD)->pluck('id');
            if ($ids->count()) {
                $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
                $db->table('data_rows')->whereIn('id', $ids)->delete();
            }
            foreach ($db->table('settings')->where(['type' => 'menu', 'entity' => 'products'])->get() as $menu) {
                $tabs = json_decode($menu->value, true);
                if (!is_array($tabs)) {
                    continue;
                }
                $kept = array_values(array_filter($tabs, fn ($tab) => ($tab['tab'] ?? null) !== InstallSupplierOrdersEntity::PRODUCT_FIELD));
                if (count($kept) !== count($tabs)) {
                    $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($kept, JSON_UNESCAPED_SLASHES)]);
                }
            }
        }

        $this->removeRelationFields($db, $label);

        if ($this->option('purge')) {
            $db->statement("DROP TABLE IF EXISTS `{$slug}`");
            $db->table('histories')->where('entity', $slug)->delete();
            if ($db->getSchemaBuilder()->hasTable('object_relations')) {
                $db->table('object_relations')->where('source_slug', $slug)->orWhere('target_slug', $slug)->delete();
            }
        }

        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }

        $this->line("    [{$label}] {$slug}: удалено" . ($this->option('purge') ? ' вместе с данными' : ''));
    }

    private function removeRelationFields($db, string $label): void
    {
        $slug = InstallSupplierOrdersEntity::SLUG;
        $removed = 0;
        foreach (RelationFieldsService::ENTITIES as $entity) {
            if ($entity === $slug) {
                continue;
            }
            $typeId = $db->table('data_types')->where('slug', $entity)->value('id');
            if (!$typeId) {
                continue;
            }
            $field = RelationFieldsService::field($entity, $slug);
            $ids = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->pluck('id');
            if ($ids->count()) {
                $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
                $db->table('data_rows')->whereIn('id', $ids)->delete();
                $removed += $ids->count();
            }
            foreach ($db->table('settings')->where(['type' => 'menu', 'entity' => $entity])->get(['id', 'value']) as $menu) {
                $tabs = json_decode($menu->value, true);
                if (!is_array($tabs)) {
                    continue;
                }
                $kept = array_values(array_filter($tabs, fn ($tab) => ($tab['tab'] ?? null) !== $field));
                if (count($kept) !== count($tabs)) {
                    $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($kept, JSON_UNESCAPED_SLASHES)]);
                }
            }
            try {
                if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . $entity)->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
        }
        RelationFieldsService::forgetCache();
        if ($removed) {
            $this->line("    [{$label}] поля-связей «Заказы поставщикам» снято: {$removed}");
        }
    }
}
