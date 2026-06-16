<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ltType = DB::table('data_types')->where('slug', 'logistic_tasks')->first();
        if (!$ltType) {
            return;
        }

        $ltSec = DB::table('field_sections')
            ->where('page', 'logistic_tasks')
            ->whereNull('module')
            ->orderBy('id')
            ->value('id');
        if (!$ltSec) {
            $ltSec = DB::table('field_sections')
                ->where('page', 'logistic_tasks')
                ->orderBy('id')
                ->value('id');
        }
        if (!$ltSec) {
            return;
        }

        $exists = DB::table('data_rows')
            ->where('data_type_id', $ltType->id)
            ->where('field', 'unloading')
            ->exists();
        if ($exists) {
            return;
        }

        $options = [
            'Гидролифт'   => ['value' => 'Гидролифт', 'label' => 'Гидролифт'],
            'Манипулятор' => ['value' => 'Манипулятор', 'label' => 'Манипулятор'],
            'Ручная'      => ['value' => 'Ручная', 'label' => 'Ручная'],
            'Открытая'    => ['value' => 'Открытая', 'label' => 'Открытая'],
            'Водитель РФ' => ['value' => 'Водитель РФ', 'label' => 'Водитель РФ'],
        ];

        DB::table('data_rows')->insert([
            'data_type_id'         => $ltType->id,
            'field'                => 'unloading',
            'type'                 => 'select_dropdown',
            'title'                => 'Разгрузка',
            'required'             => 0,
            'details'              => json_encode(['options' => $options], JSON_UNESCAPED_UNICODE),
            'visible_always'       => 1,
            'label_color'          => '',
            'section_id'           => $ltSec,
            'group_id'             => null,
            'sort'                 => 52,
            'created_at'           => null,
            'updated_at'           => null,
            'button_name'          => 'Загрузить',
            'show_file_image'      => 0,
            'hide'                 => 0,
            'is_plural'            => 1,
            'roles_read'           => '',
            'roles_write'          => '',
            'is_remove'            => 0,
            'mobile_pages'         => '',
            'display_parent_name'  => null,
            'rules'                => null,
            'only_read'            => 0,
            'is_permanent'         => 1,
            'show_file_name'       => 0,
            'external_link'        => '',
            'is_external_link'     => 0,
            'module'               => '',
            'is_link'              => 0,
            'unit'                 => '',
            'module_section_id'    => null,
            'is_default'           => 0,
            'is_inactive'          => 0,
            'blocked_changes'      => 0,
            'mask'                 => null,
            'permanent_required'   => 0,
            'permanent_name'       => 0,
            'relation_table'       => null,
            'options'              => null,
            'set_color'            => 0,
            'related_field'        => null,
            'is_unique'            => 0,
            'is_program'           => 0,
            'subfields'            => null,
            'dependency_fields'    => null,
        ]);
    }

    public function down(): void
    {
        $ltType = DB::table('data_types')->where('slug', 'logistic_tasks')->first();
        if ($ltType) {
            DB::table('data_rows')
                ->where('data_type_id', $ltType->id)
                ->where('field', 'unloading')
                ->delete();
        }
    }
};
