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

        $reset = [];
        if (Schema::hasColumn('data_rows', 'set_default')) {
            $reset['set_default'] = 0;
        }
        if (Schema::hasColumn('data_rows', 'default_value')) {
            $reset['default_value'] = null;
        }
        if (!$reset) {
            return;
        }

        DB::table('data_rows')
            ->whereIn('field', ['created_at', 'updated_at'])
            ->update($reset);

        $tasksTypeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if ($tasksTypeId) {
            DB::table('data_rows')
                ->where('data_type_id', $tasksTypeId)
                ->where('field', 'plan_time')
                ->update($reset);
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
    }
};
