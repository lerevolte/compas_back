<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('routes') && Schema::hasColumn('routes', 'employee_id')) {
            DB::statement('ALTER TABLE `routes` MODIFY `employee_id` TEXT NULL');
        }

        if (!Schema::hasTable('route_employee')) {
            Schema::create('route_employee', function (Blueprint $table) {
                $table->unsignedInteger('route_id');
                $table->unsignedInteger('employee_id');
            });
        }

        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'route_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('route_id')->nullable();
            });
        }

        if (Schema::hasTable('logistic_tasks') && !Schema::hasColumn('logistic_tasks', 'employee_id')) {
            Schema::table('logistic_tasks', function (Blueprint $table) {
                $table->text('employee_id')->nullable();
            });
        }

        if (!Schema::hasTable('logistic_task_employee')) {
            Schema::create('logistic_task_employee', function (Blueprint $table) {
                $table->unsignedInteger('logistic_task_id');
                $table->unsignedInteger('employee_id');
            });
        }

        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'logistic_task_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('logistic_task_id')->nullable();
            });
        }

        if (Schema::hasTable('routes') && Schema::hasTable('route_employee')) {
            $rows = DB::table('routes')
                ->whereNotNull('employee_id')
                ->where('employee_id', '!=', '')
                ->where('employee_id', '!=', '[]')
                ->get(['id', 'employee_id']);
            foreach ($rows as $row) {
                $ids = self::parseEmployeeIds($row->employee_id);
                if (!count($ids)) {
                    continue;
                }
                foreach ($ids as $employeeId) {
                    $exists = DB::table('route_employee')
                        ->where('route_id', $row->id)
                        ->where('employee_id', $employeeId)
                        ->exists();
                    if (!$exists) {
                        DB::table('route_employee')->insert([
                            'route_id' => $row->id,
                            'employee_id' => $employeeId,
                        ]);
                    }
                }
                DB::table('routes')->where('id', $row->id)->update([
                    'employee_id' => json_encode(array_values($ids)),
                ]);
            }
        }

        self::updateDataRows(DB::connection(), Schema::getFacadeRoot());

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public static function parseEmployeeIds($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_numeric($raw)) {
            return (int) $raw ? [(int) $raw] : [];
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        if (array_key_exists('value', $decoded)) {
            $decoded = is_array($decoded['value']) ? $decoded['value'] : [$decoded['value']];
        }
        $ids = [];
        foreach ($decoded as $v) {
            if (is_numeric($v) && (int) $v) {
                $ids[(int) $v] = (int) $v;
            }
        }
        return array_values($ids);
    }

    public static function updateDataRows($db, $schema): void
    {
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }

        $routesTypeId = $db->table('data_types')->where('slug', 'routes')->value('id');
        $routeEmployeeRow = null;
        if ($routesTypeId) {
            $routeEmployeeRow = $db->table('data_rows')
                ->where('data_type_id', $routesTypeId)
                ->where('field', 'employee_id')
                ->first();
            if ($routeEmployeeRow) {
                $details = json_decode((string) $routeEmployeeRow->details, true);
                if (!is_array($details)) {
                    $details = [];
                }
                $details['table'] = 'employees';
                $db->table('data_rows')->where('id', $routeEmployeeRow->id)->update([
                    'is_plural' => 1,
                    'relation_table' => 'employees',
                    'related_field' => 'route_id',
                    'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }

        $tasksTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$tasksTypeId) {
            return;
        }
        $exists = $db->table('data_rows')
            ->where('data_type_id', $tasksTypeId)
            ->where('field', 'employee_id')
            ->exists();
        if ($exists) {
            $db->table('data_rows')
                ->where('data_type_id', $tasksTypeId)
                ->where('field', 'employee_id')
                ->update([
                    'is_plural' => 1,
                    'relation_table' => 'employees',
                    'related_field' => 'logistic_task_id',
                ]);
            return;
        }

        $sectionId = $db->table('field_sections')
            ->where('page', 'logistic_tasks')
            ->whereNull('module')
            ->orderBy('id')
            ->value('id');
        if (!$sectionId) {
            $sectionId = $db->table('field_sections')->where('page', 'logistic_tasks')->orderBy('id')->value('id');
        }

        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $tasksTypeId)->max('sort');

        if ($routeEmployeeRow) {
            $row = (array) $routeEmployeeRow;
            unset($row['id']);
            $row['data_type_id'] = $tasksTypeId;
            $row['section_id'] = $sectionId;
            $row['module_section_id'] = null;
            $row['module'] = '';
            $row['hide'] = 0;
            $row['only_read'] = 0;
            $row['sort'] = $maxSort + 1;
            $row['is_plural'] = 1;
            $row['relation_table'] = 'employees';
            $row['related_field'] = 'logistic_task_id';
            $row['details'] = json_encode(['table' => 'employees'], JSON_UNESCAPED_UNICODE);
            $row['title'] = 'Сотрудник';
        } else {
            $row = [
                'data_type_id' => $tasksTypeId,
                'field' => 'employee_id',
                'type' => 'relation',
                'title' => 'Сотрудник',
                'required' => 0, 'details' => json_encode(['table' => 'employees']), 'visible_always' => 0, 'label_color' => '',
                'section_id' => $sectionId, 'group_id' => null, 'sort' => $maxSort + 1,
                'created_at' => now(), 'updated_at' => now(), 'button_name' => 'Загрузить',
                'show_file_image' => 0, 'hide' => 0, 'is_plural' => 1, 'roles_read' => '',
                'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
                'rules' => null, 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
                'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
                'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
                'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
                'relation_table' => 'employees', 'options' => null, 'set_color' => 0, 'related_field' => 'logistic_task_id',
                'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null, 'unit' => '',
            ];
        }

        $db->table('data_rows')->insert($row);
    }

    public function down(): void
    {
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $tasksTypeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');
            if ($tasksTypeId) {
                DB::table('data_rows')
                    ->where('data_type_id', $tasksTypeId)
                    ->where('field', 'employee_id')
                    ->delete();
            }
            $routesTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
            if ($routesTypeId) {
                DB::table('data_rows')
                    ->where('data_type_id', $routesTypeId)
                    ->where('field', 'employee_id')
                    ->update([
                        'is_plural' => 0,
                        'relation_table' => null,
                        'related_field' => null,
                    ]);
            }
        }

        if (Schema::hasTable('routes') && Schema::hasColumn('routes', 'employee_id')) {
            $rows = DB::table('routes')
                ->whereNotNull('employee_id')
                ->where('employee_id', '!=', '')
                ->get(['id', 'employee_id']);
            foreach ($rows as $row) {
                $ids = self::parseEmployeeIds($row->employee_id);
                DB::table('routes')->where('id', $row->id)->update([
                    'employee_id' => count($ids) ? $ids[0] : null,
                ]);
            }
            DB::statement('ALTER TABLE `routes` MODIFY `employee_id` INT NULL');
        }

        Schema::dropIfExists('route_employee');
        Schema::dropIfExists('logistic_task_employee');

        if (Schema::hasTable('employees')) {
            if (Schema::hasColumn('employees', 'route_id')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->dropColumn('route_id');
                });
            }
            if (Schema::hasColumn('employees', 'logistic_task_id')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->dropColumn('logistic_task_id');
                });
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
