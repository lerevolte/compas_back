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

        $tenantMigration = include database_path('migrations/tenant/2026_07_16_000004_fix_logistic_task_fields_visibility.php');
        $tenantMigration::apply($db, $schema);
    }

    public function down(): void
    {
    }
};
