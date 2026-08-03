<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

/**
 * Устанавливает сущность «Контакты» (contacts) в портал.
 *
 * Контакты синхронизируются с Bitrix24: ФИО, множественные Email/Телефон
 * (type multi_text — набор инпутов, как в Bitrix24), связь «Компания»
 * (plural relation -> companies, пивот company_contact).
 *
 * Дополнительно патчит companies: колонки contact_id/b24_id/deal_id и
 * СКРЫТОЕ поле «Контакт» (data_rows c section_id=0 — несуществующая секция,
 * поле не рендерится до одобрения; после одобрения переставить section_id).
 *
 * Сущность видима (hidden=0) и выводится в сайдбар (sidebar_items,
 * slug contacts) — фича одобрена. Команда идемпотентна.
 *   php artisan entity:install-contacts avixo
 */
class InstallContactsEntity extends Command
{
    protected $signature = 'entity:install-contacts
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Установить сущность «Контакты» (contacts) в admin_seeds / тенант / все тенанты';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id));
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
        $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function installInto($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();

        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `choosed_at` timestamp NULL DEFAULT NULL,
  `name` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `emails` text DEFAULT NULL,
  `phones` text DEFAULT NULL,
  `company_id` text DEFAULT NULL,
  `deal_id` int(11) DEFAULT NULL,
  `b24_id` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $db->statement('CREATE TABLE IF NOT EXISTS `company_contact` (
            `company_id` INT NOT NULL, `contact_id` INT NOT NULL,
            UNIQUE KEY `company_contact_unique` (`company_id`, `contact_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $contactsIndexes = collect($db->select('SHOW INDEX FROM `contacts`'))->pluck('Key_name');
        if (!$contactsIndexes->contains('contacts_b24_id_index')) {
            $db->statement('ALTER TABLE `contacts` ADD INDEX `contacts_b24_id_index` (`b24_id`)');
        }

        if ($sb->hasTable('companies')) {
            foreach (['contact_id' => 'TEXT NULL', 'b24_id' => 'VARCHAR(32) NULL', 'deal_id' => 'INT NULL'] as $col => $ddl) {
                if (!$sb->hasColumn('companies', $col)) {
                    $db->statement("ALTER TABLE `companies` ADD COLUMN `{$col}` {$ddl}");
                }
            }
            $companiesIndexes = collect($db->select('SHOW INDEX FROM `companies`'))->pluck('Key_name');
            if (!$companiesIndexes->contains('companies_b24_id_index')) {
                $db->statement('ALTER TABLE `companies` ADD INDEX `companies_b24_id_index` (`b24_id`)');
            }
        }

        $oldTypeIds = $db->table('data_types')
            ->where('name', 'contacts')->orWhere('slug', 'contacts')
            ->pluck('id');
        if ($oldTypeIds->isNotEmpty()) {
            $db->table('data_rows')->whereIn('data_type_id', $oldTypeIds)->delete();
            $db->table('data_types')->whereIn('id', $oldTypeIds)->delete();
        }
        $db->table('field_sections')->where('page', 'contacts')->delete();
        $db->table('settings')->where('entity', 'contacts')->where('type', 'menu')->delete();

        $now = now();

        $typeId = $db->table('data_types')->insertGetId([
            'name'                 => 'contacts',
            'slug'                 => 'contacts',
            'title_singular'       => 'Контакт',
            'title_plural'         => 'Контакты',
            'model_name'           => 'App\\Models\\Contact',
            'generate_permissions' => 1,
            'server_side'          => 0,
            'created_at'           => $now,
            'updated_at'           => $now,
            'color'                => '#4A90D9',
            'enable'               => 1,
            'slug_singular'        => 'contact',
            'hidden'               => 0,
        ]);

        $infoSecId = $db->table('field_sections')->insertGetId([
            'sort' => 0, 'name' => 'Информация', 'page' => 'contacts',
            'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
            'column_id' => 1, '_lft' => 0, '_rgt' => 0,
        ]);

        $base = [
            'data_type_id' => $typeId, 'type' => 'text', 'title' => '',
            'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $infoSecId, 'group_id' => null, 'sort' => 0,
            'button_name' => 'Загрузить', 'show_file_image' => 0, 'hide' => 0,
            'is_plural' => 0, 'roles_read' => '', 'roles_write' => '', 'is_remove' => 0,
            'mobile_pages' => '', 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0,
        ];

        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'id', 'type' => 'number', 'title' => 'ID', 'sort' => 0,
            'only_read' => 1, 'is_default' => 1, 'is_program' => 1,
        ]));
        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'created_at', 'type' => 'date', 'title' => 'Дата создания', 'sort' => 1,
            'only_read' => 1, 'is_default' => 1, 'mobile_pages' => '0',
        ]));
        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'updated_at', 'type' => 'date', 'title' => 'Дата изменения', 'sort' => 2,
            'only_read' => 1, 'is_default' => 1, 'mobile_pages' => '0',
        ]));
        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'name', 'type' => 'text', 'title' => 'ФИО', 'sort' => 3,
            'is_default' => 1, 'permanent_name' => 1,
        ]));
        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'phones', 'type' => 'multi_text', 'title' => 'Телефон', 'sort' => 4,
            'is_plural' => 1,
        ]));
        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'emails', 'type' => 'multi_text', 'title' => 'Email', 'sort' => 5,
            'is_plural' => 1,
        ]));
        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'company_id', 'type' => 'relation', 'title' => 'Компания', 'sort' => 6,
            'is_plural' => 1, 'details' => '{"table":"companies"}', 'is_link' => 1,
            'relation_table' => 'companies', 'related_field' => 'contact_id',
        ]));
        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'user_id', 'type' => 'relation', 'title' => 'Ответственный', 'sort' => 7,
            'details' => '{"table":"users"}', 'is_link' => 1,
            'relation_table' => 'users', 'related_field' => 'contact_id', 'is_inactive' => 1,
        ]));

        $db->table('settings')->insert([
            'key' => 'menu', 'display_name' => null,
            'value' => json_encode([
                ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                ['title' => 'История изменений', 'tab' => 'history', 'sort' => 1, 'enabled' => true, 'id' => 1, 'has_roles_read' => false, 'roles_read' => null],
            ], JSON_UNESCAPED_SLASHES),
            'type' => 'menu', 'entity' => 'contacts', 'user_id' => null,
        ]);

        $this->patchCompanies($db, $label);

        if ($db->getSchemaBuilder()->hasTable('sidebar_items')) {
            $existingItem = $db->table('sidebar_items')->where('slug', 'contacts')->first();
            if ($existingItem) {
                $db->table('sidebar_items')->where('slug', 'contacts')
                    ->update(['enabled' => 1, 'is_hidden' => 0, 'updated_at' => $now]);
            } else {
                $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
                $db->table('sidebar_items')->insert([
                    'created_at' => $now, 'updated_at' => $now,
                    'name' => 'Контакты', 'slug' => 'contacts',
                    'sort' => 0, 'link' => '/objects/contacts',
                    '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                    'is_hidden' => 0, 'enabled' => 1,
                ]);
            }
        }

        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }

        $this->line("    [{$label}] contacts: data_type={$typeId}, section={$infoSecId}");
    }

    /**
     * Скрытое поле «Контакт» у компаний: section_id=0 (несуществующая секция) —
     * поле не рендерится в карточке до одобрения. Для показа: выставить
     * section_id на реальную секцию компании.
     */
    private function patchCompanies($db, string $label): void
    {
        $companiesType = $db->table('data_types')
            ->where('slug', 'companies')->orWhere('name', 'companies')
            ->first();
        if (!$companiesType) {
            return;
        }

        $exists = $db->table('data_rows')
            ->where('data_type_id', $companiesType->id)
            ->where('field', 'contact_id')
            ->exists();
        if ($exists) {
            return;
        }

        $db->table('data_rows')->insert([
            'data_type_id' => $companiesType->id, 'field' => 'contact_id', 'type' => 'relation',
            'title' => 'Контакт', 'required' => 0, 'details' => '{"table":"contacts"}',
            'visible_always' => 1, 'label_color' => '', 'section_id' => 0, 'group_id' => null,
            'sort' => 90, 'button_name' => 'Загрузить', 'show_file_image' => 0, 'hide' => 0,
            'is_plural' => 1, 'roles_read' => '', 'roles_write' => '', 'is_remove' => 0,
            'mobile_pages' => '', 'only_read' => 0, 'is_permanent' => 0, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 1,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => 'contacts', 'related_field' => 'company_id', 'set_color' => 0,
            'is_unique' => 0, 'is_program' => 0,
        ]);
        $this->line("    [{$label}] companies: добавлено скрытое поле contact_id (section_id=0)");
    }
}
