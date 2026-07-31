<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            $db = DB::connection('seeds');
            $schema = Schema::connection('seeds');
            $db->getPdo();
        } catch (\Throwable $e) {
            return;
        }

        $tenantMigration = require database_path('migrations/tenant/2026_07_31_000003_fix_products_category_relation_table.php');
        $tenantMigration::apply($db, $schema);
    }

    public function down(): void
    {
    }
};
