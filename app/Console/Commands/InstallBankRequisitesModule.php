<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class InstallBankRequisitesModule extends Command
{
    public const MODULE_SLUG = 'bank_requisites';
    public const MODULE_NAME = 'Банковские реквизиты';
    public const ENTITY = 'bank_requisites';
    public const GROUP_TITLE = 'Реквизиты';

    public const COMPANY_FIELDS = [
        'inn'               => ['title' => 'ИНН', 'mask' => '############', 'column' => 'varchar(20)'],
        'full_name'         => ['title' => 'Полное наименование организации', 'column' => 'text'],
        'ogrn'              => ['title' => 'ОГРН', 'mask' => '###############', 'column' => 'varchar(20)'],
        'kpp'               => ['title' => 'КПП', 'mask' => '#########', 'column' => 'varchar(20)'],
        'registration_date' => ['title' => 'Дата государственной регистрации', 'type' => 'date', 'column' => 'varchar(32)'],
        'okpo'              => ['title' => 'ОКПО', 'mask' => '##########', 'column' => 'varchar(20)'],
        'oktmo'             => ['title' => 'ОКТМО', 'mask' => '###########', 'column' => 'varchar(20)'],
        'director'          => ['title' => 'Ген. директор', 'column' => 'text'],
        'accountant'        => ['title' => 'Гл. бухгалтер', 'column' => 'text'],
        'fact_address'      => ['title' => 'Фактический адрес', 'column' => 'text'],
        'address'           => ['title' => 'Юридический адрес', 'column' => 'text'],
    ];

    public const ENTITY_FIELDS = [
        'company_id', 'bank_name', 'bic', 'account', 'corr_account',
        'currency', 'bank_address', 'swift', 'comment', 'is_default',
    ];

    protected $signature = 'bank-requisites:install
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Установить модуль «Банковские реквизиты»: сущность bank_requisites, поле привязки и группа «Реквизиты» у компаний, вкладка «Модули»';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id, true));
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
        $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $target, true));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function installInto(ConnectionInterface $db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();

        $companiesType = $db->table('data_types')->where('slug', 'companies')->first();
        if (!$companiesType || !$sb->hasTable('companies')) {
            $this->warn("    [{$label}] сущность companies не найдена, пропуск");
            return;
        }

        $this->ensureTables($db);
        $typeId = $this->ensureEntity($db, $label);
        $this->patchCompanies($db, $label, (int) $companiesType->id);
        $this->installModuleTab($db, $label);
        $this->clearCache($db, $inTenant);

        $this->line("    [{$label}] bank_requisites: data_type={$typeId}");
    }

    private function ensureTables(ConnectionInterface $db): void
    {
        $sb = $db->getSchemaBuilder();

        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `bank_requisites` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `choosed_at` timestamp NULL DEFAULT NULL,
  `name` text DEFAULT NULL,
  `photo` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `color` varchar(191) DEFAULT '',
  `company_id` int(11) DEFAULT NULL,
  `bank_name` text DEFAULT NULL,
  `bic` varchar(20) DEFAULT NULL,
  `account` varchar(40) DEFAULT NULL,
  `corr_account` varchar(40) DEFAULT NULL,
  `currency` varchar(16) DEFAULT NULL,
  `bank_address` text DEFAULT NULL,
  `swift` varchar(20) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_default` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_requisites_company_id_index` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        if (!$sb->hasColumn('companies', 'bank_requisite_id')) {
            $db->statement('ALTER TABLE `companies` ADD COLUMN `bank_requisite_id` TEXT NULL');
        }
        foreach (self::COMPANY_FIELDS as $field => $def) {
            if (!$sb->hasColumn('companies', $field)) {
                $db->statement("ALTER TABLE `companies` ADD COLUMN `{$field}` {$def['column']} NULL");
            }
        }
    }

    private function ensureEntity(ConnectionInterface $db, string $label): int
    {
        $now = now();

        $type = $db->table('data_types')->where('slug', self::ENTITY)->first();
        $typeAttrs = [
            'name' => self::ENTITY,
            'slug' => self::ENTITY,
            'title_singular' => 'Банковские реквизиты',
            'title_plural' => 'Банковские реквизиты',
            'model_name' => 'App\\Models\\BankRequisite',
            'generate_permissions' => 1,
            'server_side' => 0,
            'updated_at' => $now,
            'color' => '#2E7D6B',
            'enable' => 1,
            'slug_singular' => 'bank_requisite',
            'hidden' => 0,
        ];
        if ($type) {
            $db->table('data_types')->where('id', $type->id)->update($typeAttrs);
            $typeId = (int) $type->id;
        } else {
            $typeId = (int) $db->table('data_types')->insertGetId($typeAttrs + ['created_at' => $now]);
        }

        $infoSecId = $db->table('field_sections')
            ->where('page', self::ENTITY)
            ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
            ->orderBy('sort')
            ->value('id');
        if (!$infoSecId) {
            $infoSecId = $db->table('field_sections')->insertGetId([
                'sort' => 0, 'name' => 'Информация', 'page' => self::ENTITY,
                'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
                'column_id' => 1, '_lft' => 0, '_rgt' => 0,
            ]);
        }

        $fields = [
            'id' => ['type' => 'number', 'title' => 'ID', 'only_read' => 1, 'is_default' => 1, 'is_program' => 1],
            'created_at' => ['type' => 'date', 'title' => 'Дата создания', 'only_read' => 1, 'is_default' => 1, 'mobile_pages' => '0'],
            'updated_at' => ['type' => 'date', 'title' => 'Дата изменения', 'only_read' => 1, 'is_default' => 1, 'mobile_pages' => '0'],
            'name' => ['type' => 'text', 'title' => 'Название', 'is_default' => 1, 'permanent_name' => 1, 'required' => 1],
            'photo' => ['type' => 'file', 'title' => 'Фото', 'show_file_image' => 1, 'is_default' => 1],
            'user_id' => ['type' => 'relation', 'title' => 'Ответственный', 'details' => '{"table":"users"}', 'is_link' => 1, 'required' => 1, 'relation_table' => 'users', 'is_inactive' => 1],
            'company_id' => ['type' => 'relation', 'title' => 'Компания', 'details' => '{"table":"companies"}', 'is_link' => 1, 'relation_table' => 'companies', 'related_field' => 'bank_requisite_id'],
            'bank_name' => ['type' => 'text', 'title' => 'Наименование банка', 'details' => '{"suggest":"bank"}'],
            'bic' => ['type' => 'text', 'title' => 'БИК', 'details' => '{"suggest":"bank"}', 'mask' => '#########'],
            'account' => ['type' => 'text', 'title' => 'Расчетный счет', 'mask' => '####################'],
            'corr_account' => ['type' => 'text', 'title' => 'Кор. счет', 'mask' => '####################'],
            'currency' => ['type' => 'select_dropdown', 'title' => 'Валюта счета', 'default_value' => 'RUB', 'set_default' => 1, 'details' => json_encode(['options' => [
                ['label' => 'RUB — Российский рубль', 'value' => 'RUB'],
                ['label' => 'USD — Доллар США', 'value' => 'USD'],
                ['label' => 'EUR — Евро', 'value' => 'EUR'],
                ['label' => 'CNY — Китайский юань', 'value' => 'CNY'],
                ['label' => 'KZT — Казахстанский тенге', 'value' => 'KZT'],
                ['label' => 'BYN — Белорусский рубль', 'value' => 'BYN'],
            ]], JSON_UNESCAPED_UNICODE)],
            'bank_address' => ['type' => 'text', 'title' => 'Адрес банка'],
            'swift' => ['type' => 'text', 'title' => 'SWIFT', 'mask' => '***********'],
            'comment' => ['type' => 'text', 'title' => 'Комментарий', 'is_plural' => 1],
            'is_default' => ['type' => 'select_dropdown', 'title' => 'По умолчанию', 'details' => json_encode(['options' => [
                ['label' => 'Да', 'value' => 1],
                ['label' => 'Нет', 'value' => 0],
            ]], JSON_UNESCAPED_UNICODE)],
        ];

        $sort = 0;
        foreach ($fields as $field => $attrs) {
            $this->upsertField($db, $typeId, (int) $infoSecId, $field, $attrs, $sort++);
        }

        $this->ensureMenu($db, self::ENTITY, [
            ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
            ['title' => 'История изменений', 'tab' => 'history', 'sort' => 1, 'enabled' => true, 'id' => 1, 'has_roles_read' => false, 'roles_read' => null],
        ]);

        if ($db->getSchemaBuilder()->hasTable('sidebar_items')) {
            $existingItem = $db->table('sidebar_items')->where('slug', self::ENTITY)->first();
            if ($existingItem) {
                $db->table('sidebar_items')->where('slug', self::ENTITY)
                    ->update(['enabled' => 1, 'is_hidden' => 0, 'updated_at' => $now]);
            } else {
                $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
                $db->table('sidebar_items')->insert([
                    'created_at' => $now, 'updated_at' => $now,
                    'name' => self::MODULE_NAME, 'slug' => self::ENTITY,
                    'sort' => 0, 'link' => '/objects/' . self::ENTITY,
                    '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                    'is_hidden' => 0, 'enabled' => 1,
                ]);
            }
        }

        return $typeId;
    }

    private function patchCompanies(ConnectionInterface $db, string $label, int $typeId): void
    {
        $sectionId = (int) ($db->table('field_sections')
            ->where('page', 'companies')->where('name', 'Прикрепленные сущности')
            ->value('id')
            ?: $db->table('field_sections')
                ->where('page', 'companies')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id')
            ?: 0);

        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');

        $this->upsertField($db, $typeId, $sectionId, 'bank_requisite_id', [
            'type' => 'relation', 'title' => self::MODULE_NAME, 'details' => '{"table":"bank_requisites"}',
            'is_plural' => 1, 'is_link' => 1, 'relation_table' => 'bank_requisites', 'related_field' => 'company_id',
            'is_permanent' => 0,
        ], $maxSort + 1, false);

        $infoSecId = (int) ($db->table('field_sections')
            ->where('page', 'companies')
            ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
            ->orderBy('sort')
            ->value('id') ?: 0);

        $group = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'text_group')
            ->where('is_remove', 0)
            ->where('title', self::GROUP_TITLE)
            ->first();
        if (!$group) {
            $groupId = $db->table('data_rows')->insertGetId(array_merge($this->baseRow($typeId, $infoSecId), [
                'field' => 'requisites', 'type' => 'text_group', 'title' => self::GROUP_TITLE,
                'sort' => $maxSort + 2, 'hide' => $infoSecId ? 0 : 1, 'is_permanent' => 0,
            ]));
            $db->table('data_rows')->where('id', $groupId)->update(['field' => 'requisites_' . $groupId]);
            $group = $db->table('data_rows')->where('id', $groupId)->first();
            $this->line("    [{$label}] companies: создана группа «" . self::GROUP_TITLE . "» (id {$groupId})");
        }

        $subfields = [];
        $sort = 0;
        foreach (self::COMPANY_FIELDS as $field => $def) {
            $attrs = ['type' => $def['type'] ?? 'text', 'title' => $def['title'], 'is_permanent' => 0];
            if (isset($def['mask'])) {
                $attrs['mask'] = $def['mask'];
            }
            $existing = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', $field)
                ->first();
            if ($existing) {
                $patch = ['title' => $def['title']];
                if (!$existing->group_id) {
                    $patch['group_id'] = $group->id;
                    $patch['sort'] = $sort;
                }
                if (isset($def['mask']) && !$existing->mask) {
                    $patch['mask'] = $def['mask'];
                }
                $db->table('data_rows')->where('id', $existing->id)->update($patch);
                $id = (int) $existing->id;
            } else {
                $id = (int) $db->table('data_rows')->insertGetId(array_merge($this->baseRow($typeId, (int) $group->section_id), $attrs, [
                    'field' => $field, 'group_id' => $group->id, 'sort' => $sort,
                ]));
                $this->line("    [{$label}] companies: создано поле {$field} (id {$id})");
            }
            $subfields[] = $id;
            $sort++;
        }

        $db->table('data_rows')->where('id', $group->id)->update(['subfields' => json_encode($subfields)]);
    }

    private function upsertField(ConnectionInterface $db, int $typeId, int $sectionId, string $field, array $attrs, int $sort, bool $patchType = true): int
    {
        $existing = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', $field)
            ->first();

        if ($existing) {
            $keys = ['title', 'details', 'relation_table', 'related_field', 'mask', 'is_plural', 'only_read'];
            if ($patchType) {
                $keys[] = 'type';
            }
            $patch = array_intersect_key($attrs, array_flip($keys));
            $patch['is_remove'] = 0;
            if ((int) $existing->section_id === 0 && $sectionId) {
                $patch['section_id'] = $sectionId;
                $patch['hide'] = 0;
            }
            $db->table('data_rows')->where('id', $existing->id)->update($patch);
            return (int) $existing->id;
        }

        return (int) $db->table('data_rows')->insertGetId(array_merge($this->baseRow($typeId, $sectionId), $attrs, [
            'field' => $field, 'sort' => $sort, 'hide' => $sectionId ? 0 : 1,
        ]));
    }

    private function baseRow(int $typeId, int $sectionId): array
    {
        return [
            'data_type_id' => $typeId, 'field' => null, 'type' => 'text', 'title' => '',
            'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $sectionId ?: null, 'group_id' => null, 'sort' => 0,
            'button_name' => 'Загрузить', 'show_file_image' => 0, 'hide' => 0,
            'is_plural' => 0, 'roles_read' => '', 'roles_write' => '', 'is_remove' => 0,
            'mobile_pages' => '', 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0,
        ];
    }

    private function ensureMenu(ConnectionInterface $db, string $entity, array $tabs): void
    {
        if ($db->table('settings')->where(['type' => 'menu', 'entity' => $entity])->exists()) {
            return;
        }
        $db->table('settings')->insert([
            'key' => 'menu', 'display_name' => null,
            'value' => json_encode($tabs, JSON_UNESCAPED_SLASHES),
            'type' => 'menu', 'entity' => $entity, 'user_id' => null,
        ]);
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
            $this->line("    [{$label}] добавлена запись модуля " . self::MODULE_SLUG . " в modules");
        }

        $moduleFields = [
            'companies' => array_merge(['bank_requisite_id'], array_keys(self::COMPANY_FIELDS)),
            self::ENTITY => self::ENTITY_FIELDS,
        ];

        foreach ($moduleFields as $entity => $names) {
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

            $db->table('section_fields_sort')->where('section_id', $sectionId)->delete();
            $insert = [];
            foreach ($ordered as $i => $fieldId) {
                $insert[] = ['section_id' => $sectionId, 'field_id' => $fieldId, 'sort' => $i];
            }
            if ($insert) {
                $db->table('section_fields_sort')->insert($insert);
            }

            $this->syncMenu($db, $entity);

            $this->line("    [{$label}] [{$entity}] секция модуля #{$sectionId}, полей: " . count($ordered));
        }
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

    private function clearCache(ConnectionInterface $db, bool $inTenant): void
    {
        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->whereIn('url', ['fields/companies', 'fields/' . self::ENTITY])->update(['updated_at' => now()]);
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
