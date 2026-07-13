<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guides')) {
            return;
        }

        if (!Schema::hasColumn('guides', 'sort')) {
            Schema::table('guides', function (Blueprint $table) {
                $table->integer('sort')->nullable()->default(0);
            });
            DB::table('guides')->update(['sort' => DB::raw('id')]);
        }

        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeId = DB::table('data_types')->where('slug', 'guides')->value('id');
        if (!$typeId) {
            return;
        }

        $exists = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'sort')
            ->exists();
        if ($exists) {
            return;
        }

        $sectionId = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'is_active')
            ->value('section_id');
        if (!$sectionId) {
            $sectionId = DB::table('data_rows')
                ->where('data_type_id', $typeId)
                ->whereNotNull('section_id')
                ->orderBy('id')
                ->value('section_id');
        }

        $maxSort = (int) DB::table('data_rows')->where('data_type_id', $typeId)->max('sort');

        $row = [
            'data_type_id' => $typeId,
            'field' => 'sort',
            'type' => 'number',
            'title' => 'Сортировка',
            'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $sectionId, 'group_id' => null, 'sort' => $maxSort + 1,
            'created_at' => now(), 'updated_at' => now(), 'button_name' => 'Загрузить',
            'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0, 'roles_read' => '',
            'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
            'rules' => null, 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'options' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null,
        ];

        $columns = Schema::getColumnListing('data_rows');
        DB::table('data_rows')->insert(array_intersect_key($row, array_flip($columns)));

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $typeId = DB::table('data_types')->where('slug', 'guides')->value('id');
            if ($typeId) {
                DB::table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('field', 'sort')
                    ->delete();
            }
        }

        if (Schema::hasTable('guides') && Schema::hasColumn('guides', 'sort')) {
            Schema::table('guides', function (Blueprint $table) {
                $table->dropColumn('sort');
            });
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
