<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('data_rows')
            ->where('field', 'loading_time')
            ->update(['title' => 'Начало маршрута']);

        DB::table('data_rows')
            ->where('field', 'employee_requirements')
            ->update(['title' => 'Требования к сотруднику']);

        $parseStart = function ($time) {
            if (!$time) {
                return null;
            }
            $part = explode('-', (string) $time)[0];
            if (preg_match('/(\d{1,2}):(\d{2})/', $part, $m)) {
                return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
            }
            return null;
        };

        $routes = DB::table('routes')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('loading_time')->orWhere('loading_time', '');
            })
            ->pluck('id');

        foreach ($routes as $routeId) {
            $task = DB::table('logistic_tasks')
                ->where('route_id', $routeId)
                ->whereNull('deleted_at')
                ->orderBy('sort')
                ->first(['time']);

            if (!$task) {
                continue;
            }

            $start = $parseStart($task->time);
            if ($start) {
                DB::table('routes')->where('id', $routeId)->update(['loading_time' => $start]);
            }
        }
    }

    public function down(): void
    {
        DB::table('data_rows')
            ->where('field', 'loading_time')
            ->update(['title' => 'Время загрузки']);

        DB::table('data_rows')
            ->where('field', 'employee_requirements')
            ->update(['title' => 'Требования к водителю']);
    }
};
