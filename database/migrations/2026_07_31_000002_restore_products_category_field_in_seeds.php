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

        $tenantMigration = require database_path('migrations/tenant/2026_07_31_000002_restore_products_category_field.php');
        $tenantMigration::apply($db, $schema);
    }

    public function down(): void
    {
        try {
            $db = DB::connection('seeds');
            $db->getPdo();
        } catch (\Throwable $e) {
            return;
        }

        if (!$db->getSchemaBuilder()->hasTable('data_rows')) {
            return;
        }
        $typeId = $db->table('data_types')->where('slug', 'products')->value('id');
        if ($typeId) {
            $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', 'category_id')
                ->delete();
        }
    }
};
