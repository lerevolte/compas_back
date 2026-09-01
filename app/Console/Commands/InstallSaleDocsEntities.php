<?php

namespace App\Console\Commands;

use App\Models\ObjectRelation;
use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallSaleDocsEntities extends Command
{
    public const ENTITIES = [
        'payment_invoices' => [
            'title_singular' => 'Счет на оплату',
            'title_plural' => 'Счета на оплату',
            'model' => 'App\\Models\\PaymentInvoice',
            'slug_singular' => 'payment_invoice',
            'color' => '#B8860B',
            'columns' => [
                'number' => 'VARCHAR(64) NULL',
                'b24_id' => 'VARCHAR(32) NULL',
                'bank_requisite_id' => 'TEXT NULL',
            ],
            'indexes' => ['payment_invoices_b24_id_index' => 'b24_id'],
            'fields' => [
                'number' => ['type' => 'text', 'title' => 'Номер', 'after' => 'name'],
            ],
        ],
        'expense_invoices' => [
            'title_singular' => 'Расходная накладная',
            'title_plural' => 'Расходные накладные',
            'model' => 'App\\Models\\ExpenseInvoice',
            'slug_singular' => 'expense_invoice',
            'color' => '#8E5AA8',
        ],
        'product_returns' => [
            'title_singular' => 'Возврат',
            'title_plural' => 'Возвраты',
            'model' => 'App\\Models\\ProductReturn',
            'slug_singular' => 'product_return',
            'color' => '#C0392B',
            'columns' => [
                'reason' => 'TEXT NULL',
            ],
            'fields' => [
                'reason' => ['type' => 'text', 'title' => 'Причина возврата', 'is_plural' => 1],
            ],
        ],
    ];

    public const PRODUCTS_TAB_ENTITIES = ['payment_invoices', 'expense_invoices', 'product_returns'];

    public const PRINT_TAB = 'print_docs';
    public const PRINT_TAB_TITLE = 'Печать документов';
    public const PRINT_TAB_ENTITIES = ['logistic_tasks', 'pickups'];
    public const PRINT_TAB_REMOVE_FROM = ['deals'];

    public const BANK_FIELD = 'bank_requisite_id';
    public const BANK_FIELD_TITLE = 'Банковские реквизиты';
    public const BANK_FIELD_ENTITIES = ['payment_invoices'];

    public const VAT_FIELD = 'vat';
    public const VAT_TITLE = 'НДС';
    public const VAT_OPTIONS = [
        ['label' => 'Без НДС', 'value' => 'none'],
        ['label' => '0%', 'value' => '0'],
        ['label' => '5%', 'value' => '5'],
        ['label' => '7%', 'value' => '7'],
        ['label' => '10%', 'value' => '10'],
        ['label' => '20%', 'value' => '20'],
        ['label' => '22%', 'value' => '22'],
    ];

    public const DEAL_SUM_FIELD = 'sum';
    public const DEAL_SUM_TITLE = 'Сумма';

    public const SHIPMENT_FIELD = 'shipment_company_id';
    public const SHIPMENT_FIELD_TITLE = 'Компания отгрузки';
    public const SHIPMENT_FIELD_ENTITIES = ['deals', 'payment_invoices', 'expense_invoices', 'logistic_tasks'];

    protected $signature = 'entity:install-sale-docs
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Установить сущности «Счета на оплату», «Расходные накладные» и «Возвраты», переименовать deals в «Заказы покупателей», добавить вкладку «Печать документов», поле «Банковские реквизиты» у счетов и заказов и поле «Компания отгрузки» у заказов/счетов/накладных';

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

    private function installInto($db, string $label, bool $inTenant): void
    {
        $this->renameDeals($db, $label);

        foreach (self::ENTITIES as $slug => $meta) {
            $this->installEntity($db, $label, $slug, $meta);
        }

        $this->ensurePrintTab($db, $label);

        $this->ensureProductsTabs($db, $label);

        foreach (self::ensureBankRequisiteFields($db) as $line) {
            $this->line("    [{$label}] {$line}");
        }

        foreach (self::ensureShipmentCompanyFields($db) as $line) {
            $this->line("    [{$label}] {$line}");
        }

        foreach (self::ensureDealSumField($db) as $line) {
            $this->line("    [{$label}] {$line}");
        }

        foreach (self::ensureCompanyVatField($db) as $line) {
            $this->line("    [{$label}] {$line}");
        }

        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $urls = array_merge(['fields/deals'], array_map(fn ($s) => 'fields/' . $s, array_keys(self::ENTITIES)));
                $db->table('local_cache')->whereIn('url', $urls)->update(['updated_at' => now()]);
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

    private function renameDeals($db, string $label): void
    {
        $updated = $db->table('data_types')->where('slug', 'deals')->update([
            'title_singular' => 'Заказ покупателя',
            'title_plural' => 'Заказы покупателей',
        ]);
        if ($updated) {
            $db->table('sidebar_items')->where('slug', 'deals')->update(['name' => 'Заказы покупателей']);
            $this->line("    [{$label}] deals переименованы в «Заказы покупателей»");
        }
    }

    private function installEntity($db, string $label, string $slug, array $meta): void
    {
        $sb = $db->getSchemaBuilder();
        $now = now();

        $db->statement(<<<SQL
CREATE TABLE IF NOT EXISTS `{$slug}` (
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
  `company_id` text DEFAULT NULL,
  `sum` varchar(64) DEFAULT NULL,
  `products` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        foreach ($meta['columns'] ?? [] as $column => $definition) {
            if (!$sb->hasColumn($slug, $column)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `{$column}` {$definition}");
            }
        }
        foreach ($meta['indexes'] ?? [] as $index => $column) {
            $exists = collect($db->select("SHOW INDEX FROM `{$slug}`"))->pluck('Key_name')->contains($index);
            if (!$exists) {
                $db->statement("ALTER TABLE `{$slug}` ADD INDEX `{$index}` (`{$column}`)");
            }
        }

        $type = $db->table('data_types')->where('slug', $slug)->first();
        $typeAttrs = [
            'name' => $slug,
            'slug' => $slug,
            'title_singular' => $meta['title_singular'],
            'title_plural' => $meta['title_plural'],
            'model_name' => $meta['model'],
            'generate_permissions' => 1,
            'server_side' => 0,
            'updated_at' => $now,
            'color' => $meta['color'],
            'enable' => 1,
            'slug_singular' => $meta['slug_singular'],
            'hidden' => 0,
        ];
        if ($type) {
            $db->table('data_types')->where('id', $type->id)->update($typeAttrs);
            $typeId = (int) $type->id;
        } else {
            $typeId = (int) $db->table('data_types')->insertGetId($typeAttrs + ['created_at' => $now]);
        }

        $infoSecId = $db->table('field_sections')
            ->where('page', $slug)
            ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
            ->orderBy('sort')
            ->value('id');
        if (!$infoSecId) {
            $infoSecId = $db->table('field_sections')->insertGetId([
                'sort' => 0, 'name' => 'Информация', 'page' => $slug,
                'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
                'column_id' => 1, '_lft' => 0, '_rgt' => 0,
            ]);
        }

        $fields = [
            'id' => ['type' => 'number', 'title' => 'ID', 'only_read' => 1, 'is_default' => 1, 'is_program' => 1],
            'created_at' => ['type' => 'date', 'title' => 'Дата создания', 'only_read' => 1, 'is_default' => 1, 'mobile_pages' => '0'],
            'updated_at' => ['type' => 'date', 'title' => 'Дата изменения', 'only_read' => 1, 'is_default' => 1, 'mobile_pages' => '0'],
            'name' => ['type' => 'text', 'title' => 'Название', 'is_default' => 1, 'permanent_name' => 1],
            'photo' => ['type' => 'file', 'title' => 'Фото', 'show_file_name' => 1, 'is_default' => 1],
            'user_id' => ['type' => 'relation', 'title' => 'Ответственный', 'details' => '{"table":"users"}', 'is_link' => 1, 'required' => 1, 'relation_table' => 'users', 'is_inactive' => 1],
            'company_id' => ['type' => 'relation', 'title' => 'Компания', 'details' => '{"table":"companies"}', 'is_link' => 1, 'is_plural' => 1, 'relation_table' => 'companies'],
            'sum' => ['type' => 'number', 'title' => 'Сумма', 'unit' => 'руб.'],
        ];
        foreach ($meta['fields'] ?? [] as $field => $attrs) {
            $after = $attrs['after'] ?? null;
            unset($attrs['after']);
            if ($after && isset($fields[$after])) {
                $ordered = [];
                foreach ($fields as $key => $value) {
                    $ordered[$key] = $value;
                    if ($key === $after) {
                        $ordered[$field] = $attrs;
                    }
                }
                $fields = $ordered;
            } else {
                $fields[$field] = $attrs;
            }
        }

        $sort = 0;
        foreach ($fields as $field => $attrs) {
            $existing = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', $field)
                ->first();
            if ($existing) {
                $patch = array_intersect_key($attrs, array_flip(['title', 'details', 'relation_table', 'is_plural', 'only_read', 'unit']));
                $patch['is_remove'] = 0;
                $db->table('data_rows')->where('id', $existing->id)->update($patch);
                continue;
            }
            $db->table('data_rows')->insert(array_merge(self::baseRow($typeId, (int) $infoSecId), $attrs, [
                'field' => $field, 'sort' => $sort,
            ]));
            $sort++;
        }

        if (!$db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->exists()) {
            $db->table('settings')->insert([
                'key' => 'menu', 'display_name' => null,
                'value' => json_encode([
                    ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 1, 'enabled' => true, 'id' => 1, 'has_roles_read' => false, 'roles_read' => null],
                ], JSON_UNESCAPED_SLASHES),
                'type' => 'menu', 'entity' => $slug, 'user_id' => null,
            ]);
        }

        try {
            ObjectRelation::ensureTab($slug, $db);
        } catch (\Throwable $e) {
        }

        if ($sb->hasTable('sidebar_items')) {
            $existingItem = $db->table('sidebar_items')->where('slug', $slug)->first();
            if ($existingItem) {
                $db->table('sidebar_items')->where('slug', $slug)
                    ->update(['enabled' => 1, 'is_hidden' => 0, 'name' => $meta['title_plural'], 'updated_at' => $now]);
            } else {
                $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
                $db->table('sidebar_items')->insert([
                    'created_at' => $now, 'updated_at' => $now,
                    'name' => $meta['title_plural'], 'slug' => $slug,
                    'sort' => 0, 'link' => '/objects/' . $slug,
                    '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                    'is_hidden' => 0, 'enabled' => 1,
                ]);
            }
        }

        $this->line("    [{$label}] {$slug}: data_type={$typeId}");
    }

    private function ensureProductsTabs($db, string $label): void
    {
        foreach (self::PRODUCTS_TAB_ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId) {
                continue;
            }
            $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', 'products')->first();
            $attrs = ['type' => 'json', 'title' => 'Состав', 'only_read' => 1, 'is_permanent' => 1, 'is_remove' => 0, 'hide' => 0];
            if ($existing) {
                $db->table('data_rows')->where('id', $existing->id)->update($attrs);
            } else {
                $sectionId = (int) ($db->table('field_sections')
                    ->where('page', $slug)
                    ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                    ->orderBy('sort')
                    ->value('id') ?: 0);
                $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
                $db->table('data_rows')->insert(array_merge(self::baseRow((int) $typeId, $sectionId), $attrs, [
                    'field' => 'products', 'sort' => $maxSort + 1,
                ]));
                $this->line("    [{$label}] {$slug}: добавлено поле «Состав»");
            }

            $menus = $db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->get();
            foreach ($menus as $menu) {
                $tabs = json_decode($menu->value, true);
                if (!is_array($tabs) || collect($tabs)->contains(fn ($tab) => ($tab['tab'] ?? null) === 'products')) {
                    continue;
                }
                $maxId = 0;
                foreach ($tabs as $tab) {
                    $maxId = max($maxId, (int) ($tab['id'] ?? 0));
                }
                $ordered = [];
                $inserted = false;
                foreach ($tabs as $tab) {
                    $ordered[] = $tab;
                    if (!$inserted && ($tab['tab'] ?? null) === 'order') {
                        $ordered[] = ['title' => 'Товары и услуги', 'tab' => 'products', 'sort' => (int) ($tab['sort'] ?? 0) + 1, 'enabled' => 1, 'id' => $maxId + 1];
                        $inserted = true;
                    }
                }
                if (!$inserted) {
                    $ordered[] = ['title' => 'Товары и услуги', 'tab' => 'products', 'sort' => count($ordered), 'enabled' => 1, 'id' => $maxId + 1];
                }
                $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($ordered, JSON_UNESCAPED_SLASHES)]);
                $this->line("    [{$label}] {$slug}: добавлена вкладка «Товары и услуги»");
            }
        }
    }

    private function ensurePrintTab($db, string $label): void
    {
        foreach (self::PRINT_TAB_REMOVE_FROM as $entity) {
            foreach ($db->table('settings')->where(['type' => 'menu', 'entity' => $entity])->get() as $menu) {
                $tabs = json_decode($menu->value, true);
                if (!is_array($tabs) || !collect($tabs)->contains(fn ($tab) => ($tab['tab'] ?? null) === self::PRINT_TAB)) {
                    continue;
                }
                $tabs = array_values(array_filter($tabs, fn ($tab) => ($tab['tab'] ?? null) !== self::PRINT_TAB));
                $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($tabs, JSON_UNESCAPED_SLASHES)]);
                $this->line("    [{$label}] {$entity}: вкладка «" . self::PRINT_TAB_TITLE . "» убрана");
            }
        }

        foreach (self::PRINT_TAB_ENTITIES as $entity) {
            if (!$db->table('data_types')->where('slug', $entity)->exists()) {
                continue;
            }
            $this->ensurePrintTabFor($db, $label, $entity);
        }
    }

    private function ensurePrintTabFor($db, string $label, string $entity): void
    {
        $menus = $db->table('settings')->where(['type' => 'menu', 'entity' => $entity])->get();
        foreach ($menus as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs)) {
                continue;
            }
            if (collect($tabs)->contains(fn ($tab) => ($tab['tab'] ?? null) === self::PRINT_TAB)) {
                continue;
            }
            $maxSort = 0;
            $maxId = 0;
            foreach ($tabs as $tab) {
                $maxSort = max($maxSort, (int) ($tab['sort'] ?? 0));
                $maxId = max($maxId, (int) ($tab['id'] ?? 0));
            }
            $tabs[] = [
                'title' => self::PRINT_TAB_TITLE,
                'tab' => self::PRINT_TAB,
                'sort' => $maxSort + 1,
                'enabled' => 1,
                'id' => $maxId + 1,
                'has_roles_read' => false,
                'roles_read' => null,
            ];
            $db->table('settings')->where('id', $menu->id)->update([
                'value' => json_encode($tabs, JSON_UNESCAPED_SLASHES),
            ]);
            $this->line("    [{$label}] {$entity}: добавлена вкладка «" . self::PRINT_TAB_TITLE . "»");
        }
    }

    public static function ensureBankRequisiteFields($db): array
    {
        $sb = $db->getSchemaBuilder();
        $lines = [];

        $dealsTypeId = $db->table('data_types')->where('slug', 'deals')->value('id');
        if ($dealsTypeId) {
            $dealRows = $db->table('data_rows')->where('data_type_id', $dealsTypeId)->where('field', self::BANK_FIELD)->pluck('id');
            if ($dealRows->count()) {
                $db->table('section_fields_sort')->whereIn('field_id', $dealRows)->delete();
                $db->table('data_rows')->whereIn('id', $dealRows)->delete();
                $lines[] = 'deals: поле «' . self::BANK_FIELD_TITLE . '» удалено (не используется)';
            }
            $menus = $db->table('settings')->where(['type' => 'menu', 'entity' => 'deals'])->get();
            foreach ($menus as $menu) {
                $tabs = json_decode($menu->value, true);
                if (!is_array($tabs)) {
                    continue;
                }
                $kept = array_values(array_filter($tabs, fn ($tab) => ($tab['tab'] ?? null) !== self::BANK_FIELD));
                if (count($kept) !== count($tabs)) {
                    $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($kept, JSON_UNESCAPED_SLASHES)]);
                }
            }
        }

        if (!$db->table('data_types')->where('slug', 'bank_requisites')->exists() || !$sb->hasTable('bank_requisites')) {
            $lines[] = 'модуль «Банковские реквизиты» не установлен — поле «' . self::BANK_FIELD_TITLE . '» у счетов не добавлено';
            return $lines;
        }

        foreach (self::BANK_FIELD_ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId || !$sb->hasTable($slug)) {
                continue;
            }
            if (!$sb->hasColumn($slug, self::BANK_FIELD)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `" . self::BANK_FIELD . "` TEXT NULL");
            }

            $attrs = [
                'type' => 'relation', 'title' => self::BANK_FIELD_TITLE, 'details' => '{"table":"bank_requisites"}',
                'is_link' => 1, 'is_plural' => 1, 'relation_table' => 'bank_requisites', 'is_permanent' => 0,
                'is_remove' => 0, 'hide' => 0,
            ];
            $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::BANK_FIELD)->first();
            if ($existing) {
                $db->table('data_rows')->where('id', $existing->id)->update($attrs);
                continue;
            }

            $sectionId = (int) ($db->table('field_sections')
                ->where('page', $slug)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id') ?: 0);
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $db->table('data_rows')->insert(array_merge(self::baseRow((int) $typeId, $sectionId), $attrs, [
                'field' => self::BANK_FIELD, 'sort' => $maxSort + 1,
            ]));
            $lines[] = "{$slug}: добавлено поле «" . self::BANK_FIELD_TITLE . '»';
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')
                    ->whereIn('url', array_map(fn ($s) => 'fields/' . $s, self::BANK_FIELD_ENTITIES))
                    ->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }

        return $lines;
    }

    public static function ensureCompanyVatField($db): array
    {
        $sb = $db->getSchemaBuilder();
        $lines = [];
        $typeId = $db->table('data_types')->where('slug', 'companies')->value('id');
        if (!$typeId || !$sb->hasTable('companies')) {
            return $lines;
        }
        if (!$sb->hasColumn('companies', self::VAT_FIELD)) {
            $db->statement("ALTER TABLE `companies` ADD COLUMN `" . self::VAT_FIELD . "` VARCHAR(16) NULL");
        }

        $attrs = [
            'type' => 'select_dropdown', 'title' => self::VAT_TITLE,
            'details' => json_encode(['options' => self::VAT_OPTIONS], JSON_UNESCAPED_UNICODE),
            'is_remove' => 0, 'hide' => 0,
        ];
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::VAT_FIELD)->first();
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update($attrs);
            return $lines;
        }

        $kpp = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', 'kpp')->first();
        $sectionId = (int) ($kpp->section_id ?? 0);
        if (!$sectionId) {
            $sectionId = (int) ($db->table('field_sections')
                ->where('page', 'companies')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id') ?: 0);
        }
        $sort = $kpp ? (int) $kpp->sort + 1 : (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort') + 1;
        $vatId = $db->table('data_rows')->insertGetId(array_merge(self::baseRow((int) $typeId, $sectionId), $attrs, [
            'field' => self::VAT_FIELD, 'sort' => $sort, 'is_permanent' => 0,
            'group_id' => $kpp->group_id ?? null,
        ]));
        if (!empty($kpp->group_id)) {
            $group = $db->table('data_rows')->where('id', $kpp->group_id)->first();
            $subfields = json_decode((string) ($group->subfields ?? ''), true);
            if (is_array($subfields) && !in_array($vatId, $subfields)) {
                $subfields[] = $vatId;
                $db->table('data_rows')->where('id', $group->id)->update(['subfields' => json_encode($subfields)]);
            }
        }
        $lines[] = 'companies: добавлено поле «' . self::VAT_TITLE . '»';

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/companies')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }

        return $lines;
    }

    public static function ensureDealSumField($db): array
    {
        $sb = $db->getSchemaBuilder();
        $lines = [];
        $typeId = $db->table('data_types')->where('slug', 'deals')->value('id');
        if (!$typeId || !$sb->hasTable('deals')) {
            return $lines;
        }
        if (!$sb->hasColumn('deals', self::DEAL_SUM_FIELD)) {
            $db->statement("ALTER TABLE `deals` ADD COLUMN `" . self::DEAL_SUM_FIELD . "` VARCHAR(64) NULL");
        }

        $attrs = ['type' => 'number', 'title' => self::DEAL_SUM_TITLE, 'unit' => 'руб.', 'is_remove' => 0, 'hide' => 0];
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::DEAL_SUM_FIELD)->first();
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update($attrs);
        } else {
            $sectionId = (int) ($db->table('field_sections')
                ->where('page', 'deals')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id') ?: 0);
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $db->table('data_rows')->insert(array_merge(self::baseRow((int) $typeId, $sectionId), $attrs, [
                'field' => self::DEAL_SUM_FIELD, 'sort' => $maxSort + 1, 'is_permanent' => 0,
            ]));
            $lines[] = 'deals: добавлено поле «' . self::DEAL_SUM_TITLE . '»';
        }

        $filled = 0;
        $db->table('deals')
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereNull(self::DEAL_SUM_FIELD)->orWhere(self::DEAL_SUM_FIELD, ''))
            ->whereNotNull('products')
            ->orderBy('id')
            ->chunkById(500, function ($deals) use ($db, &$filled) {
                foreach ($deals as $deal) {
                    $products = json_decode((string) $deal->products, true);
                    if (!is_array($products)) {
                        continue;
                    }
                    $total = 0.0;
                    foreach ($products as $product) {
                        if (is_array($product)) {
                            $total += (float) ($product['count'] ?? 0) * (float) ($product['price'] ?? 0);
                        }
                    }
                    if ($total <= 0) {
                        continue;
                    }
                    $db->table('deals')->where('id', $deal->id)->update([
                        self::DEAL_SUM_FIELD => rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.'),
                    ]);
                    $filled++;
                }
            });
        if ($filled) {
            $lines[] = "deals: сумма заполнена из состава у {$filled} заказов";
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/deals')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }

        return $lines;
    }

    public static function ensureShipmentCompanyFields($db): array
    {
        $sb = $db->getSchemaBuilder();
        $lines = [];

        if (!$db->table('data_types')->where('slug', 'companies')->exists() || !$sb->hasTable('companies')) {
            $lines[] = 'сущность companies не найдена — поле «' . self::SHIPMENT_FIELD_TITLE . '» не добавлено';
            return $lines;
        }

        foreach (self::SHIPMENT_FIELD_ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId || !$sb->hasTable($slug)) {
                continue;
            }
            if (!$sb->hasColumn($slug, self::SHIPMENT_FIELD)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `" . self::SHIPMENT_FIELD . "` TEXT NULL");
            }

            $attrs = [
                'type' => 'relation', 'title' => self::SHIPMENT_FIELD_TITLE, 'details' => '{"table":"companies"}',
                'is_link' => 1, 'is_plural' => 0, 'relation_table' => 'companies', 'is_remove' => 0, 'hide' => 0,
            ];
            $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::SHIPMENT_FIELD)->first();
            if ($existing) {
                $db->table('data_rows')->where('id', $existing->id)->update(
                    array_intersect_key($attrs, array_flip(['type', 'title', 'details', 'relation_table', 'is_plural', 'is_remove', 'hide']))
                );
                continue;
            }

            $company = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', 'company_id')->first();
            $sectionId = (int) ($company->section_id ?? 0);
            if (!$sectionId) {
                $sectionId = (int) ($db->table('field_sections')
                    ->where('page', $slug)
                    ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                    ->orderBy('sort')
                    ->value('id') ?: 0);
            }
            $sort = $company
                ? (int) $company->sort + 1
                : (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort') + 1;
            $db->table('data_rows')->insert(array_merge(self::baseRow((int) $typeId, $sectionId), $attrs, [
                'field' => self::SHIPMENT_FIELD, 'sort' => $sort, 'is_permanent' => 0,
            ]));
            $lines[] = "{$slug}: добавлено поле «" . self::SHIPMENT_FIELD_TITLE . '»';
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')
                    ->whereIn('url', array_map(fn ($s) => 'fields/' . $s, self::SHIPMENT_FIELD_ENTITIES))
                    ->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }

        return $lines;
    }

    public static function removeBankRequisiteFields($db): void
    {
        foreach (self::BANK_FIELD_ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId) {
                continue;
            }
            $ids = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::BANK_FIELD)->pluck('id');
            if ($ids->isEmpty()) {
                continue;
            }
            $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
            $db->table('data_rows')->whereIn('id', $ids)->delete();
        }
    }

    public static function baseRow(int $typeId, int $sectionId): array
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
}
