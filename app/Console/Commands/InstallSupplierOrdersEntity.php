<?php

namespace App\Console\Commands;

use App\Models\ObjectRelation;
use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallSupplierOrdersEntity extends Command
{
    protected $signature = 'entity:install-supplier-orders
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Модуль «Заказы поставщикам»: сущность supplier_orders и поле «Заказы поставщикам» с вкладкой у товаров. Ставится точечно (по умолчанию avixo), не на все порталы. После установки — relations:install-module <tenant>';

    public const SLUG = 'supplier_orders';
    public const MODEL = 'App\\Models\\SupplierOrder';
    public const TITLE_SINGULAR = 'Заказ поставщику';
    public const TITLE_PLURAL = 'Заказы поставщикам';
    public const COLOR = '#3F7FBF';

    public const PRODUCT_FIELD = 'supplier_order_id';
    public const COMPANY_FIELD = 'supplier_order_id';
    public const COMPANY_FIELD_TITLE = 'Заказы поставщикам';
    public const PRODUCT_FIELD_TITLE = 'Заказы поставщикам';
    public const RECEIVER_FIELD = 'shipment_company_id';
    public const RECEIVER_FIELD_TITLE = 'Компания получатель';
    public const RECEIVER_FIELD_OLD_TITLES = ['Компания отгрузки'];

    public function handle(): int
    {
        $target = (string) $this->argument('target');

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
        $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id, true));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function installInto($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        $now = now();
        $slug = self::SLUG;

        $db->statement(<<<SQL
CREATE TABLE IF NOT EXISTS `{$slug}` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `choosed_at` timestamp NULL DEFAULT NULL,
  `name` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `photo` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `color` varchar(191) DEFAULT '',
  `company_id` text DEFAULT NULL,
  `contact_id` text DEFAULT NULL,
  `sum` varchar(64) DEFAULT NULL,
  `payment` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `weight` varchar(64) DEFAULT NULL,
  `volume` varchar(64) DEFAULT NULL,
  `products` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        foreach (self::TASK_COLUMNS as $column => $ddl) {
            if (!$sb->hasColumn($slug, $column)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `{$column}` {$ddl}");
            }
        }

        $type = $db->table('data_types')->where('slug', $slug)->first();
        $typeAttrs = [
            'name' => $slug,
            'slug' => $slug,
            'title_singular' => self::TITLE_SINGULAR,
            'title_plural' => self::TITLE_PLURAL,
            'model_name' => self::MODEL,
            'generate_permissions' => 1,
            'server_side' => 0,
            'updated_at' => $now,
            'color' => self::COLOR,
            'enable' => 1,
            'slug_singular' => 'supplier_order',
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

        $hasContacts = $db->table('data_types')->where('slug', 'contacts')->exists() && $sb->hasTable('contacts');
        $hasCompanies = $db->table('data_types')->where('slug', 'companies')->exists() && $sb->hasTable('companies');

        $fields = [
            'id' => ['type' => 'number', 'title' => 'ID', 'only_read' => 1, 'is_default' => 1, 'is_program' => 1],
            'name' => ['type' => 'text', 'title' => 'Название', 'is_default' => 1, 'permanent_name' => 1],
            'date' => ['type' => 'date', 'title' => 'Дата поставки'],
        ];
        if ($hasCompanies) {
            $fields['company_id'] = ['type' => 'relation', 'title' => 'Поставщик', 'details' => '{"table":"companies"}', 'is_link' => 1, 'is_plural' => 1, 'relation_table' => 'companies'];
        }
        if ($hasContacts) {
            $fields['contact_id'] = ['type' => 'relation', 'title' => 'Контакт', 'details' => '{"table":"contacts"}', 'is_link' => 1, 'is_plural' => 1, 'relation_table' => 'contacts'];
        }
        $fields += [
            'products' => ['type' => 'json', 'title' => 'Состав', 'only_read' => 1],
            'sum' => ['type' => 'number', 'title' => 'Сумма', 'unit' => 'руб.', 'only_read' => 1],
            'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1],
            'photo' => ['type' => 'file', 'title' => 'Файлы', 'show_file_name' => 1],
            'user_id' => ['type' => 'relation', 'title' => 'Ответственный', 'details' => '{"table":"users"}', 'is_link' => 1, 'required' => 1, 'relation_table' => 'users', 'is_inactive' => 1],
            'weight' => ['type' => 'number', 'title' => 'Вес', 'hide' => 1],
            'volume' => ['type' => 'number', 'title' => 'Объем', 'hide' => 1],
            'created_at' => ['type' => 'date', 'title' => 'Дата создания', 'only_read' => 1, 'is_default' => 1, 'hide' => 1, 'mobile_pages' => '0'],
            'updated_at' => ['type' => 'date', 'title' => 'Дата изменения', 'only_read' => 1, 'is_default' => 1, 'hide' => 1, 'mobile_pages' => '0'],
        ];
        $fields += $this->taskFields($db, $hasCompanies);

        $this->removePaymentField($db, $typeId, $label);
        if (self::renameReceiverField($db, $typeId)) {
            $this->line("    [{$label}] {$slug}: поле «" . self::RECEIVER_FIELD . "» переименовано в «" . self::RECEIVER_FIELD_TITLE . "»");
        }

        $sort = 0;
        $added = 0;
        foreach ($fields as $field => $attrs) {
            $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
            if ($existing) {
                $patch = array_intersect_key($attrs, array_flip(['type', 'details', 'relation_table', 'is_plural', 'only_read', 'unit', 'mask']));
                $patch['is_remove'] = 0;
                $db->table('data_rows')->where('id', $existing->id)->update($patch);
                $sort++;
                continue;
            }
            $db->table('data_rows')->insert(array_merge(InstallSaleDocsEntities::baseRow($typeId, (int) $infoSecId), $attrs, [
                'field' => $field, 'sort' => $sort,
            ]));
            $sort++;
            $added++;
        }

        if (!$db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->exists()) {
            $db->table('settings')->insert([
                'key' => 'menu', 'display_name' => null,
                'value' => json_encode([
                    ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                    ['title' => 'Товары и услуги', 'tab' => 'products', 'sort' => 1, 'enabled' => 1, 'id' => 1],
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 2, 'enabled' => true, 'id' => 2, 'has_roles_read' => false, 'roles_read' => null],
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
                    ->update(['enabled' => 1, 'is_hidden' => 0, 'name' => self::TITLE_PLURAL, 'updated_at' => $now]);
            } else {
                $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
                $db->table('sidebar_items')->insert([
                    'created_at' => $now, 'updated_at' => $now,
                    'name' => self::TITLE_PLURAL, 'slug' => $slug,
                    'sort' => 0, 'link' => '/objects/' . $slug,
                    '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                    'is_hidden' => 0, 'enabled' => 1,
                ]);
            }
        }

        $this->line("    [{$label}] {$slug}: data_type={$typeId}, полей добавлено {$added}");

        $this->installProductField($db, $label);
        $this->installCompanyField($db, $label);

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->whereIn('url', ['fields/' . $slug, 'fields/products', 'fields/companies'])->update(['updated_at' => $now]);
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

    public static function renameReceiverField($db, int $typeId): int
    {
        return $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', self::RECEIVER_FIELD)
            ->whereIn('title', self::RECEIVER_FIELD_OLD_TITLES)
            ->update(['title' => self::RECEIVER_FIELD_TITLE]);
    }

    private function removePaymentField($db, int $typeId, string $label): void
    {
        $ids = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', 'payment')->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }
        $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
        $db->table('data_rows')->whereIn('id', $ids)->delete();
        $this->line("    [{$label}] " . self::SLUG . ': поле «Оплата, руб» удалено');
    }

    public const TASK_COLUMNS = [
        'address' => 'LONGTEXT NULL',
        'phone' => 'TEXT NULL',
        'time' => 'TEXT NULL',
        'contact' => 'TEXT NULL',
        'car_requirements' => 'TEXT NULL',
        'employee_requirements' => 'TEXT NULL',
        'service_time' => 'INT NULL',
        'delivery_price' => 'TEXT NULL',
        'shipment_company_id' => 'TEXT NULL',
    ];

    public const TASK_FIELDS = [
        'address' => ['type' => 'address', 'title' => 'Адрес'],
        'phone' => ['type' => 'multi_text', 'title' => 'Телефон', 'is_plural' => 1],
        'time' => ['type' => 'text', 'title' => 'Окно', 'mask' => '##:## - ##:##'],
        'contact' => ['type' => 'text', 'title' => 'Контактное лицо'],
        'car_requirements' => ['type' => 'select_dropdown', 'title' => 'Требования к машине', 'is_plural' => 1, 'details' => '{"options":[{"label":"Гидролифт","value":0},{"label":"Манипулятор","value":1},{"label":"Ручная","value":2}]}'],
        'employee_requirements' => ['type' => 'select_dropdown', 'title' => 'Требования к сотруднику', 'is_plural' => 1, 'details' => '{"options":[{"label":"Гражданство РФ","value":0}]}'],
        'service_time' => ['type' => 'number', 'title' => 'Время обслуживания'],
        'delivery_price' => ['type' => 'number', 'title' => 'Цена доставки', 'unit' => 'руб'],
        'shipment_company_id' => ['type' => 'relation', 'title' => self::RECEIVER_FIELD_TITLE, 'details' => '{"table":"companies"}', 'is_link' => 1, 'is_plural' => 0, 'relation_table' => 'companies'],
    ];

    private function taskFields($db, bool $hasCompanies): array
    {
        $taskTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        $result = [];
        foreach (self::TASK_FIELDS as $field => $attrs) {
            if ($field === 'shipment_company_id' && !$hasCompanies) {
                continue;
            }
            $source = $taskTypeId
                ? $db->table('data_rows')->where('data_type_id', $taskTypeId)->where('field', $field)->where('is_remove', 0)->first()
                : null;
            if ($source && $source->type !== 'status') {
                $attrs = array_merge($attrs, array_filter([
                    'type' => $source->type,
                    'title' => $field === self::RECEIVER_FIELD ? null : $source->title,
                    'details' => $source->details,
                    'mask' => $source->mask,
                    'unit' => $source->unit,
                    'relation_table' => $source->relation_table,
                ], fn ($v) => $v !== null && $v !== ''), ['is_plural' => (int) $source->is_plural, 'is_link' => (int) $source->is_link]);
            }
            $result[$field] = $attrs;
        }

        return $result;
    }

    private function installCompanyField($db, string $label): void
    {
        $result = \App\Services\ReverseLinkService::installField($db, 'companies', self::COMPANY_FIELD, self::COMPANY_FIELD_TITLE, self::SLUG);
        if ($result === null) {
            $this->line("    [{$label}] companies: сущности нет, поле «" . self::COMPANY_FIELD_TITLE . "» не добавлено");
            return;
        }
        if ($result === 'created') {
            $this->line("    [{$label}] companies: добавлено поле «" . self::COMPANY_FIELD_TITLE . "»");
        }
        $map = [];
        foreach ($db->table(self::SLUG)->whereNull('deleted_at')->get(['id', 'company_id']) as $order) {
            foreach (\App\Services\ReverseLinkService::ids($order->company_id) as $cid) {
                $map[$cid][] = (int) $order->id;
            }
        }
        $filled = \App\Services\ReverseLinkService::backfill($db, 'companies', self::COMPANY_FIELD, $map);
        if ($filled) {
            $this->line("    [{$label}] companies: связи с заказами поставщикам заполнены у {$filled} компаний");
        }
    }

    private function installProductField($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        $typeId = $db->table('data_types')->where('slug', 'products')->value('id');
        if (!$typeId || !$sb->hasTable('products')) {
            $this->line("    [{$label}] products: сущности нет, поле «" . self::PRODUCT_FIELD_TITLE . "» не добавлено");
            return;
        }
        if (!$sb->hasColumn('products', self::PRODUCT_FIELD)) {
            $db->statement('ALTER TABLE `products` ADD COLUMN `' . self::PRODUCT_FIELD . '` TEXT NULL');
        }

        $attrs = [
            'type' => 'relation', 'title' => self::PRODUCT_FIELD_TITLE, 'details' => '{"table":"' . self::SLUG . '"}',
            'is_link' => 1, 'is_plural' => 1, 'relation_table' => self::SLUG, 'only_read' => 1,
            'is_permanent' => 1, 'is_remove' => 0, 'hide' => 0,
        ];
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::PRODUCT_FIELD)->first();
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update($attrs);
        } else {
            $sectionId = (int) ($db->table('field_sections')
                ->where('page', 'products')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id') ?: 0);
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $db->table('data_rows')->insert(array_merge(InstallSaleDocsEntities::baseRow((int) $typeId, $sectionId), $attrs, [
                'field' => self::PRODUCT_FIELD, 'sort' => $maxSort + 1,
            ]));
            $this->line("    [{$label}] products: добавлено поле «" . self::PRODUCT_FIELD_TITLE . "»");
        }

        foreach ($db->table('settings')->where(['type' => 'menu', 'entity' => 'products'])->get() as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs) || collect($tabs)->contains(fn ($tab) => ($tab['tab'] ?? null) === self::PRODUCT_FIELD)) {
                continue;
            }
            $maxId = 0;
            foreach ($tabs as $tab) {
                $maxId = max($maxId, (int) ($tab['id'] ?? 0));
            }
            $ordered = [];
            $inserted = false;
            foreach ($tabs as $tab) {
                if (!$inserted && in_array($tab['tab'] ?? null, ['history', 'modules'], true)) {
                    $ordered[] = ['title' => self::PRODUCT_FIELD_TITLE, 'tab' => self::PRODUCT_FIELD, 'slug' => self::SLUG, 'sort' => (int) ($tab['sort'] ?? 0), 'enabled' => 1, 'id' => $maxId + 1, 'roles_read' => null];
                    $inserted = true;
                }
                $ordered[] = $tab;
            }
            if (!$inserted) {
                $ordered[] = ['title' => self::PRODUCT_FIELD_TITLE, 'tab' => self::PRODUCT_FIELD, 'slug' => self::SLUG, 'sort' => count($ordered), 'enabled' => 1, 'id' => $maxId + 1, 'roles_read' => null];
            }
            foreach ($ordered as $i => $tab) {
                $ordered[$i]['sort'] = $i;
            }
            $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($ordered, JSON_UNESCAPED_SLASHES)]);
        }

        $orders = $db->table(self::SLUG)->whereNull('deleted_at')->get(['id', 'products']);
        if ($orders->count()) {
            $map = [];
            foreach ($orders as $order) {
                foreach (\App\Models\SupplierOrder::productIds($order->products) as $pid) {
                    $map[$pid][] = (int) $order->id;
                }
            }
            foreach ($map as $pid => $ids) {
                $db->table('products')->where('id', $pid)->update([self::PRODUCT_FIELD => json_encode(array_values(array_unique($ids)))]);
            }
            $this->line("    [{$label}] products: связи с заказами заполнены у " . count($map) . " товаров");
        }
    }
}
