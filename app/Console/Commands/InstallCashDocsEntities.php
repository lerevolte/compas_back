<?php

namespace App\Console\Commands;

use App\Models\ObjectRelation;
use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallCashDocsEntities extends Command
{
    protected $signature = 'entity:install-cash-docs
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Сущности кассы: «Статьи расходов», «Документы по кассе» (операции и сумма считаются по связанным расходам и поступлениям), «Расходы» и «Поступления» с обязательной суммой; расход и поступление создаются на основании документа по кассе';

    public const ENTITIES = [
        'expense_articles' => [
            'title_singular' => 'Статья расходов',
            'title_plural' => 'Статьи расходов',
            'model' => 'App\\Models\\ExpenseArticle',
            'slug_singular' => 'expense_article',
            'color' => '#A0522D',
            'columns' => [],
            'fields' => [
                'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1],
            ],
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
            ],
            'fields' => [
                'date' => ['type' => 'date', 'title' => 'Дата', 'is_default' => 1],
                'sum' => ['type' => 'number', 'title' => 'Сумма', 'unit' => 'руб.', 'only_read' => 1, 'is_default' => 1],
                'operations' => ['type' => 'multi_text', 'title' => 'Операции', 'is_plural' => 1, 'only_read' => 1, 'is_default' => 1],
                'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1],
            ],
            'relations_tab' => true,
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
                'date' => ['type' => 'date', 'title' => 'Дата', 'is_default' => 1],
                'sum' => ['type' => 'number', 'title' => 'Сумма', 'unit' => 'руб.', 'required' => 1, 'is_default' => 1],
                'expense_article_id' => ['type' => 'relation', 'title' => 'Статья расходов', 'details' => '{"table":"expense_articles"}', 'is_link' => 1, 'relation_table' => 'expense_articles', 'is_default' => 1],
                'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1, 'is_default' => 1],
            ],
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
            ],
            'fields' => [
                'date' => ['type' => 'date', 'title' => 'Дата', 'is_default' => 1],
                'sum' => ['type' => 'number', 'title' => 'Сумма', 'unit' => 'руб.', 'required' => 1, 'is_default' => 1],
                'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1, 'is_default' => 1],
            ],
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
            if (isset($def['fields']['expense_article_id']) && !$db->table('data_types')->where('slug', 'expense_articles')->exists()) {
                unset($fields['expense_article_id']);
            }

            $sort = 0;
            $added = 0;
            foreach ($fields as $field => $attrs) {
                $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
                if ($existing) {
                    $patch = array_intersect_key($attrs, array_flip(['type', 'details', 'relation_table', 'is_plural', 'only_read', 'unit']));
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
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
