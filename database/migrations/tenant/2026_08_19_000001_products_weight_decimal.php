<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        self::apply(DB::connection(), Schema::connection(null));
    }

    public static function apply($db, $schema): void
    {
        if (!$schema->hasTable('products') || !$schema->hasColumn('products', 'weight')) {
            return;
        }

        $db->statement('ALTER TABLE `products` MODIFY `weight` DOUBLE NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'weight')) {
            return;
        }

        DB::statement('ALTER TABLE `products` MODIFY `weight` INT NULL DEFAULT NULL');
    }
};
