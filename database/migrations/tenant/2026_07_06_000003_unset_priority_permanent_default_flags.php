<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$typeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'priority')
            ->update(['is_permanent' => 0, 'is_default' => 0]);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$typeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'priority')
            ->update(['is_permanent' => 1, 'is_default' => 1]);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
