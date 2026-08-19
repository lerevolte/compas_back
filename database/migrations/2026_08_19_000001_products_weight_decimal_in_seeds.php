<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenantMigration = include database_path('migrations/tenant/2026_08_19_000001_products_weight_decimal.php');
        $tenantMigration::apply(DB::connection('seeds'), Schema::connection('seeds'));
    }

    public function down(): void
    {
        $schema = Schema::connection('seeds');
        if ($schema->hasTable('products') && $schema->hasColumn('products', 'weight')) {
            DB::connection('seeds')->statement('ALTER TABLE `products` MODIFY `weight` INT NULL DEFAULT NULL');
        }
    }
};
