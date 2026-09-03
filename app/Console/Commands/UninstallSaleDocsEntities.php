<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class UninstallSaleDocsEntities extends Command
{
    protected $signature = 'entity:uninstall-sale-docs
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--purge : удалить и данные (DROP таблиц, история)}';

    protected $description = 'Удалить сущности «Счета на оплату», «Отгрузки», «Оприходования», вкладку «Печать документов» и поле «Банковские реквизиты» у заказов';

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

    private function uninstallFrom($db, string $label, bool $inTenant): void
    {
        foreach (array_keys(InstallSaleDocsEntities::ENTITIES) as $slug) {
            $typeIds = $db->table('data_types')
                ->where('slug', $slug)->orWhere('name', $slug)
                ->pluck('id');
            if ($typeIds->isNotEmpty()) {
                $db->table('data_rows')->whereIn('data_type_id', $typeIds)->delete();
                $db->table('permissions')->whereIn('entity_id', $typeIds)->delete();
                $db->table('data_types')->whereIn('id', $typeIds)->delete();
            }
            $db->table('field_sections')->where('page', $slug)->delete();
            $db->table('sidebar_items')->where('slug', $slug)->delete();
            $db->table('settings')->where('entity', $slug)->delete();

            if ($this->option('purge')) {
                $db->statement("DROP TABLE IF EXISTS `{$slug}`");
                $db->table('histories')->where('entity', $slug)->delete();
            }
            $this->line("    [{$label}] {$slug}: метаданные удалены");
        }

        InstallSaleDocsEntities::removeBankRequisiteFields($db);

        foreach ($db->table('settings')->where(['type' => 'menu', 'entity' => 'deals'])->get() as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs)) {
                continue;
            }
            $filtered = array_values(array_filter($tabs, fn ($tab) => ($tab['tab'] ?? null) !== InstallSaleDocsEntities::PRINT_TAB));
            if (count($filtered) !== count($tabs)) {
                $db->table('settings')->where('id', $menu->id)->update([
                    'value' => json_encode($filtered, JSON_UNESCAPED_SLASHES),
                ]);
            }
        }

        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/deals')->update(['updated_at' => now()]);
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
}
