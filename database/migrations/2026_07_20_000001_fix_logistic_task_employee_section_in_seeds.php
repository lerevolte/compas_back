<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenantMigration = include database_path('migrations/tenant/2026_07_20_000001_fix_logistic_task_employee_section.php');
        $tenantMigration::apply(DB::connection('seeds'), Schema::connection('seeds'));
    }

    public function down(): void
    {
    }
};
