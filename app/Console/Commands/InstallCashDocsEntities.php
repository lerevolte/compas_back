<?php

namespace App\Console\Commands;

use App\Models\ObjectRelation;
use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallCashDocsEntities extends Command
{
    protected $signature = 'entity:install-cash-docs
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Сущности кассы: «Статьи» (тип поступление/расход, категории), «Расходы» и «Поступления» (дата операции, статья), «Документы по кассе» — журнал операций, который строится из расходов и поступлений';

    public const ARTICLE_TYPES = '{"options":[{"label":"Поступление","value":"income"},{"label":"Расход","value":"expense"}]}';

    public const CATEGORY_TABLE = 'expense_article_categories';

    public const ENTITIES = [
        'expense_articles' => [
            'title_singular' => 'Статья',
            'title_plural' => 'Статьи',
            'model' => 'App\\Models\\ExpenseArticle',
            'slug_singular' => 'expense_article',
            'color' => '#A0522D',
            'columns' => [
                'article_type' => 'TEXT NULL',
                'category_id' => 'TEXT NULL',
            ],
            'fields' => [
                'article_type' => ['type' => 'select_dropdown', 'title' => 'Тип', 'details' => self::ARTICLE_TYPES, 'is_plural' => 1, 'is_default' => 1],
                'category_id' => ['type' => 'relation', 'title' => 'Категория', 'details' => '{"table":"expense_article_categories"}', 'relation_table' => 'expense_article_categories', 'is_default' => 1],
                'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1],
            ],
            'remove_fields' => [],
            'relations_tab' => false,
        ],
        'cash_documents' => [
            'title_singular' => 'Документ по кассе',
            'title_plural' => 'Документы по кассе',
            'model' => 'App\\Models\\CashDocument',
            'slug_singular' => 'cash_document',
            'color' => '#2F6F9F',
            'columns' => [
                'date' => 'DATE NULL',
                'sum' => 'VARCHAR(64) NULL',
                'operations' => 'LONGTEXT NULL',
                'operation_kind' => 'TEXT NULL',
                'expense_article_id' => 'TEXT NULL',
                'operation_slug' => 'VARCHAR(32) NULL',
                'operation_id' => 'BIGINT UNSIGNED NULL',
            ],
            'fields' => [
                'date' => ['type' => 'date', 'title' => 'Дата операции', 'only_read' => 1, 'is_default' => 1, 'rename' => 1],
                'operation_kind' => ['type' => 'select_dropdown', 'title' => 'Операция', 'details' => self::ARTICLE_TYPES, 'only_read' => 1, 'is_default' => 1],
                'sum' => ['type' => 'number', 'title' => 'Сумма', 'unit' => 'руб.', 'only_read' => 1, 'is_default' => 1],
                'expense_article_id' => ['type' => 'relation', 'title' => 'Статья', 'details' => '{"table":"expense_articles"}', 'is_link' => 1, 'relation_table' => 'expense_articles', 'only_read' => 1, 'is_default' => 1, 'rename' => 1],
                'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1, 'only_read' => 1, 'is_default' => 1],
            ],
            'remove_fields' => ['operations'],
            'readonly_base' => true,
            'relations_tab' => false,
        ],
        'cash_expenses' => [
            'title_singular' => 'Расход',
            'title_plural' => 'Расходы',
            'model' => 'App\\Models\\CashExpense',
            'slug_singular' => 'cash_expense',
            'color' => '#C0392B',
            'columns' => [
                'date' => 'DATE NULL',
                'sum' => 'VARCHAR(64) NULL',
                'expense_article_id' => 'TEXT NULL',
            ],
            'fields' => [
                'date' => ['type' => 'date', 'title' => 'Дата операции', 'is_default' => 1, 'rename' => 1],
                'sum' => ['type' => 'number', 'title' => 'Сумма', 'unit' => 'руб.', 'required' => 1, 'is_default' => 1],
                'expense_article_id' => ['type' => 'relation', 'title' => 'Статья', 'details' => '{"table":"expense_articles","option_filter":{"article_type":["expense"]}}', 'is_link' => 1, 'relation_table' => 'expense_articles', 'is_default' => 1, 'rename' => 1],
                'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1, 'is_default' => 1],
            ],
            'remove_fields' => [],
            'relations_tab' => true,
        ],
        'cash_incomes' => [
            'title_singular' => 'Поступление',
            'title_plural' => 'Поступления',
            'model' => 'App\\Models\\CashIncome',
            'slug_singular' => 'cash_income',
            'color' => '#2E8B57',
            'columns' => [
                'date' => 'DATE NULL',
                'sum' => 'VARCHAR(64) NULL',
                'expense_article_id' => 'TEXT NULL',
            ],
            'fields' => [
                'date' => ['type' => 'date', 'title' => 'Дата операции', 'is_default' => 1, 'rename' => 1],
                'sum' => ['type' => 'number', 'title' => 'Сумма', 'unit' => 'руб.', 'required' => 1, 'is_default' => 1],
                'expense_article_id' => ['type' => 'relation', 'title' => 'Статья', 'details' => '{"table":"expense_articles","option_filter":{"article_type":["income"]}}', 'is_link' => 1, 'relation_table' => 'expense_articles', 'is_default' => 1, 'rename' => 1],
                'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1, 'is_default' => 1],
            ],
            'remove_fields' => [],
            'relations_tab' => true,
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

        $this->installCategories($db, $now);

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
            foreach ($def['columns'] as $column => $ddl) {
                if (!$sb->hasColumn($slug, $column)) {
                    $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `{$column}` {$ddl}");
                }
            }

            $type = $db->table('data_types')->where('slug', $slug)->first();
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
            ] + $def['fields'] + [
                'photo' => ['type' => 'file', 'title' => 'Файлы', 'show_file_name' => 1],
                'user_id' => ['type' => 'relation', 'title' => 'Ответственный', 'details' => '{"table":"users"}', 'is_link' => 1, 'required' => 1, 'relation_table' => 'users', 'is_inactive' => 1],
                'created_at' => ['type' => 'date', 'title' => 'Дата создания', 'only_read' => 1, 'is_default' => 1, 'hide' => 1, 'mobile_pages' => '0'],
                'updated_at' => ['type' => 'date', 'title' => 'Дата изменения', 'only_read' => 1, 'is_default' => 1, 'hide' => 1, 'mobile_pages' => '0'],
            ];
            if (!empty($def['readonly_base'])) {
                foreach (['name', 'photo', 'user_id'] as $field) {
                    $fields[$field]['only_read'] = 1;
                    unset($fields[$field]['required']);
                }
            }

            foreach ($fields as $field => $attrs) {
                if (($attrs['type'] ?? '') === 'relation' && !empty($attrs['relation_table']) && !$db->table('data_types')->where('name', $attrs['relation_table'])->exists()) {
                    unset($fields[$field]);
                }
            }

            foreach ($def['remove_fields'] ?? [] as $field) {
                $rowIds = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->pluck('id');
                if ($rowIds->count()) {
                    if ($sb->hasTable('section_fields_sort')) {
                        $db->table('section_fields_sort')->whereIn('field_id', $rowIds)->delete();
                    }
                    $db->table('data_rows')->whereIn('id', $rowIds)->delete();
                }
            }

            $sort = 0;
            $added = 0;
            foreach ($fields as $field => $attrs) {
                $rename = !empty($attrs['rename']);
                unset($attrs['rename']);
                $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
                if ($existing) {
                    $patch = array_intersect_key($attrs, array_flip(array_merge(['type', 'details', 'relation_table', 'is_plural', 'only_read', 'unit'], $rename ? ['title'] : [])));
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
                        ['title' => 'История изменений', 'tab' => 'history', 'sort' => 1, 'enabled' => true, 'id' => 1, 'has_roles_read' => false, 'roles_read' => null],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'type' => 'menu', 'entity' => $slug, 'user_id' => null,
                ]);
            }
            if ($def['relations_tab']) {
                try {
                    ObjectRelation::ensureTab($slug, $db);
                } catch (\Throwable $e) {
                }
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

            if ($slug === 'expense_articles' && $sb->hasColumn($slug, 'article_type')) {
                $converted = $db->table($slug)->whereIn('article_type', ['income', 'expense'])->update(['article_type' => $db->raw('JSON_ARRAY(article_type)')]);
                if ($converted) {
                    $this->line("    [{$label}] {$slug}: тип переведён в множественный у {$converted}");
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
        \App\Services\CashDocumentService::forget();
        if ($inTenant) {
            $result = \App\Services\CashDocumentService::rebuild();
            $this->line("    [{$label}] журнал кассы: операций {$result['synced']}, старых документов снято {$result['legacy_removed']}");
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }

    private function installCategories($db, $now): void
    {
        $table = self::CATEGORY_TABLE;
        $db->statement(<<<SQL
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `choosed_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `_lft` int(11) NOT NULL DEFAULT 0,
  `_rgt` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `{$table}_lft_rgt_parent_id_index` (`_lft`, `_rgt`, `parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        $attrs = [
            'name' => $table,
            'slug' => $table,
            'title_singular' => 'Категория статей',
            'title_plural' => 'Категории статей',
            'model_name' => 'App\\Models\\ExpenseArticleCategory',
            'generate_permissions' => 0,
            'server_side' => 0,
            'updated_at' => $now,
            'enable' => 1,
            'slug_singular' => 'expense_article_category',
            'hidden' => 1,
        ];
        $typeId = $db->table('data_types')->where('slug', $table)->value('id');
        if ($typeId) {
            $db->table('data_types')->where('id', $typeId)->update($attrs);
        } else {
            $db->table('data_types')->insert($attrs + ['created_at' => $now]);
        }
    }
}
