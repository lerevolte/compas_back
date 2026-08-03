<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        self::addDataRows(DB::connection(), Schema::getFacadeRoot());

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public static function addDataRows($db, $schema): void
    {
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types') || !$schema->hasTable('employees')) {
            return;
        }

        $employeesTypeId = $db->table('data_types')->where('slug', 'employees')->value('id');
        if (!$employeesTypeId) {
            return;
        }

        $sectionId = $db->table('field_sections')
            ->where('page', 'employees')
            ->whereNull('module')
            ->orderBy('id')
            ->value('id');
        if (!$sectionId) {
            $sectionId = $db->table('field_sections')->where('page', 'employees')->orderBy('id')->value('id');
        }

        $fields = [
            [
                'field' => 'route_id',
                'title' => 'Маршруты',
                'relation_table' => 'routes',
            ],
            [
                'field' => 'logistic_task_id',
                'title' => 'Задачи логистики',
                'relation_table' => 'logistic_tasks',
            ],
        ];

        foreach ($fields as $item) {
            if (!$schema->hasColumn('employees', $item['field'])) {
                continue;
            }
            if (!$db->table('data_types')->where('slug', $item['relation_table'])->exists()) {
                continue;
            }
            $exists = $db->table('data_rows')
                ->where('data_type_id', $employeesTypeId)
                ->where('field', $item['field'])
                ->exists();
            if ($exists) {
                $db->table('data_rows')
                    ->where('data_type_id', $employeesTypeId)
                    ->where('field', $item['field'])
                    ->update([
                        'type' => 'relation',
                        'is_plural' => 1,
                        'relation_table' => $item['relation_table'],
                        'related_field' => 'employee_id',
                        'details' => json_encode(['table' => $item['relation_table']], JSON_UNESCAPED_UNICODE),
                        'only_read' => 1,
                        'is_remove' => 0,
                        'hide' => 0,
                    ]);
                continue;
            }

            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $employeesTypeId)->max('sort');

            $db->table('data_rows')->insert([
                'data_type_id' => $employeesTypeId,
                'field' => $item['field'],
                'type' => 'relation',
                'title' => $item['title'],
                'required' => 0, 'details' => json_encode(['table' => $item['relation_table']], JSON_UNESCAPED_UNICODE),
                'visible_always' => 0, 'label_color' => '',
                'section_id' => $sectionId, 'group_id' => null, 'sort' => $maxSort + 1,
                'created_at' => now(), 'updated_at' => now(), 'button_name' => 'Загрузить',
                'show_file_image' => 0, 'hide' => 0, 'is_plural' => 1, 'roles_read' => '',
                'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
                'rules' => null, 'only_read' => 1, 'is_permanent' => 1, 'show_file_name' => 0,
                'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
                'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
                'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
                'relation_table' => $item['relation_table'], 'options' => null, 'set_color' => 0,
                'related_field' => 'employee_id',
                'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null, 'unit' => '',
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $employeesTypeId = DB::table('data_types')->where('slug', 'employees')->value('id');
            if ($employeesTypeId) {
                DB::table('data_rows')
                    ->where('data_type_id', $employeesTypeId)
                    ->whereIn('field', ['route_id', 'logistic_task_id'])
                    ->delete();
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
