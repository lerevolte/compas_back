<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Парная к tenant/..._route_time_number_and_readonly_metrics.php: тот же апдейт
 * в базе-шаблоне (connection: seeds), чтобы у НОВЫХ регистрируемых порталов
 * сущность routes сразу имела нужные настройки полей (8455).
 */
return new class extends Migration
{
    private array $readonlyMetrics = ['mileage', 'reserve_for_delivery', 'volume', 'weight'];

    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }

        $routeTypeId = DB::connection('seeds')->table('data_types')->where('slug', 'routes')->value('id');
        if (!$routeTypeId) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->where('data_type_id', $routeTypeId)
            ->where('field', 'time')
            ->update(['type' => 'number', 'unit' => 'мин', 'only_read' => 1]);

        DB::connection('seeds')->table('data_rows')
            ->where('data_type_id', $routeTypeId)
            ->whereIn('field', $this->readonlyMetrics)
            ->update(['only_read' => 1]);
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }

        $routeTypeId = DB::connection('seeds')->table('data_types')->where('slug', 'routes')->value('id');
        if (!$routeTypeId) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->where('data_type_id', $routeTypeId)
            ->where('field', 'time')
            ->update(['type' => 'text', 'unit' => '', 'only_read' => 0]);

        DB::connection('seeds')->table('data_rows')
            ->where('data_type_id', $routeTypeId)
            ->whereIn('field', $this->readonlyMetrics)
            ->update(['only_read' => 0]);
    }
};
