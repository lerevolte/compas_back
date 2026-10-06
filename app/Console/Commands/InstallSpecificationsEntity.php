<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallSpecificationsEntity extends Command
{
    protected $signature = 'entity:install-specifications
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Сущность «Спецификации»: готовая продукция (товар) и обязательный состав на вкладке «Состав» с дробным количеством и столбцом «Кол-во продукции»';

    public const SLUG = 'specifications';
    public const MODEL = 'App\\Models\\Specification';
    public const TITLE_SINGULAR = 'Спецификация';
    public const TITLE_PLURAL = 'Спецификации';
    public const COLOR = '#5B8C5A';
    public const TAB_TITLE = 'Состав';

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
        $slug = self::SLUG;

        if (!$db->table('data_types')->where('slug', 'products')->exists() || !$sb->hasTable('products')) {
            $this->warn("    [{$label}] сущность products не найдена, пропуск");
            return;
        }

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
  `finished_product_id` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `products` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

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
            'slug_singular' => 'specification',
            'hidden' => 0,
        ];
        if ($type) {
            $db->table('data_types')->where('id', $type->id)->update($typeAttrs);
            $typeId = (int) $type->id;
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
            'finished_product_id' => ['type' => 'relation', 'title' => 'Готовая продукция', 'details' => '{"table":"products"}', 'is_link' => 1, 'relation_table' => 'products', 'is_default' => 1],
            'products' => ['type' => 'json', 'title' => 'Состав', 'only_read' => 1, 'required' => 1, 'is_default' => 1],
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
                $patch = array_intersect_key($attrs, array_flip(['type', 'details', 'relation_table', 'is_plural', 'only_read']));
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

        if (!$db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->exists()) {
            $db->table('settings')->insert([
                'key' => 'menu', 'display_name' => null,
                'value' => json_encode([
                    ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                    ['title' => self::TAB_TITLE, 'tab' => 'products', 'sort' => 1, 'enabled' => 1, 'id' => 1],
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 2, 'enabled' => true, 'id' => 2, 'has_roles_read' => false, 'roles_read' => null],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'type' => 'menu', 'entity' => $slug, 'user_id' => null,
            ]);
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

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => $now]);
                $db->table('local_cache')->where('url', 'sidebar')->update(['updated_at' => $now]);
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

    private function installProductField($db, string $label): void
    {
        $field = \App\Models\Specification::PRODUCT_FIELD;
        $result = \App\Services\ReverseLinkService::installField($db, 'products', $field, self::TITLE_SINGULAR, self::SLUG);
        if ($result === null) {
            return;
        }
        if ($result === 'created') {
            $this->line("    [{$label}] products: добавлено поле «" . self::TITLE_SINGULAR . "»");
        }
        $map = [];
        foreach ($db->table(self::SLUG)->whereNull('deleted_at')->get(['id', 'finished_product_id']) as $specification) {
            foreach (\App\Services\ReverseLinkService::ids($specification->finished_product_id) as $productId) {
                $map[$productId][] = (int) $specification->id;
            }
        }
        $filled = \App\Services\ReverseLinkService::backfill($db, 'products', $field, $map);
        if ($filled) {
            $this->line("    [{$label}] products: спецификации проставлены у {$filled} товаров");
        }
    }
}
