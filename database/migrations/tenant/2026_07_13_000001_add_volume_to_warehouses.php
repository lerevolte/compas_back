<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouses') && !Schema::hasColumn('warehouses', 'volume')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->text('volume')->nullable();
            });
        }

        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeId = DB::table('data_types')->where('slug', 'warehouses')->value('id');
        if (!$typeId) {
            return;
        }

        $exists = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'volume')
            ->exists();
        if ($exists) {
            return;
        }

        $sectionId = DB::table('field_sections')
            ->where('page', 'warehouses')
            ->whereNull('module')
            ->orderBy('id')
            ->value('id');
        if (!$sectionId) {
            $sectionId = DB::table('field_sections')->where('page', 'warehouses')->orderBy('id')->value('id');
        }

        $srcTypeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');
        $src = $srcTypeId
            ? DB::table('data_rows')->where('data_type_id', $srcTypeId)->where('field', 'volume')->first()
            : null;

        if ($src) {
            $row = (array) $src;
            unset($row['id']);
            $row['data_type_id'] = $typeId;
            $row['section_id'] = $sectionId;
            $row['module_section_id'] = null;
            $row['only_read'] = 0;
        } else {
            $maxSort = (int) DB::table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $row = [
                'data_type_id' => $typeId,
                'field' => 'volume',
                'type' => 'number',
                'title' => 'Объем',
                'unit' => 'л',
                'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
                'section_id' => $sectionId, 'group_id' => null, 'sort' => $maxSort + 1,
                'created_at' => now(), 'updated_at' => now(), 'button_name' => 'Загрузить',
                'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0, 'roles_read' => '',
                'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
                'rules' => null, 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
                'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
                'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
                'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
                'relation_table' => null, 'options' => null, 'set_color' => 0, 'related_field' => null,
                'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null,
            ];
        }

        DB::table('data_rows')->insert($row);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $typeId = DB::table('data_types')->where('slug', 'warehouses')->value('id');
            if ($typeId) {
                DB::table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('field', 'volume')
                    ->delete();
            }
        }

        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'volume')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('volume');
            });
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
