<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenantMigration = include database_path('migrations/tenant/2026_07_16_000002_show_products_field_in_logistic_tasks.php');
        $tenantMigration::apply(DB::connection('seeds'), Schema::connection('seeds'));
    }

    public function down(): void
    {
        $db = DB::connection('seeds');
        $schema = Schema::connection('seeds');
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }
        $typeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if ($typeId) {
            $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', 'products')
                ->update(['only_read' => 0, 'hide' => 1]);
        }
    }
};
