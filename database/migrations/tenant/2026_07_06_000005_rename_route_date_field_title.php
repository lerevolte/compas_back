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

        $typeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'date')
            ->where('title', 'Дата')
            ->update(['title' => 'Дата доставки']);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'date')
            ->where('title', 'Дата доставки')
            ->update(['title' => 'Дата']);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
