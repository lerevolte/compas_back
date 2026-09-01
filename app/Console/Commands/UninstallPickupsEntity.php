<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class UninstallPickupsEntity extends Command
{
    protected $signature = 'entity:uninstall-pickups
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--purge : удалить и данные (DROP таблицы pickups, история, связи)}';

    protected $description = 'Удалить сущность «Самовывозы» (pickups): метаданные; с --purge — и данные';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->uninstallFrom(\DB::connection('seeds'), 'admin_seeds');
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
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->uninstallFrom(\DB::connection(), $target));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function uninstallFrom($db, string $label): void
    {
        $slug = InstallPickupsEntity::SLUG;
        $typeIds = $db->table('data_types')->where('slug', $slug)->pluck('id');

        if ($typeIds->count()) {
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
}
