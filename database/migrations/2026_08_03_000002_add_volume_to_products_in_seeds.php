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

        $tenantMigration = include database_path('migrations/tenant/2026_08_03_000002_add_volume_to_products.php');
        $tenantMigration::apply($db, $schema);
    }

    public function down(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if ($schema->hasTable('data_rows') && $schema->hasTable('data_types')) {
            $typeId = $db->table('data_types')->where('slug', 'products')->value('id');
            if ($typeId) {
                $db->table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('field', 'volume')
                    ->delete();
            }
        }

        if ($schema->hasTable('products') && $schema->hasColumn('products', 'volume')) {
            $schema->table('products', function ($table) {
                $table->dropColumn('volume');
            });
        }
    }
};
