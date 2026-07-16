<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if ($schema->hasTable('routes') && $schema->hasColumn('routes', 'employee_id')) {
            $db->statement('ALTER TABLE `routes` MODIFY `employee_id` TEXT NULL');
        }

        if (!$schema->hasTable('route_employee')) {
            $schema->create('route_employee', function (Blueprint $table) {
                $table->unsignedInteger('route_id');
                $table->unsignedInteger('employee_id');
            });
        }

        if ($schema->hasTable('employees') && !$schema->hasColumn('employees', 'route_id')) {
            $schema->table('employees', function (Blueprint $table) {
                $table->string('route_id')->nullable();
            });
        }

        if ($schema->hasTable('logistic_tasks') && !$schema->hasColumn('logistic_tasks', 'employee_id')) {
            $schema->table('logistic_tasks', function (Blueprint $table) {
                $table->text('employee_id')->nullable();
            });
        }

        if (!$schema->hasTable('logistic_task_employee')) {
            $schema->create('logistic_task_employee', function (Blueprint $table) {
                $table->unsignedInteger('logistic_task_id');
                $table->unsignedInteger('employee_id');
            });
        }

        if ($schema->hasTable('employees') && !$schema->hasColumn('employees', 'logistic_task_id')) {
            $schema->table('employees', function (Blueprint $table) {
                $table->string('logistic_task_id')->nullable();
            });
        }

        $tenantMigration = include database_path('migrations/tenant/2026_07_16_000001_make_employee_fields_plural.php');
        $tenantMigration::updateDataRows($db, $schema);
    }

    public function down(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if ($schema->hasTable('data_rows') && $schema->hasTable('data_types')) {
            $tasksTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
            if ($tasksTypeId) {
                $db->table('data_rows')
                    ->where('data_type_id', $tasksTypeId)
                    ->where('field', 'employee_id')
                    ->delete();
            }
            $routesTypeId = $db->table('data_types')->where('slug', 'routes')->value('id');
            if ($routesTypeId) {
                $db->table('data_rows')
                    ->where('data_type_id', $routesTypeId)
                    ->where('field', 'employee_id')
                    ->update([
                        'is_plural' => 0,
                        'relation_table' => null,
                        'related_field' => null,
                    ]);
            }
        }

        if ($schema->hasTable('routes') && $schema->hasColumn('routes', 'employee_id')) {
            $db->statement('ALTER TABLE `routes` MODIFY `employee_id` INT NULL');
        }

        $schema->dropIfExists('route_employee');
        $schema->dropIfExists('logistic_task_employee');

        if ($schema->hasTable('employees')) {
            if ($schema->hasColumn('employees', 'route_id')) {
                $schema->table('employees', function (Blueprint $table) {
                    $table->dropColumn('route_id');
                });
            }
            if ($schema->hasColumn('employees', 'logistic_task_id')) {
                $schema->table('employees', function (Blueprint $table) {
                    $table->dropColumn('logistic_task_id');
                });
            }
        }
    }
};
