<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

/**
 * Удаляет сущность «Контакты» (contacts) из портала — парная команда к
 * entity:install-contacts (контракт для будущей страницы «Модули», см. CLAUDE.md).
 *
 * По умолчанию удаляются только метаданные, включая откат патча companies
 * (скрытое поле «Контакт» — data_rows); колонки companies (contact_id/b24_id/
 * deal_id) и таблицы с данными сохраняются. С флагом --purge удаляются и
 * данные: таблицы contacts, пивот company_contact, история сущности.
 */
class UninstallContactsEntity extends Command
{
    protected $signature = 'entity:uninstall-contacts
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--purge : удалить и данные (DROP таблиц contacts/company_contact, история)}';

    protected $description = 'Удалить сущность «Контакты» (contacts) из admin_seeds / тенанта / всех тенантов';

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
            ->where('name', 'contacts')->orWhere('slug', 'contacts')
            ->pluck('id');

        if ($typeIds->isNotEmpty()) {
            $db->table('data_rows')->whereIn('data_type_id', $typeIds)->delete();
            $db->table('permissions')->whereIn('entity_id', $typeIds)->delete();
            $db->table('data_types')->whereIn('id', $typeIds)->delete();
        }
        $db->table('field_sections')->where('page', 'contacts')->delete();
        $db->table('sidebar_items')->where('slug', 'contacts')->delete();
        $db->table('settings')->where('entity', 'contacts')->delete();

        $companiesType = $db->table('data_types')
            ->where('slug', 'companies')->orWhere('name', 'companies')
            ->first();
        if ($companiesType) {
            $db->table('data_rows')
                ->where('data_type_id', $companiesType->id)
                ->where('field', 'contact_id')
                ->delete();
        }

        if ($this->option('purge')) {
            $db->statement('DROP TABLE IF EXISTS `contacts`');
            $db->statement('DROP TABLE IF EXISTS `company_contact`');
            $db->table('histories')->where('entity', 'contacts')->delete();
        }

        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }

        $this->line("    [{$label}] contacts удалена" . ($this->option('purge') ? ' (с данными)' : ' (данные сохранены)'));
    }
}
