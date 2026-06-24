<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types') || !Schema::hasTable('field_values')) {
            return;
        }

        $empTypeId = DB::table('data_types')->where('slug', 'employees')->value('id');
        if (!$empTypeId) {
            return;
        }

        $fieldId = DB::table('data_rows')
            ->where('data_type_id', $empTypeId)
            ->where('field', 'color_status')
            ->value('id');
        if (!$fieldId) {
            return;
        }

        DB::table('field_values')
            ->where('field_id', $fieldId)
            ->where('color', '#8F8F8F')
            ->where('value', 'Серый')
            ->update(['value' => 'Не выбрано']);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        // Откат текста не требуется.
    }
};
