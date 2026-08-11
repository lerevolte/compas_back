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

        $typeId = DB::table('data_types')->where('slug', 'users')->value('id');
        if (!$typeId) {
            return;
        }

        $fields = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'work_phone')
            ->get();

        if ($fields->isEmpty() && !Schema::hasColumn('users', 'work_phone')) {
            return;
        }

        if (Schema::hasColumn('users', 'work_phone')) {
            Schema::table('users', function ($table) {
                $table->dropColumn('work_phone');
            });
        }

        $ids = $fields->pluck('id')->all();
        if ($ids) {
            DB::table('data_rows')->whereIn('id', $ids)->delete();
            DB::table('data_rows')->whereIn('group_id', $ids)->update(['group_id' => null]);
            if (Schema::hasTable('field_values')) {
                DB::table('field_values')->whereIn('field_id', $ids)->delete();
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
    }
};
