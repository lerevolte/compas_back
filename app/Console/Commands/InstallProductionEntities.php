<?php

namespace App\Console\Commands;

use App\Models\ObjectRelation;
use App\Models\Tenant;
use App\Services\ProductionService;
use Illuminate\Console\Command;

class InstallProductionEntities extends Command
{
    protected $signature = 'entity:install-production
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Сущности «Заказы на производство» (продукция со спецификацией, материалы, статус выполнения по фактическому количеству) и «Производство» (создаётся на основании заказа, количества не больше заказа и спецификаций)';

    public const ENTITIES = [
        ProductionService::ORDER => [
            'title_singular' => 'Заказ на производство',
            'title_plural' => 'Заказы на производство',
            'model' => 'App\\Models\\ProductionOrder',
            'slug_singular' => 'production_order',
            'color' => '#8E5BB5',
            'status' => true,
        ],
        ProductionService::DOC => [
            'title_singular' => 'Производство',
            'title_plural' => 'Производство',
            'model' => 'App\\Models\\Production',
            'slug_singular' => 'production',
            'color' => '#B5703A',
            'status' => false,
        ],
    ];

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id, true));
                $this->info("  ✓ {$tenant->id}");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function installInto($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        $now = now();

        if (!$db->table('data_types')->where('slug', 'products')->exists() || !$sb->hasTable('products')) {
            $this->warn("    [{$label}] сущность products не найдена, пропуск");
            return;
        }

        foreach (self::ENTITIES as $slug => $def) {
            $db->statement(<<<SQL
CREATE TABLE IF NOT EXISTS `{$slug}` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `choosed_at` timestamp NULL DEFAULT NULL,
  `name` text DEFAULT NULL,
  `photo` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `color` varchar(191) DEFAULT '',
  `products` longtext DEFAULT NULL,
  `materials` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
            if ($def['status'] && !$sb->hasColumn($slug, ProductionService::STATUS_FIELD)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `" . ProductionService::STATUS_FIELD . '` TEXT NULL');
            }

            $typeAttrs = [
                'name' => $slug,
                'slug' => $slug,
                'title_singular' => $def['title_singular'],
                'title_plural' => $def['title_plural'],
                'model_name' => $def['model'],
                'generate_permissions' => 1,
                'server_side' => 0,
                'updated_at' => $now,
                'color' => $def['color'],
                'enable' => 1,
                'slug_singular' => $def['slug_singular'],
                'hidden' => 0,
            ];
            $typeId = (int) $db->table('data_types')->where('slug', $slug)->value('id');
            if ($typeId) {
                $db->table('data_types')->where('id', $typeId)->update($typeAttrs);
            } else {
                $typeId = (int) $db->table('data_types')->insertGetId($typeAttrs + ['created_at' => $now]);
            }

            $sectionId = $db->table('field_sections')
                ->where('page', $slug)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');
            if (!$sectionId) {
                $sectionId = $db->table('field_sections')->insertGetId([
                    'sort' => 0, 'name' => 'Информация', 'page' => $slug,
                    'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
                    'column_id' => 1, '_lft' => 0, '_rgt' => 0,
                ]);
            }

            $fields = [
                'id' => ['type' => 'number', 'title' => 'ID', 'only_read' => 1, 'is_default' => 1, 'is_program' => 1],
                'name' => ['type' => 'text', 'title' => 'Название', 'is_default' => 1, 'permanent_name' => 1],
            ];
            if ($def['status']) {
                $fields[ProductionService::STATUS_FIELD] = ['type' => 'status', 'title' => ProductionService::STATUS_TITLE, 'only_read' => 1, 'is_program' => 1, 'is_default' => 1, 'is_permanent' => 1];
            }
            $fields += [
                'products' => ['type' => 'json', 'title' => 'Продукция', 'only_read' => 1, 'is_default' => 1],
                ProductionService::MATERIALS_FIELD => ['type' => 'json', 'title' => 'Материалы', 'only_read' => 1, 'is_default' => 1],
                'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1],
                'photo' => ['type' => 'file', 'title' => 'Файлы', 'show_file_name' => 1],
                'user_id' => ['type' => 'relation', 'title' => 'Ответственный', 'details' => '{"table":"users"}', 'is_link' => 1, 'required' => 1, 'relation_table' => 'users', 'is_inactive' => 1],
                'created_at' => ['type' => 'date', 'title' => 'Дата создания', 'only_read' => 1, 'is_default' => 1, 'hide' => 1, 'mobile_pages' => '0'],
                'updated_at' => ['type' => 'date', 'title' => 'Дата изменения', 'only_read' => 1, 'is_default' => 1, 'hide' => 1, 'mobile_pages' => '0'],
            ];

            $sort = 0;
            $added = 0;
            foreach ($fields as $field => $attrs) {
                $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
                if ($existing) {
                    $patch = array_intersect_key($attrs, array_flip(['type', 'details', 'relation_table', 'is_plural', 'only_read', 'is_program']));
                    $patch['is_remove'] = 0;
                    $db->table('data_rows')->where('id', $existing->id)->update($patch);
                    $sort++;
                    continue;
                }
                $db->table('data_rows')->insert(array_merge(InstallSaleDocsEntities::baseRow($typeId, (int) $sectionId), $attrs, [
                    'field' => $field, 'sort' => $sort,
                ]));
                $sort++;
                $added++;
            }

            if ($def['status']) {
                $this->installStatusValues($db, $slug, $typeId, $label);
            }

            if (!$db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->exists()) {
                $db->table('settings')->insert([
                    'key' => 'menu', 'display_name' => null,
                    'value' => json_encode([
                        ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                        ['title' => 'Продукция', 'tab' => 'products', 'sort' => 1, 'enabled' => 1, 'id' => 1],
                        ['title' => 'Материалы', 'tab' => ProductionService::MATERIALS_FIELD, 'sort' => 2, 'enabled' => 1, 'id' => 2],
                        ['title' => 'История изменений', 'tab' => 'history', 'sort' => 3, 'enabled' => true, 'id' => 3, 'has_roles_read' => false, 'roles_read' => null],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
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
                        ->update(['enabled' => 1, 'is_hidden' => 0, 'name' => $def['title_plural'], 'updated_at' => $now]);
                } else {
                    $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
                    $db->table('sidebar_items')->insert([
                        'created_at' => $now, 'updated_at' => $now,
                        'name' => $def['title_plural'], 'slug' => $slug,
                        'sort' => 0, 'link' => '/objects/' . $slug,
                        '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                        'is_hidden' => 0, 'enabled' => 1,
                    ]);
                }
            }

            $this->line("    [{$label}] {$slug}: data_type={$typeId}, полей добавлено {$added}");
            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => $now]);
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'sidebar')->update(['updated_at' => $now]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
            foreach ($db->table(ProductionService::ORDER)->whereNull('deleted_at')->pluck('id') as $orderId) {
                ProductionService::recalcOrder((int) $orderId);
            }
        }
    }

    private function installStatusValues($db, string $slug, int $typeId, string $label): void
    {
        $fieldId = (int) $db->table('data_rows')->where('data_type_id', $typeId)->where('field', ProductionService::STATUS_FIELD)->value('id');
        if (!$fieldId) {
            return;
        }
        $visible = $db->table('field_values')->where('field_id', $fieldId)->where('is_hidden', '!=', 1)->count();
        if ($visible < count(ProductionService::STATUS_VALUES)) {
            foreach (ProductionService::STATUS_VALUES as $sort => $def) {
                $value = $db->table('field_values')->where('field_id', $fieldId)->where('value', $def['value'])->first();
                if ($value) {
                    $db->table('field_values')->where('id', $value->id)->update(['sort' => $sort, 'is_hidden' => 0]);
                } else {
                    $db->table('field_values')->insert([
                        'field_id' => $fieldId, 'value' => $def['value'], 'color' => $def['color'], 'sort' => $sort, 'is_hidden' => 0,
                    ]);
                }
            }
        }
        $defaultId = $db->table('field_values')->where('field_id', $fieldId)->where('is_hidden', '!=', 1)
            ->orderBy('sort')->orderBy('id')->value('id');
        if ($defaultId) {
            $filled = $db->table($slug)
                ->where(fn ($q) => $q->whereNull(ProductionService::STATUS_FIELD)->orWhere(ProductionService::STATUS_FIELD, ''))
                ->update([ProductionService::STATUS_FIELD => $defaultId]);
            if ($filled) {
                $this->line("    [{$label}] {$slug}: статус по умолчанию проставлен {$filled} строкам");
            }
        }
    }
}
