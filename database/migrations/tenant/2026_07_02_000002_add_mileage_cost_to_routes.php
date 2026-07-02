<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('routes')) {
            return;
        }

        if (!Schema::hasColumn('routes', 'mileage_cost')) {
            Schema::table('routes', function (Blueprint $table) {
                $table->string('mileage_cost')->nullable();
            });
        }

        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $routesTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
            if ($routesTypeId) {
                $exists = DB::table('data_rows')
                    ->where('data_type_id', $routesTypeId)
                    ->where('field', 'mileage_cost')
                    ->exists();
                if (!$exists) {
                    $src = DB::table('data_rows')
                        ->where('data_type_id', $routesTypeId)
                        ->where('field', 'mileage')
                        ->first();

                    $row = $src ? (array) $src : ['data_type_id' => $routesTypeId, 'required' => 0, 'sort' => 100];
                    unset($row['id']);
                    $row['data_type_id'] = $routesTypeId;
                    $row['field'] = 'mileage_cost';
                    $row['title'] = 'Общий расход за километраж';
                    $row['type'] = 'number';
                    $row['unit'] = 'руб';
                    $row['only_read'] = 1;
                    if ($src && $src->sort !== null) {
                        $row['sort'] = $src->sort + 1;
                    }
                    $row['created_at'] = now();
                    $row['updated_at'] = now();

                    DB::table('data_rows')->insert($row);
                }
            }
        }

        if (Schema::hasTable('cars')) {
            $toNum = fn ($v) => (float) str_replace(',', '.', trim((string) $v));
            foreach (DB::table('routes')->whereNotNull('car_id')->cursor() as $route) {
                $car = DB::table('cars')->where('id', $route->car_id)->first();
                $price = $car ? $toNum($car->price_per_km) : 0;
                if (!$price) {
                    continue;
                }
                $cost = round($price * $toNum($route->mileage), 2);
                DB::table('routes')->where('id', $route->id)->update(['mileage_cost' => $cost]);
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $routesTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
            if ($routesTypeId) {
                DB::table('data_rows')
                    ->where('data_type_id', $routesTypeId)
                    ->where('field', 'mileage_cost')
                    ->delete();
            }
        }

        if (Schema::hasTable('routes') && Schema::hasColumn('routes', 'mileage_cost')) {
            Schema::table('routes', function (Blueprint $table) {
                $table->dropColumn('mileage_cost');
            });
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
