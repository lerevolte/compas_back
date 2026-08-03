<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        $tenantMigration = include database_path('migrations/tenant/2026_08_03_000001_add_employee_route_task_fields.php');
        $tenantMigration::addDataRows($db, $schema);
    }

    public function down(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if ($schema->hasTable('data_rows') && $schema->hasTable('data_types')) {
            $employeesTypeId = $db->table('data_types')->where('slug', 'employees')->value('id');
            if ($employeesTypeId) {
                $db->table('data_rows')
                    ->where('data_type_id', $employeesTypeId)
                    ->whereIn('field', ['route_id', 'logistic_task_id'])
                    ->delete();
            }
        }
    }
};
