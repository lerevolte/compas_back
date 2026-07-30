<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

/**
 * Удаляет сущность «Сделки» (deals) из портала — парная команда к
 * entity:install-deals (контракт для будущей страницы «Модули», см. CLAUDE.md).
 *
 * По умолчанию удаляются только метаданные (data_types, data_rows,
 * field_sections, settings, sidebar_items, permissions) — таблицы с данными
 * сохраняются, повторный entity:install-deals вернёт сущность с данными.
 * С флагом --purge удаляются и данные: таблицы deals, пивоты
 * contact_deal/company_deal, история сущности.
 */
class UninstallDealsEntity extends Command
{
    protected $signature = 'entity:uninstall-deals
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--purge : удалить и данные (DROP таблиц deals/contact_deal/company_deal, история)}';

    protected $description = 'Удалить сущность «Сделки» (deals) из admin_seeds / тенанта / всех тенантов';

    public function handle(): int
    {
        $target = $this->argument('target');

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
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $tenant = Tenant::find(substr($target, strlen($prefix)));
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

    private function uninstallFrom($db, string $label): void
    {
        $typeIds = $db->table('data_types')
            ->where('name', 'deals')->orWhere('slug', 'deals')
            ->pluck('id');

        if ($typeIds->isNotEmpty()) {
            $db->table('data_rows')->whereIn('data_type_id', $typeIds)->delete();
            $db->table('permissions')->whereIn('entity_id', $typeIds)->delete();
            $db->table('data_types')->whereIn('id', $typeIds)->delete();
        }
        $db->table('field_sections')->where('page', 'deals')->delete();
        $db->table('sidebar_items')->where('slug', 'deals')->delete();
        $db->table('settings')->where('entity', 'deals')->delete();

        if ($this->option('purge')) {
            $db->statement('DROP TABLE IF EXISTS `deals`');
            $db->statement('DROP TABLE IF EXISTS `contact_deal`');
            $db->statement('DROP TABLE IF EXISTS `company_deal`');
            $db->table('histories')->where('entity', 'deals')->delete();
        }

        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }

        $this->line("    [{$label}] deals удалена" . ($this->option('purge') ? ' (с данными)' : ' (данные сохранены)'));
    }
}
