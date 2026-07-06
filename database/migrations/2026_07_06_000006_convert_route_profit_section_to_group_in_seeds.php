<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $groupFields = ['reserve_for_delivery', 'mileage_cost', 'delivery_cost'];

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

        $targetSectionId = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'mileage')
            ->value('section_id');
        if (!$targetSectionId) {
            $targetSectionId = $db->table('field_sections')
                ->where('page', 'routes')
                ->whereNull('module')
                ->orderBy('id')
                ->value('id');
        }
        if (!$targetSectionId) {
            $targetSectionId = $db->table('field_sections')->where('page', 'routes')->orderBy('id')->value('id');
        }

        $fieldIds = [];
        foreach ($this->groupFields as $field) {
            $fid = $db->table('data_rows')
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

        $groupId = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'text_group')
            ->where('title', 'Прибыль по маршруту')
            ->value('id');

        if (!$groupId) {
            $maxSort = (int) $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('section_id', $targetSectionId)
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
            $db->table('data_rows')->where('id', $groupId)->update(['field' => 'route_profit_'.$groupId]);
        } else {
            $db->table('data_rows')->where('id', $groupId)->update([
                'subfields' => json_encode($fieldIds),
                'section_id' => $targetSectionId,
            ]);
        }

        foreach ($fieldIds as $k => $fid) {
            $db->table('data_rows')->where('id', $fid)->update([
                'group_id' => $groupId,
                'section_id' => $targetSectionId,
                'sort' => $k,
            ]);
        }

        $oldSectionId = $db->table('field_sections')
            ->where('page', 'routes')
            ->where('name', 'Прибыль по маршруту')
            ->value('id');
        if ($oldSectionId) {
            $db->table('data_rows')
                ->where('section_id', $oldSectionId)
                ->update(['section_id' => $targetSectionId]);
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
            ->value('id');
        if ($groupId) {
            $db->table('data_rows')->where('group_id', $groupId)->update(['group_id' => null]);
            $db->table('data_rows')->where('id', $groupId)->delete();
        }
    }
};
