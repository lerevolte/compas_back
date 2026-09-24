<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        self::apply(DB::connection(), Schema::getFacadeRoot());

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public static function apply($db, $schema): void
    {
        if (!$schema->hasTable('data_rows')) {
            return;
        }

        $db->table('data_rows')
            ->where('field', 'deal_id')
            ->where('type', 'relation')
            ->update(['is_permanent' => 1]);
    }

    public function down(): void
    {
    }
};
