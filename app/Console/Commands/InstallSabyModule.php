<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class InstallSabyModule extends Command
{
    protected $signature = 'saby:install
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Установить модуль «Транспортные накладные Saby»: таблицы saby_config/saby_waybills и поля маршрута, автопарка, сотрудника, товара';

    private const TARE_TYPES = [
        ['value' => 'XD', 'label' => 'XD — Мешок из полимерной плёнки'],
        ['value' => 'XB', 'label' => 'XB — Мешок бумажный'],
        ['value' => '8A', 'label' => '8A — Поддон деревянный'],
        ['value' => '4D', 'label' => '4D — Ящик деревянный'],
        ['value' => 'BX', 'label' => 'BX — Коробка'],
        ['value' => 'CT', 'label' => 'CT — Картонная коробка'],
        ['value' => 'BA', 'label' => 'BA — Бочка'],
        ['value' => 'CN', 'label' => 'CN — Контейнер'],
        ['value' => 'RO', 'label' => 'RO — Рулон'],
        ['value' => 'NE', 'label' => 'NE — Без упаковки'],
    ];

    private const OWNERSHIP_TYPES = [
        ['value' => '1', 'label' => 'Собственность'],
        ['value' => '2', 'label' => 'Совместная собственность супругов'],
        ['value' => '3', 'label' => 'Аренда'],
        ['value' => '4', 'label' => 'Лизинг'],
    ];

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds');
            $this->info('Готово: admin_seeds');
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

        $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function installInto(ConnectionInterface $db, string $label): void
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->line("    [{$label}] будут созданы таблицы saby_config, saby_waybills и поля модуля");
            return;
        }

        $this->ensureTables($db);

        $this->addField($db, 'routes', 'receiver_company_id', [
            'type' => 'relation',
            'title' => 'Получатель',
            'relation_table' => 'companies',
            'details' => json_encode(['table' => 'companies'], JSON_UNESCAPED_UNICODE),
        ], 'text');

        $this->addField($db, 'routes', 'request_number', [
            'type' => 'text',
            'title' => 'Номер заявки',
        ], 'text');

        $this->addField($db, 'routes', 'request_date', [
            'type' => 'date',
            'title' => 'Дата заявки',
        ], 'text');

        $this->addField($db, 'routes', 'saby_waybills', [
            'type' => 'waybills',
            'title' => 'Транспортные накладные',
            'only_read' => 1,
            'visible_always' => 1,
        ], 'text');

        $this->addField($db, 'companies', 'inn', [
            'type' => 'text',
            'title' => 'ИНН',
        ], 'varchar(20)');

        $this->addField($db, 'companies', 'kpp', [
            'type' => 'text',
            'title' => 'КПП',
        ], 'varchar(20)');

        $this->addField($db, 'companies', 'address', [
            'type' => 'text',
            'title' => 'Юридический адрес',
        ], 'text');

        $this->addField($db, 'cars', 'number', [
            'type' => 'text',
            'title' => 'Гос. номер',
        ], 'varchar(32)');

        $this->addField($db, 'cars', 'ownership_type', [
            'type' => 'select_dropdown',
            'title' => 'Тип владения ТС',
            'details' => json_encode(['options' => self::OWNERSHIP_TYPES], JSON_UNESCAPED_UNICODE),
        ], 'text');

        $this->addField($db, 'employees', 'inn', [
            'type' => 'text',
            'title' => 'ИНН',
        ], 'text');

        $this->addField($db, 'products', 'packing_method', [
            'type' => 'text',
            'title' => 'Способ упаковки',
        ], 'text');

        $this->addField($db, 'products', 'tare_type', [
            'type' => 'select_dropdown',
            'title' => 'Вид тары',
            'details' => json_encode(['options' => self::TARE_TYPES], JSON_UNESCAPED_UNICODE),
        ], 'text');

        $this->clearCache();

        $this->line("    [{$label}] модуль установлен");
    }

    private function ensureTables(ConnectionInterface $db): void
    {
        $sb = $db->getSchemaBuilder();

        if (!$sb->hasTable('saby_config')) {
            $db->statement("
                CREATE TABLE `saby_config` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                    `login` varchar(255) DEFAULT NULL,
                    `password` varchar(255) DEFAULT NULL,
                    `config` text,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        if (!$sb->hasTable('saby_waybills')) {
            $db->statement("
                CREATE TABLE `saby_waybills` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                    `route_id` bigint unsigned DEFAULT NULL,
                    `doc_id` varchar(64) DEFAULT NULL,
                    `attachment_id` varchar(64) DEFAULT NULL,
                    `number` varchar(64) DEFAULT NULL,
                    `date` varchar(32) DEFAULT NULL,
                    `status` varchar(255) DEFAULT NULL,
                    `pdf_url` text,
                    `cabinet_url` text,
                    `archive_url` text,
                    `qr_url` text,
                    `payload` longtext,
                    `error` text,
                    `user_id` bigint unsigned DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `saby_waybills_route_id_index` (`route_id`),
                    KEY `saby_waybills_doc_id_index` (`doc_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        if (!$sb->hasColumn('saby_waybills', 'qr_url')) {
            $db->statement("ALTER TABLE `saby_waybills` ADD COLUMN `qr_url` TEXT NULL");
        }
    }

    private function addField(ConnectionInterface $db, string $entity, string $field, array $attrs, string $columnType): void
    {
        $sb = $db->getSchemaBuilder();

        $dataType = $db->table('data_types')->where('slug', $entity)->first();
        if (!$dataType || !$sb->hasTable($entity)) {
            $this->warn("      сущность {$entity} не найдена, поле {$field} пропущено");
            return;
        }

        if (!$sb->hasColumn($entity, $field)) {
            $db->statement("ALTER TABLE `{$entity}` ADD COLUMN `{$field}` {$columnType} NULL");
        }

        $existing = $db->table('data_rows')
            ->where('data_type_id', $dataType->id)
            ->where('field', $field)
            ->first();

        if ($existing) {
            $patch = array_intersect_key($attrs, array_flip(['type', 'title', 'relation_table', 'details', 'only_read', 'visible_always']));
            if (count($patch)) {
                $db->table('data_rows')->where('id', $existing->id)->update($patch);
            }
            $this->line("      {$entity}.{$field} обновлено (id {$existing->id})");
            return;
        }

        $sectionId = $db->table('field_sections')
            ->where('page', $entity)
            ->whereNull('module')
            ->orderBy('sort')
            ->value('id');

        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $dataType->id)->max('sort');

        $row = $this->baseRow($dataType->id, $sectionId);
        $row['field'] = $field;
        $row['sort'] = $maxSort + 1;
        $row = array_merge($row, $attrs);

        $id = $db->table('data_rows')->insertGetId($row);

        $this->line("      создано поле {$entity}.{$field} (id {$id})");
    }

    private function baseRow(int $typeId, $sectionId): array
    {
        return [
            'data_type_id' => $typeId, 'field' => null, 'type' => 'text', 'title' => '',
            'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $sectionId, 'group_id' => null, 'sort' => 0,
            'created_at' => null, 'updated_at' => null, 'button_name' => 'Загрузить',
            'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0, 'roles_read' => '',
            'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
            'rules' => null, 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'options' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null,
        ];
    }

    private function clearCache(): void
    {
        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
