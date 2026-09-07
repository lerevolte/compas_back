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
        foreach (['data_rows', 'data_types'] as $table) {
            if (!$schema->hasTable($table) || !$schema->hasColumn($table, 'details')) {
                continue;
            }
            $db->statement("ALTER TABLE `{$table}` MODIFY `details` MEDIUMTEXT NULL");
        }
    }

    public function down(): void
    {
    }
};
