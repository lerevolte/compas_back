<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Добавляет поля comment (Примечание) и contact (Контактное лицо) в таблицы
 * addresses и logistic_tasks, а также соответствующие строки в data_rows.
 *
 * comment в logistic_tasks уже может существовать (добавлен миграцией
 * 2026_06_04 для Bitrix24), поэтому для него применяется проверка hasColumn.
 */
return new class extends Migration
{
    private array $newCols = ['comment', 'contact'];

    public function up(): void
    {
        // 0. service_time в addresses — снимаем NOT NULL (было integer()->default(0)) ---
        if (Schema::hasTable('addresses') && Schema::hasColumn('addresses', 'service_time')) {
            Schema::table('addresses', function (Blueprint $table) {
                $table->integer('service_time')->nullable()->default(0)->change();
            });
        }

        // 1. Колонки в таблицах --------------------------------------------
        foreach (['addresses', 'logistic_tasks'] as $tbl) {
            if (!Schema::hasTable($tbl)) continue;
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (!Schema::hasColumn($tbl, 'comment')) {
                    $table->text('comment')->nullable();
                }
                if (!Schema::hasColumn($tbl, 'contact')) {
                    $table->text('contact')->nullable();
                }
            });
        }

        // 2. data_rows для logistic_tasks ----------------------------------
        $ltType = DB::table('data_types')->where('slug', 'logistic_tasks')->first();
        if ($ltType) {
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
            if ($ltSec) {
                $this->upsertRow($ltType->id, $ltSec, 'comment', 'Примечание',  'text', 50);
                $this->upsertRow($ltType->id, $ltSec, 'contact', 'Контактное лицо', 'text', 51);
            }
        }

        // 3. data_rows для addresses ---------------------------------------
        $addrType = DB::table('data_types')->where('slug', 'addresses')->first();
        if ($addrType) {
            $addrSec = DB::table('field_sections')
                ->where('page', 'addresses')
                ->whereNull('module')
                ->orderBy('id')
                ->value('id');
            if (!$addrSec) {
                $addrSec = DB::table('field_sections')
                    ->where('page', 'addresses')
                    ->orderBy('id')
                    ->value('id');
            }
            if ($addrSec) {
                $this->upsertRow($addrType->id, $addrSec, 'comment', 'Примечание',  'text', 50);
                $this->upsertRow($addrType->id, $addrSec, 'contact', 'Контактное лицо', 'text', 51);
            }
        }
    }

    public function down(): void
    {
        // Удаляем data_rows
        foreach (['logistic_tasks', 'addresses'] as $slug) {
            $type = DB::table('data_types')->where('slug', $slug)->first();
            if ($type) {
                DB::table('data_rows')
                    ->where('data_type_id', $type->id)
                    ->whereIn('field', $this->newCols)
                    ->delete();
            }
        }

        // Колонку comment в logistic_tasks НЕ удаляем — она могла быть добавлена
        // более ранней миграцией Bitrix24 и может содержать данные.
        foreach (['addresses', 'logistic_tasks'] as $tbl) {
            if (!Schema::hasTable($tbl)) continue;
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if ($tbl === 'addresses' && Schema::hasColumn($tbl, 'comment')) {
                    $table->dropColumn('comment');
                }
                if (Schema::hasColumn($tbl, 'contact')) {
                    $table->dropColumn('contact');
                }
            });
        }
    }

    private function upsertRow(int $typeId, int $sectionId, string $field, string $title, string $type, int $sort): void
    {
        $exists = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', $field)
            ->exists();

        if (!$exists) {
            DB::table('data_rows')->insert([
                'data_type_id'         => $typeId,
                'field'                => $field,
                'type'                 => $type,
                'title'                => $title,
                'required'             => 0,
                'details'              => null,
                'visible_always'       => 1,
                'label_color'          => '',
                'section_id'           => $sectionId,
                'group_id'             => null,
                'sort'                 => $sort,
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
    }
};
