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

    public const MODULE_SLUG = 'saby';
    public const MODULE_NAME = 'Транспортные накладные';

    private const MODULE_FIELDS = [
        'logistic_tasks' => ['saby_waybills', 'company_id', 'contact_id', 'employee_id', 'address', 'products', 'delivery_date'],
        'routes' => ['company_id', 'car_id'],
        'companies' => ['name', 'inn', 'kpp', 'address'],
        'employees' => ['name', 'phone', 'inn'],
        'cars' => ['name', 'number', 'ownership_type', 'osago_mark', 'osago_model', 'weight_max', 'volume_max'],
        'products' => ['name', 'packing_method', 'tare_type', 'weight', 'volume'],
    ];

    private const OBSOLETE_FIELDS = [
        'routes' => ['receiver_company_id', 'request_number', 'request_date', 'saby_waybills'],
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

        $this->removeObsoleteFields($db);

        $this->addField($db, 'logistic_tasks', 'company_id', [
            'type' => 'relation',
            'title' => 'Компания',
            'relation_table' => 'companies',
            'details' => json_encode(['table' => 'companies'], JSON_UNESCAPED_UNICODE),
        ], 'text');

        $this->addField($db, 'logistic_tasks', 'contact_id', [
            'type' => 'relation',
            'title' => 'Контакт',
            'relation_table' => 'contacts',
            'details' => json_encode(['table' => 'contacts'], JSON_UNESCAPED_UNICODE),
            'is_plural' => 1,
        ], 'text');

        $this->addField($db, 'logistic_tasks', 'saby_waybills', [
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

        $this->installModuleTab($db, $label);

        $this->clearCache();

        $this->line("    [{$label}] модуль установлен");
    }

    private function removeObsoleteFields(ConnectionInterface $db): void
    {
        foreach (self::OBSOLETE_FIELDS as $entity => $fields) {
            $typeId = $db->table('data_types')->where('slug', $entity)->value('id');
            if (!$typeId) {
                continue;
            }

            $rows = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->whereIn('field', $fields)
                ->get(['id', 'field']);

            if ($rows->isEmpty()) {
                continue;
            }

            $ids = $rows->pluck('id')->all();
            $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
            $db->table('data_rows')->whereIn('id', $ids)->delete();

            $this->line("      удалены устаревшие поля {$entity}: " . $rows->pluck('field')->implode(', '));
        }
    }

    private function installModuleTab(ConnectionInterface $db, string $label): void
    {
        $now = now();

        if (!$db->table('modules')->where('slug', self::MODULE_SLUG)->exists()) {
            $db->table('modules')->insert([
                'name' => self::MODULE_NAME,
                'config' => '',
                'entities' => '',
                'slug' => self::MODULE_SLUG,
                'enabled' => 1,
            ]);
            $this->line("      добавлена запись модуля " . self::MODULE_SLUG . " в modules");
        }

        foreach (self::MODULE_FIELDS as $entity => $fields) {
            $typeId = $db->table('data_types')->where('slug', $entity)->value('id');
            if (!$typeId) {
                continue;
            }

            $sectionId = $db->table('field_sections')
                ->where('page', $entity)
                ->where('module', self::MODULE_SLUG)
                ->value('id');

            if (!$sectionId) {
                $sectionId = $db->table('field_sections')->insertGetId([
                    'sort' => 0, 'name' => 'Используемые поля в модуле', 'domain_key' => null, 'page' => $entity,
                    'created_at' => $now, 'updated_at' => $now, 'account_id' => null, 'hide' => 0,
                    'column_id' => 1, 'module' => self::MODULE_SLUG, 'parent_id' => null, '_lft' => 0, '_rgt' => 0, 'is_short' => null,
                ]);
            }

            $names = $fields;
            if ($entity === 'companies') {
                $names = array_merge($names, $this->phoneFields($db, $typeId));
            }

            $rows = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('is_remove', 0)
                ->whereIn('field', $names)
                ->get(['id', 'field', 'module', 'module_section_id']);

            $ordered = [];
            foreach ($names as $name) {
                $row = $rows->firstWhere('field', $name);
                if (!$row) {
                    continue;
                }
                $ordered[] = $row->id;
                $this->attachField($db, $row, (int) $sectionId);
            }

            $this->syncSectionSort($db, (int) $sectionId, $ordered);
            $this->syncMenu($db, $entity);

            $db->table('local_cache')->where('url', "fields/{$entity}")->update(['updated_at' => $now]);

            $this->line("      [{$entity}] секция модуля #{$sectionId}, полей: " . count($ordered));
        }
    }

    private function phoneFields(ConnectionInterface $db, int $typeId): array
    {
        return $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('is_remove', 0)
            ->where('title', 'LIKE', '%елефон%')
            ->pluck('field')
            ->all();
    }

    private function attachField(ConnectionInterface $db, $row, int $sectionId): void
    {
        $modules = $this->decodeJsonList($row->module);
        $sections = array_map('intval', $this->decodeJsonList($row->module_section_id));

        $changed = false;
        if (!in_array(self::MODULE_SLUG, $modules, true)) {
            $modules[] = self::MODULE_SLUG;
            $changed = true;
        }
        if (!in_array($sectionId, $sections, true)) {
            $sections[] = $sectionId;
            $changed = true;
        }

        if ($changed) {
            $db->table('data_rows')->where('id', $row->id)->update([
                'module' => json_encode(array_values($modules)),
                'module_section_id' => json_encode(array_values($sections)),
            ]);
        }
    }

    private function syncSectionSort(ConnectionInterface $db, int $sectionId, array $ordered): void
    {
        $db->table('section_fields_sort')->where('section_id', $sectionId)->delete();

        if (!$ordered) {
            return;
        }

        $insert = [];
        foreach ($ordered as $i => $fieldId) {
            $insert[] = ['section_id' => $sectionId, 'field_id' => $fieldId, 'sort' => $i];
        }
        $db->table('section_fields_sort')->insert($insert);
    }

    private function syncMenu(ConnectionInterface $db, string $entity): void
    {
        $child = ['title' => self::MODULE_NAME, 'sort' => 2, 'enabled' => 1, 'id' => 0, 'alias' => self::MODULE_SLUG];

        $menus = $db->table('settings')->where(['type' => 'menu', 'entity' => $entity])->get();

        if ($menus->isEmpty()) {
            $db->table('settings')->insert([
                'key' => 'menu', 'display_name' => null,
                'value' => json_encode([
                    ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                    [
                        'title' => 'Модули', 'tab' => 'modules', 'sort' => 1, 'enabled' => 1, 'id' => 1,
                        'childs' => [$child],
                        'component' => ['name' => 'AsyncComponentWrapper'],
                        'roles_read' => [], 'has_roles_read' => false,
                    ],
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 3, 'enabled' => true, 'id' => 3, 'has_roles_read' => false, 'roles_read' => null],
                ], JSON_UNESCAPED_SLASHES),
                'type' => 'menu', 'entity' => $entity, 'user_id' => null,
            ]);
            return;
        }

        foreach ($menus as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs)) {
                continue;
            }

            $modulesKey = null;
            foreach ($tabs as $k => $tab) {
                if (($tab['tab'] ?? null) === 'modules') {
                    $modulesKey = $k;
                    break;
                }
            }

            if ($modulesKey === null) {
                $maxSort = 0;
                $maxId = 0;
                foreach ($tabs as $tab) {
                    $maxSort = max($maxSort, (int) ($tab['sort'] ?? 0));
                    $maxId = max($maxId, (int) ($tab['id'] ?? 0));
                }
                $tabs[] = [
                    'title' => 'Модули', 'tab' => 'modules', 'sort' => $maxSort + 1, 'enabled' => 1, 'id' => $maxId + 1,
                    'childs' => [$child],
                    'component' => ['name' => 'AsyncComponentWrapper'],
                    'roles_read' => [], 'has_roles_read' => false,
                ];
            } else {
                $tabs[$modulesKey]['enabled'] = 1;
                $childs = $tabs[$modulesKey]['childs'] ?? [];
                $exists = false;
                foreach ($childs as $ck => $item) {
                    if (($item['alias'] ?? null) === self::MODULE_SLUG) {
                        $childs[$ck]['enabled'] = 1;
                        $childs[$ck]['title'] = self::MODULE_NAME;
                        $exists = true;
                    }
                }
                if (!$exists) {
                    $childs[] = $child;
                }
                $tabs[$modulesKey]['childs'] = array_values($childs);
            }

            $db->table('settings')->where('id', $menu->id)->update([
                'value' => json_encode($tabs, JSON_UNESCAPED_SLASHES),
            ]);
        }
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

        if (!$sb->hasColumn('saby_waybills', 'task_id')) {
            $db->statement("ALTER TABLE `saby_waybills` ADD COLUMN `task_id` BIGINT UNSIGNED NULL");
            $db->statement("ALTER TABLE `saby_waybills` ADD INDEX `saby_waybills_task_id_index` (`task_id`)");
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
