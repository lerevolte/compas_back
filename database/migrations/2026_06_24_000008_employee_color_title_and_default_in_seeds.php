<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')
            || !Schema::connection('seeds')->hasTable('data_types')
            || !Schema::connection('seeds')->hasTable('field_values')) {
            return;
        }

        $db = DB::connection('seeds');

        $empTypeId = $db->table('data_types')->where('slug', 'employees')->value('id');
        if (!$empTypeId) {
            return;
        }

        $field = $db->table('data_rows')
            ->where('data_type_id', $empTypeId)
            ->where('field', 'color_status')
            ->first();
        if (!$field) {
            return;
        }

        $db->table('data_rows')->where('id', $field->id)->update(['title' => 'Цвет', 'set_color' => 1]);

        $grey = $db->table('field_values')
            ->where('field_id', $field->id)
            ->where('color', '#8F8F8F')
            ->first();

        if (!$grey) {
            $greyId = $db->table('field_values')->insertGetId([
                'field_id'  => $field->id,
                'color'     => '#8F8F8F',
                'file'      => null,
                'value'     => 'Серый',
                'sort'      => 0,
                'is_hidden' => 0,
            ]);
        } else {
            $greyId = $grey->id;
        }

        $db->table('data_rows')->where('id', $field->id)->update([
            'default_value' => $greyId,
            'set_default'   => 1,
        ]);
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows') || !Schema::connection('seeds')->hasTable('data_types')) {
            return;
        }

        $empTypeId = DB::connection('seeds')->table('data_types')->where('slug', 'employees')->value('id');
        if (!$empTypeId) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->where('data_type_id', $empTypeId)
            ->where('field', 'color_status')
            ->update(['set_default' => 0, 'default_value' => null]);
    }
};
