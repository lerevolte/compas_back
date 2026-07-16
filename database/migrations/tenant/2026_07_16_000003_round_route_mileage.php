<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('routes') || !Schema::hasColumn('routes', 'mileage')) {
            return;
        }

        $rows = DB::table('routes')
            ->whereNotNull('mileage')
            ->where('mileage', '!=', '')
            ->get(['id', 'mileage']);

        $hasCars = Schema::hasTable('cars');
        $hasCost = Schema::hasColumn('routes', 'mileage_cost');
        $toNum = fn ($v) => (float) str_replace(',', '.', trim((string) $v));

        foreach ($rows as $row) {
            $raw = str_replace(',', '.', trim((string) $row->mileage));
            if (!is_numeric($raw)) {
                continue;
            }
            $rounded = (string) (int) round((float) $raw);
            if ($rounded === (string) $row->mileage) {
                continue;
            }
            $update = ['mileage' => $rounded];
            if ($hasCars && $hasCost) {
                $route = DB::table('routes')->where('id', $row->id)->first();
                if ($route && $route->car_id) {
                    $price = $toNum(DB::table('cars')->where('id', $route->car_id)->value('price_per_km'));
                    if ($price) {
                        $update['mileage_cost'] = round($price * (float) $rounded, 2);
                    }
                }
            }
            DB::table('routes')->where('id', $row->id)->update($update);
        }
    }

    public function down(): void
    {
    }
};
