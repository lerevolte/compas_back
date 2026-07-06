<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if (!$schema->hasTable('routes') || !$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }

        $typeId = $db->table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId) {
            return;
        }

        $group = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'text_group')
            ->where('title', 'Прибыль по маршруту')
            ->first();
        if (!$group) {
            return;
        }

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

        if (count($memberIds)) {
            $db->table('data_rows')->where('id', $group->id)->update(['subfields' => json_encode($memberIds)]);
            $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('group_id', $group->id)
                ->whereNotIn('id', $memberIds)
                ->update(['group_id' => null]);
            foreach ($memberIds as $k => $fid) {
                $db->table('data_rows')->where('id', $fid)->update([
                    'group_id' => $group->id,
                    'sort' => $k,
                ]);
            }
        }

        $columnFields = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('group_id', $group->id)
            ->pluck('field')
            ->filter()
            ->push($group->field)
            ->filter()
            ->unique()
            ->all();

        foreach ($columnFields as $columnField) {
            if (!$schema->hasColumn('routes', $columnField)) {
                $schema->table('routes', function (Blueprint $table) use ($columnField) {
                    $table->text($columnField)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
    }
};
