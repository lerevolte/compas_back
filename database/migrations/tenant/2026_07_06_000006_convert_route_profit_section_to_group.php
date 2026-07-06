<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $groupFields = ['reserve_for_delivery', 'mileage_cost', 'delivery_cost'];

    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types') || !Schema::hasTable('field_sections')) {
            return;
        }

        $typeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId) {
            return;
        }

        $targetSectionId = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'mileage')
            ->value('section_id');
        if (!$targetSectionId) {
            $targetSectionId = DB::table('field_sections')
                ->where('page', 'routes')
                ->whereNull('module')
                ->orderBy('id')
                ->value('id');
        }
        if (!$targetSectionId) {
            $targetSectionId = DB::table('field_sections')->where('page', 'routes')->orderBy('id')->value('id');
        }

        $fieldIds = [];
        foreach ($this->groupFields as $field) {
            $fid = DB::table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', $field)
                ->value('id');
            if ($fid) {
                $fieldIds[] = (int) $fid;
            }
        }
        if (!count($fieldIds)) {
            return;
        }

        $groupId = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'text_group')
            ->where('title', 'Прибыль по маршруту')
            ->value('id');

        if (!$groupId) {
            $maxSort = (int) DB::table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('section_id', $targetSectionId)
                ->max('sort');
            $groupId = DB::table('data_rows')->insertGetId([
                'data_type_id' => $typeId,
                'field' => 'route_profit',
                'type' => 'text_group',
                'title' => 'Прибыль по маршруту',
                'required' => 0,
                'details' => null,
                'visible_always' => 1,
                'label_color' => '',
                'section_id' => $targetSectionId,
                'group_id' => null,
                'sort' => $maxSort + 1,
                'created_at' => now(),
                'updated_at' => now(),
                'button_name' => 'Загрузить',
                'show_file_image' => 0,
                'hide' => 0,
                'is_plural' => 0,
                'roles_read' => '',
                'roles_write' => '',
                'is_remove' => 0,
                'mobile_pages' => '',
                'display_parent_name' => null,
                'rules' => null,
                'only_read' => 0,
                'is_permanent' => 1,
                'show_file_name' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'module' => '',
                'is_link' => 0,
                'unit' => '',
                'module_section_id' => null,
                'is_default' => 0,
                'is_inactive' => 0,
                'blocked_changes' => 0,
                'mask' => null,
                'permanent_required' => 0,
                'permanent_name' => 0,
                'relation_table' => null,
                'options' => null,
                'set_color' => 0,
                'related_field' => null,
                'is_unique' => 0,
                'is_program' => 0,
                'subfields' => json_encode($fieldIds),
                'dependency_fields' => null,
            ]);
            DB::table('data_rows')->where('id', $groupId)->update(['field' => 'route_profit_'.$groupId]);
        } else {
            DB::table('data_rows')->where('id', $groupId)->update([
                'subfields' => json_encode($fieldIds),
                'section_id' => $targetSectionId,
            ]);
        }

        foreach ($fieldIds as $k => $fid) {
            DB::table('data_rows')->where('id', $fid)->update([
                'group_id' => $groupId,
                'section_id' => $targetSectionId,
                'sort' => $k,
            ]);
        }

        $oldSectionId = DB::table('field_sections')
            ->where('page', 'routes')
            ->where('name', 'Прибыль по маршруту')
            ->value('id');
        if ($oldSectionId) {
            DB::table('data_rows')
                ->where('section_id', $oldSectionId)
                ->update(['section_id' => $targetSectionId]);
            if (Schema::hasTable('section_fields_sort')) {
                DB::table('section_fields_sort')->where('section_id', $oldSectionId)->delete();
            }
            DB::table('field_sections')->where('id', $oldSectionId)->delete();
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId) {
            return;
        }

        $groupId = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'text_group')
            ->where('title', 'Прибыль по маршруту')
            ->value('id');
        if ($groupId) {
            DB::table('data_rows')->where('group_id', $groupId)->update(['group_id' => null]);
            DB::table('data_rows')->where('id', $groupId)->delete();
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
