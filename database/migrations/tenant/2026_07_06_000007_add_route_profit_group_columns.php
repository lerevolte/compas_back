<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('routes') || !Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId) {
            return;
        }

        $group = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'text_group')
            ->where('title', 'Прибыль по маршруту')
            ->first();
        if (!$group) {
            return;
        }

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

        if (count($memberIds)) {
            DB::table('data_rows')->where('id', $group->id)->update(['subfields' => json_encode($memberIds)]);
            DB::table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('group_id', $group->id)
                ->whereNotIn('id', $memberIds)
                ->update(['group_id' => null]);
            foreach ($memberIds as $k => $fid) {
                DB::table('data_rows')->where('id', $fid)->update([
                    'group_id' => $group->id,
                    'sort' => $k,
                ]);
            }
        }

        $columnFields = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('group_id', $group->id)
            ->pluck('field')
            ->filter()
            ->push($group->field)
            ->filter()
            ->unique()
            ->all();

        foreach ($columnFields as $columnField) {
            if (!Schema::hasColumn('routes', $columnField)) {
                Schema::table('routes', function (Blueprint $table) use ($columnField) {
                    $table->text($columnField)->nullable();
                });
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
    }
};
