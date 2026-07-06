<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types') || !$schema->hasTable('field_sections')) {
            return;
        }

        $typeId = $db->table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId) {
            return;
        }

        $fallbackSectionId = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'mileage')
            ->value('section_id');
        if (!$fallbackSectionId) {
            $fallbackSectionId = $db->table('field_sections')
                ->where('page', 'routes')
                ->whereNull('module')
                ->orderBy('id')
                ->value('id');
        }
        if (!$fallbackSectionId) {
            $fallbackSectionId = $db->table('field_sections')->where('page', 'routes')->orderBy('id')->value('id');
        }

        $oldSectionId = $db->table('field_sections')
            ->where('page', 'routes')
            ->where('name', 'Прибыль по маршруту')
            ->value('id');

        $reserveId = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'reserve_for_delivery')
            ->value('id');
        $mileageCostId = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'mileage_cost')
            ->value('id');

        $userCostId = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('title', 'Стоимость доставки')
            ->where('field', '!=', 'delivery_cost')
            ->orderBy('id')
            ->value('id');
        $ownCostId = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('title', 'Стоимость доставки')
            ->where('field', 'delivery_cost')
            ->value('id');

        if ($userCostId && $ownCostId) {
            $db->table('data_rows')->where('id', $ownCostId)->delete();
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

        $group = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'text_group')
            ->where('title', 'Прибыль по маршруту')
            ->first();

        if ($group) {
            $groupId = $group->id;
            $update = ['subfields' => json_encode($memberIds)];
            $groupSectionValid = $group->section_id
                && $group->section_id != $oldSectionId
                && $db->table('field_sections')->where('id', $group->section_id)->where('page', 'routes')->exists();
            if (!$groupSectionValid && $fallbackSectionId) {
                $update['section_id'] = $fallbackSectionId;
                $update['hide'] = 0;
            }
            $db->table('data_rows')->where('id', $groupId)->update($update);
        } else {
            $maxSort = (int) $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('section_id', $fallbackSectionId)
                ->max('sort');
            $groupId = $db->table('data_rows')->insertGetId([
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
            $db->table('data_rows')->where('id', $groupId)->update(['field' => 'route_profit_'.$groupId]);
        }

        $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('group_id', $groupId)
            ->whereNotIn('id', $memberIds)
            ->update(['group_id' => null]);

        foreach ($memberIds as $k => $fid) {
            $db->table('data_rows')->where('id', $fid)->update([
                'group_id' => $groupId,
                'sort' => $k,
            ]);
        }

        if ($oldSectionId) {
            $db->table('data_rows')
                ->where('section_id', $oldSectionId)
                ->update(['section_id' => $fallbackSectionId]);
            if ($schema->hasTable('section_fields_sort')) {
                $db->table('section_fields_sort')->where('section_id', $oldSectionId)->delete();
            }
            $db->table('field_sections')->where('id', $oldSectionId)->delete();
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }

        $typeId = $db->table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId) {
            return;
        }

        $groupId = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'text_group')
            ->where('title', 'Прибыль по маршруту')
            ->where('field', 'like', 'route_profit%')
            ->value('id');
        if ($groupId) {
            $db->table('data_rows')->where('group_id', $groupId)->update(['group_id' => null]);
            $db->table('data_rows')->where('id', $groupId)->delete();
        }
    }
};
