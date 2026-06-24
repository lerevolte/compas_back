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

        $field = DB::table('data_rows')
            ->where('data_type_id', $empTypeId)
            ->where('field', 'color_status')
            ->first();
        if (!$field) {
            return;
        }

        DB::table('data_rows')->where('id', $field->id)->update(['title' => 'Цвет', 'set_color' => 1]);

        $grey = DB::table('field_values')
            ->where('field_id', $field->id)
            ->where('color', '#8F8F8F')
            ->first();

        if (!$grey) {
            $greyId = DB::table('field_values')->insertGetId([
                'field_id'  => $field->id,
                'color'     => '#8F8F8F',
                'file'      => null,
                'value'     => 'Не выбрано',
                'sort'      => 0,
                'is_hidden' => 0,
            ]);
        } else {
            $greyId = $grey->id;
        }

        DB::table('data_rows')->where('id', $field->id)->update([
            'default_value' => $greyId,
            'set_default'   => 1,
        ]);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $empTypeId = DB::table('data_types')->where('slug', 'employees')->value('id');
        if (!$empTypeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $empTypeId)
            ->where('field', 'color_status')
            ->update(['set_default' => 0, 'default_value' => null]);
    }
};
