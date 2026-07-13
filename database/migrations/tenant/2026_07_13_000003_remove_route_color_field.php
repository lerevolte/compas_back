<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $typeIds = DB::table('data_types')->where('slug', 'routes')->pluck('id');
            if ($typeIds->isNotEmpty()) {
                $rowIds = DB::table('data_rows')
                    ->whereIn('data_type_id', $typeIds)
                    ->where('field', 'color')
                    ->pluck('id');
                if ($rowIds->isNotEmpty()) {
                    if (Schema::hasTable('field_values')) {
                        DB::table('field_values')->whereIn('field_id', $rowIds)->delete();
                    }
                    DB::table('data_rows')->whereIn('id', $rowIds)->delete();
                }
            }
        }

        if (Schema::hasTable('routes') && Schema::hasColumn('routes', 'color')) {
            Schema::table('routes', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('routes') && !Schema::hasColumn('routes', 'color')) {
            Schema::table('routes', function (Blueprint $table) {
                $table->text('color')->nullable();
            });
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
