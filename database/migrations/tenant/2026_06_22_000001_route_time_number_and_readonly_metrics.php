<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * (8455) Сущность «Маршруты» (routes) — её поля являются агрегатами маршрута и
 * редактировать их вручную не нужно:
 *  - time («Время маршрута») → type=number, unit='мин', only_read=1;
 *  - mileage / reserve_for_delivery / volume / weight → only_read=1.
 *
 * ВАЖНО: поле `time` существует ещё и в logistic_tasks («Окно») — там оно
 * остаётся текстовым с маской, поэтому правки строго ограничены data_type_id
 * сущности routes.
 *
 * Шаблон новых порталов правит парная миграция ..._in_seeds.php.
 */
return new class extends Migration
{
    private array $readonlyMetrics = ['mileage', 'reserve_for_delivery', 'volume', 'weight'];

    public function up(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        $routeTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$routeTypeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $routeTypeId)
            ->where('field', 'time')
            ->update(['type' => 'number', 'unit' => 'мин', 'only_read' => 1]);

        DB::table('data_rows')
            ->where('data_type_id', $routeTypeId)
            ->whereIn('field', $this->readonlyMetrics)
            ->update(['only_read' => 1]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        $routeTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$routeTypeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $routeTypeId)
            ->where('field', 'time')
            ->update(['type' => 'text', 'unit' => '', 'only_read' => 0]);

        DB::table('data_rows')
            ->where('data_type_id', $routeTypeId)
            ->whereIn('field', $this->readonlyMetrics)
            ->update(['only_read' => 0]);
    }
};
