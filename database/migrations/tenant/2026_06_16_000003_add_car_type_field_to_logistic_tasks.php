<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('logistic_tasks')) {
            return;
        }
        if (!Schema::hasColumn('logistic_tasks', 'car_type')) {
            Schema::table('logistic_tasks', function (Blueprint $table) {
                $table->integer('car_type')->nullable();
            });
        }

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
            ->where('field', 'car_type')
            ->exists();
        if ($exists) {
            return;
        }

        $options = [
            2712 => ['value' => 2712, 'label' => 'ТК, СДЭК'],
            2713 => ['value' => 2713, 'label' => 'ТК, Boxberry'],
            2714 => ['value' => 2714, 'label' => 'ТК, Почта России'],
            2715 => ['value' => 2715, 'label' => 'ТК, ПЭК'],
            2716 => ['value' => 2716, 'label' => 'ТК, Другая'],
        ];

        DB::table('data_rows')->insert([
            'data_type_id'         => $ltType->id,
            'field'                => 'car_type',
            'type'                 => 'select_dropdown',
            'title'                => 'Тип ТК',
            'required'             => 0,
            'details'              => json_encode(['options' => $options], JSON_UNESCAPED_UNICODE),
            'visible_always'       => 1,
            'label_color'          => '',
            'section_id'           => $ltSec,
            'group_id'             => null,
            'sort'                 => 53,
            'created_at'           => null,
            'updated_at'           => null,
            'button_name'          => 'Загрузить',
            'show_file_image'      => 0,
            'hide'                 => 0,
            'is_plural'            => 0,
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
                ->where('field', 'car_type')
                ->delete();
        }
        if (Schema::hasTable('logistic_tasks') && Schema::hasColumn('logistic_tasks', 'car_type')) {
            Schema::table('logistic_tasks', function (Blueprint $table) {
                $table->dropColumn('car_type');
            });
        }
    }
};
