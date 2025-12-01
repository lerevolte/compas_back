<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Task;
use App\Models\FactRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\RouteAnalysisService;

class MapController extends Controller
{
    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function show(Request $request)
    {
        $radius = 500;
        $date = $request->date;
        try {
            $validatedDate = Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            abort(404, 'Неверный формат даты.');
        }

        $routesFromDb = Route::where('date', $validatedDate)
                             ->with(['tasks', 'tasks.status'])
                             ->get();
        
        $routesForJs = $routesFromDb->map(function ($route) use ($validatedDate, $radius) {

            $actualPathData = [];
            if ($route->employee_id) {
                $factDate = Carbon::parse($validatedDate)->format('d.m.Y');

                // Теперь получаем не только координаты, но и скорость со временем
                $actualPathData = FactRoute::where('user_id', $route->employee_id)
                    ->where('date', $factDate)
                    ->orderBy('time', 'asc')
                    ->get(['latitude', 'longitude', 'speed', 'time']) // Получаем нужные поля
                    ->map(function ($point) {
                        // Форматируем в удобный объект
                        return [
                            'lat'   => (float)$point->latitude,
                            'lon'   => (float)$point->longitude,
                            'speed' => (float)$point->speed,
                            'time'  => Carbon::parse($point->time)->format('H:i'),
                        ];
                    })
                    ->toArray();
            }

            $tasksForJs = $route->tasks->map(function ($task, $key) {
                $taskName = mb_convert_encoding($task->name, 'UTF-8', 'UTF-8');
                $addressJson = mb_convert_encoding($task->address, 'UTF-8', 'UTF-8');

                if ($this->isJson($taskName)) {
                    $decodedName = json_decode($taskName, true);
                    $taskName = $decodedName['value'] ?? 'Без названия';
                }

                return [
                    'id' => $task->id,
                    'order' => $key + 1,//$task->order, 
                    'name' => $taskName,
                    'address' => $task->address, // Это уже JSON-строка
                    'factTime' => $task->fact_time ?? '', // Если есть фактическое время
                    'statusColor' => $task->status->color ?? '#ccc',
                    'service_time' => $task->service_time ?? 0
                ];
            });
            $allTasksForRoute = $tasksForJs;
            $analysisService = new RouteAnalysisService($allTasksForRoute->toArray(), $radius);
            $analysisResult = $analysisService->analyze($route->employee_id, Carbon::parse($validatedDate)->format('d.m.Y'));

            return [
                'id' => $route->id,
                'name' => $route->name,
                'loading_time' => $route->loading_time ?? '7:00',
                'color' => $route->color ?? '#8601ff',
                'tasks' => $tasksForJs,
                'actual_path' => $actualPathData,
                'service_stops' => $analysisResult['serviceStops'],
                'parking_stops' => $analysisResult['parkingStops'],
            ];
        });
        
        $unassignedTasksFromDb = Task::whereNull('route_id')
                                     ->where('delivery_date', $validatedDate)
                                     ->with('status')
                                     ->get();

        $unassignedTasksForJs = $unassignedTasksFromDb->map(function ($task) {
            $taskName = $task->name;
            if ($this->isJson($taskName)) {
                $decodedName = json_decode($taskName, true);
                $taskName = $decodedName['value'] ?? 'Без названия';
            }
            
            // Убедитесь, что у задачи есть поля planned_time и fact_time
            return [
                'id' => $task->id,
                'name' => $taskName,
                'address' => $task->address,
                'planned_time' => $task->planned_time ? Carbon::parse($task->planned_time)->format('H:i') : null,
                'fact_time' => $task->fact_time ? Carbon::parse($task->fact_time)->format('H:i') : null,
                'statusColor' => $task->status->color ?? '#ccc',
            ];
        });

        return view('leaflet.show', [
            'routes' => $routesForJs,
            'unassignedTasks' => $unassignedTasksForJs,
            'radius' => $radius
        ]);
    }
}