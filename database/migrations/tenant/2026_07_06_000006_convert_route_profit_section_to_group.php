<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types') || !Schema::hasTable('field_sections')) {
            return;
        }

        $typeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId) {
            return;
        }

        $fallbackSectionId = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'mileage')
            ->value('section_id');
        if (!$fallbackSectionId) {
            $fallbackSectionId = DB::table('field_sections')
                ->where('page', 'routes')
                ->whereNull('module')
                ->orderBy('id')
                ->value('id');
        }
        if (!$fallbackSectionId) {
            $fallbackSectionId = DB::table('field_sections')->where('page', 'routes')->orderBy('id')->value('id');
        }

        $oldSectionId = DB::table('field_sections')
            ->where('page', 'routes')
            ->where('name', 'Прибыль по маршруту')
            ->value('id');

        $reserveId = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'reserve_for_delivery')
            ->value('id');
        $mileageCostId = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'mileage_cost')
            ->value('id');

        $userCostId = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('title', 'Стоимость доставки')
            ->where('field', '!=', 'delivery_cost')
            ->orderBy('id')
            ->value('id');
        $ownCostId = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('title', 'Стоимость доставки')
            ->where('field', 'delivery_cost')
            ->value('id');

        if ($userCostId && $ownCostId) {
            DB::table('data_rows')->where('id', $ownCostId)->delete();
            $ownCostId = null;
        }
        $costId = $userCostId ?: $ownCostId;

        $memberIds = array_values(array_filter([
            $reserveId ? (int) $reserveId : null,
            $mileageCostId ? (int) $mileageCostId : null,
            $costId ? (int) $costId : null,
        ]));
        if (!count($memberIds)) {
            return;
        }

        $group = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'text_group')
            ->where('title', 'Прибыль по маршруту')
            ->first();

        if ($group) {
            $groupId = $group->id;
            $update = ['subfields' => json_encode($memberIds)];
            $groupSectionValid = $group->section_id
                && $group->section_id != $oldSectionId
                && DB::table('field_sections')->where('id', $group->section_id)->where('page', 'routes')->exists();
            if (!$groupSectionValid && $fallbackSectionId) {
                $update['section_id'] = $fallbackSectionId;
                $update['hide'] = 0;
            }
            DB::table('data_rows')->where('id', $groupId)->update($update);
        } else {
            $maxSort = (int) DB::table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('section_id', $fallbackSectionId)
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
                'section_id' => $fallbackSectionId,
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
                'subfields' => json_encode($memberIds),
                'dependency_fields' => null,
            ]);
            DB::table('data_rows')->where('id', $groupId)->update(['field' => 'route_profit_'.$groupId]);
        }

        DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('group_id', $groupId)
            ->whereNotIn('id', $memberIds)
            ->update(['group_id' => null]);

        foreach ($memberIds as $k => $fid) {
            DB::table('data_rows')->where('id', $fid)->update([
                'group_id' => $groupId,
                'sort' => $k,
            ]);
        }

        if ($oldSectionId) {
            DB::table('data_rows')
                ->where('section_id', $oldSectionId)
                ->update(['section_id' => $fallbackSectionId]);
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
            ->where('field', 'like', 'route_profit%')
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
