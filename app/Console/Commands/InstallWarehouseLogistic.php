<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallWarehouseLogistic extends Command
{
    protected $signature = 'entity:install-warehouse-logistic
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Складская логистика: сущности «Рабочие смены» и «Задачи склада» (скрытые, без пунктов меню) для страницы /warehouse-logistic';

    public const SHIFTS = 'work_shifts';
    public const TASKS = 'warehouse_tasks';

    public const TASK_STATUS_VALUES = [
        ['value' => 'Новая', 'color' => '#A8A8A8'],
        ['value' => 'В работе', 'color' => '#007AFF'],
        ['value' => 'Выполнена', 'color' => '#34C759'],
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

        if (!$sb->hasTable('employees') || !$db->table('data_types')->where('slug', 'employees')->exists()) {
            $this->warn("    [{$label}] сущность employees не найдена, пропуск");
            return;
        }
        $hasStorehouses = $sb->hasTable('storehouses') && $db->table('data_types')->where('slug', 'storehouses')->exists();

        $db->statement(<<<SQL
CREATE TABLE IF NOT EXISTS `work_shifts` (
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
  `shift_date` date DEFAULT NULL,
  `employee_id` text DEFAULT NULL,
  `storehouse_id` text DEFAULT NULL,
  `start_time` varchar(5) DEFAULT NULL,
  `end_time` varchar(5) DEFAULT NULL,
  `break_start` varchar(5) DEFAULT NULL,
  `break_end` varchar(5) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_shifts_shift_date_index` (`shift_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $db->statement(<<<SQL
CREATE TABLE IF NOT EXISTS `warehouse_tasks` (
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
  `status` text DEFAULT NULL,
  `task_date` date DEFAULT NULL,
  `work_shift_id` text DEFAULT NULL,
  `start_time` varchar(5) DEFAULT NULL,
  `end_time` varchar(5) DEFAULT NULL,
  `duration` text DEFAULT NULL,
  `storehouse_id` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_tasks_task_date_index` (`task_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $storehouse = $hasStorehouses
            ? ['storehouse_id' => ['type' => 'relation', 'title' => 'Склад', 'details' => '{"table":"storehouses"}', 'relation_table' => 'storehouses', 'is_link' => 1]]
            : [];
        $time = fn (string $title, array $extra = []) => array_merge(['type' => 'text', 'title' => $title, 'mask' => '##:##', 'is_default' => 1], $extra);
        $tail = [
            'user_id' => ['type' => 'relation', 'title' => 'Ответственный', 'details' => '{"table":"users"}', 'is_link' => 1, 'relation_table' => 'users', 'is_inactive' => 1],
            'created_at' => ['type' => 'date', 'title' => 'Дата создания', 'only_read' => 1, 'is_default' => 1, 'hide' => 1, 'mobile_pages' => '0'],
            'updated_at' => ['type' => 'date', 'title' => 'Дата изменения', 'only_read' => 1, 'is_default' => 1, 'hide' => 1, 'mobile_pages' => '0'],
        ];

        $this->installEntity($db, $label, self::SHIFTS, [
            'model' => 'App\\Models\\WorkShift',
            'singular' => 'Рабочая смена',
            'plural' => 'Рабочие смены',
            'slug_singular' => 'work_shift',
            'color' => '#5856D6',
        ], array_merge([
            'id' => ['type' => 'number', 'title' => 'ID', 'only_read' => 1, 'is_default' => 1, 'is_program' => 1],
            'name' => ['type' => 'text', 'title' => 'Название', 'is_default' => 1, 'permanent_name' => 1],
            'shift_date' => ['type' => 'date', 'title' => 'Дата смены', 'required' => 1, 'is_default' => 1],
            'employee_id' => ['type' => 'relation', 'title' => 'Сотрудник', 'details' => '{"table":"employees"}', 'relation_table' => 'employees', 'is_link' => 1, 'is_default' => 1],
        ], $storehouse, [
            'start_time' => $time('Начало смены'),
            'end_time' => $time('Конец смены'),
            'break_start' => $time('Начало обеда'),
            'break_end' => $time('Конец обеда'),
            'comment' => ['type' => 'text', 'title' => 'Примечание', 'is_plural' => 1],
        ], $tail));

        $tasksTypeId = $this->installEntity($db, $label, self::TASKS, [
            'model' => 'App\\Models\\WarehouseTask',
            'singular' => 'Задача склада',
            'plural' => 'Задачи склада',
            'slug_singular' => 'warehouse_task',
            'color' => '#FF9500',
        ], array_merge([
            'id' => ['type' => 'number', 'title' => 'ID', 'only_read' => 1, 'is_default' => 1, 'is_program' => 1],
            'name' => ['type' => 'text', 'title' => 'Название', 'is_default' => 1, 'permanent_name' => 1],
            'status' => ['type' => 'status', 'title' => 'Статус', 'is_default' => 1],
            'task_date' => ['type' => 'date', 'title' => 'Дата', 'is_default' => 1],
            'work_shift_id' => ['type' => 'relation', 'title' => 'Рабочая смена', 'details' => '{"table":"work_shifts"}', 'relation_table' => 'work_shifts', 'is_link' => 1, 'is_default' => 1],
            'start_time' => $time('Начало'),
            'end_time' => $time('Окончание'),
            'duration' => ['type' => 'number', 'title' => 'Длительность', 'unit' => 'мин', 'is_default' => 1],
        ], $storehouse, [
            'comment' => ['type' => 'text', 'title' => 'Описание', 'is_plural' => 1],
            'photo' => ['type' => 'file', 'title' => 'Файлы', 'show_file_name' => 1],
        ], $tail));

        $this->installStatusValues($db, $tasksTypeId, $label);

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->whereIn('url', ['fields/' . self::SHIFTS, 'fields/' . self::TASKS, 'sidebar'])->update(['updated_at' => $now]);
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

    private function installEntity($db, string $label, string $slug, array $meta, array $fields): int
    {
        $now = now();
        $type = $db->table('data_types')->where('slug', $slug)->first();
        $typeAttrs = [
            'name' => $slug,
            'slug' => $slug,
            'title_singular' => $meta['singular'],
            'title_plural' => $meta['plural'],
            'model_name' => $meta['model'],
            'generate_permissions' => 1,
            'server_side' => 0,
            'updated_at' => $now,
            'color' => $meta['color'],
            'enable' => 1,
            'slug_singular' => $meta['slug_singular'],
        ];
        if ($type) {
            $db->table('data_types')->where('id', $type->id)->update($typeAttrs);
            $typeId = (int) $type->id;
        } else {
            $typeId = (int) $db->table('data_types')->insertGetId($typeAttrs + ['created_at' => $now, 'hidden' => 1]);
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

        $sort = 0;
        $added = 0;
        foreach ($fields as $field => $attrs) {
            $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
            if ($existing) {
                $patch = array_intersect_key($attrs, array_flip(['type', 'details', 'relation_table', 'is_plural', 'only_read', 'mask', 'unit']));
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

        $this->line("    [{$label}] {$slug}: data_type={$typeId}, полей добавлено {$added}");

        return $typeId;
    }

    private function installStatusValues($db, int $typeId, string $label): void
    {
        $fieldId = (int) $db->table('data_rows')->where('data_type_id', $typeId)->where('field', 'status')->value('id');
        if (!$fieldId) {
            return;
        }
        if ($db->table('field_values')->where('field_id', $fieldId)->where('is_hidden', '!=', 1)->count() < count(self::TASK_STATUS_VALUES)) {
            foreach (self::TASK_STATUS_VALUES as $sort => $def) {
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
            $filled = $db->table(self::TASKS)
                ->where(fn ($q) => $q->whereNull('status')->orWhere('status', ''))
                ->update(['status' => $defaultId]);
            if ($filled) {
                $this->line("    [{$label}] " . self::TASKS . ": статус по умолчанию проставлен {$filled} строкам");
            }
        }
    }
}
