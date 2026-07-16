<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        self::apply(DB::connection(), Schema::getFacadeRoot());

        if (class_exists(\App\Models\Settings::class) && function_exists('tenant') && tenant('id')) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
            try {
                $now = now();
                foreach (DB::table('users')->pluck('id') as $userId) {
                    $updated = DB::table('local_cache')
                        ->where(['url' => 'logistic_tasks', 'user_id' => $userId])
                        ->update(['updated_at' => $now]);
                    if (!$updated) {
                        DB::table('local_cache')->insert([
                            'url' => 'logistic_tasks',
                            'user_id' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }
    }

    public static function apply($db, $schema): void
    {
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }
        $tasksTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$tasksTypeId) {
            return;
        }

        $sectionIds = $db->table('field_sections')
            ->where('page', 'logistic_tasks')
            ->whereNull('module')
            ->orderBy('id')
            ->pluck('id')
            ->all();
        if (!count($sectionIds)) {
            $sectionIds = $db->table('field_sections')
                ->where('page', 'logistic_tasks')
                ->orderBy('id')
                ->pluck('id')
                ->all();
        }
        $defaultSectionId = count($sectionIds) ? $sectionIds[0] : null;
        $allPageSectionIds = $db->table('field_sections')
            ->where('page', 'logistic_tasks')
            ->pluck('id')
            ->all();

        $employeeRow = $db->table('data_rows')
            ->where('data_type_id', $tasksTypeId)
            ->where('field', 'employee_id')
            ->first();

        if (!$employeeRow) {
            $tenantMigration = include database_path('migrations/tenant/2026_07_16_000001_make_employee_fields_plural.php');
            $tenantMigration::updateDataRows($db, $schema);
        } else {
            $update = [
                'type' => 'relation',
                'is_plural' => 1,
                'relation_table' => 'employees',
                'related_field' => 'logistic_task_id',
                'details' => json_encode(['table' => 'employees'], JSON_UNESCAPED_UNICODE),
                'hide' => 0,
                'only_read' => 0,
                'is_remove' => 0,
                'group_id' => null,
            ];
            if ($defaultSectionId && (!$employeeRow->section_id || !in_array($employeeRow->section_id, $allPageSectionIds))) {
                $update['section_id'] = $defaultSectionId;
            }
            if (!$employeeRow->title) {
                $update['title'] = 'Сотрудник';
            }
            $db->table('data_rows')->where('id', $employeeRow->id)->update($update);
        }

        $productsRow = $db->table('data_rows')
            ->where('data_type_id', $tasksTypeId)
            ->where('field', 'products')
            ->first();
        if ($productsRow) {
            $update = [
                'only_read' => 1,
                'hide' => 0,
                'is_remove' => 0,
                'group_id' => null,
            ];
            if ($defaultSectionId && (!$productsRow->section_id || !in_array($productsRow->section_id, $allPageSectionIds))) {
                $update['section_id'] = $defaultSectionId;
            }
            $db->table('data_rows')->where('id', $productsRow->id)->update($update);
        }
    }

    public function down(): void
    {
    }
};
