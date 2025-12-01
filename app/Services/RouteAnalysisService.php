<?php

namespace App\Services;

use App\Models\FactRoute;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RouteAnalysisService
{
    private $plannedTasksLocations;
    private $serviceRadius;

    /** @var int Максимальный временной разрыв в минутах, чтобы считать остановки одним событием. */
    private const MAX_TIME_GAP_MINUTES = 15;

    /** @var int Максимальное расстояние в метрах, чтобы считать остановки одним событием. */
    private const MAX_DISTANCE_METERS = 500;

    public function __construct(array $plannedTasks, int $radius)
    {
        $this->serviceRadius = $radius;
        $this->plannedTasksLocations = collect($plannedTasks)->mapWithKeys(function ($task) {
            if (isset($task['address']) && !is_null($task['address'])) {
                $decoded = json_decode($task['address'], true);
                if (isset($decoded['coords'])) {
                    return [$task['id'] => ['lat' => (float)$decoded['coords'][0], 'lon' => (float)$decoded['coords'][1]]];
                }
            }
            return [];
        })->filter();
    }
    

    public function analyze(int $employeeId, string $dateForFactRoute)
    {
        $points = FactRoute::where('user_id', $employeeId)
            ->where('date', $dateForFactRoute)
            ->orderBy('time', 'asc')
            ->get();

        // if ($points->count() < 2) {
        //     return ['serviceStops' => [], 'parkingStops' => [], 'signalLossEvents' => []];
        // }

        $rawStops = $this->findRawStops($points);
        $groupedStops = $this->groupNearbyStops($rawStops);
        $classifiedStops = $this->classifyStops($groupedStops);
        $signalLossEvents = $this->findSignalLossEvents($points);
        
        return [
            'serviceStops' => $classifiedStops['serviceStops'],
            'parkingStops' => $classifiedStops['parkingStops'],
            'signalLossEvents' => $signalLossEvents,
        ];
    }

    private function findSignalLossEvents(Collection $points): array
    {
        $lossEvents = [];
        if ($points->count() < 2) {
            return [];
        }

        for ($i = 0; $i < $points->count() - 1; $i++) {
            $p1 = $points[$i];
            $p2 = $points[$i + 1];

            $timeDiff = Carbon::parse($p1->time)->diffInMinutes($p2->time);

            if ($timeDiff > 5) {
                $lossEvents[] = [
                    'loss_point' => [
                        'lat' => (float)$p1->latitude,
                        'lon' => (float)$p1->longitude,
                        'time' => Carbon::parse($p1->time)->format('H:i'),
                    ],
                    'restore_point' => [
                        'lat' => (float)$p2->latitude,
                        'lon' => (float)$p2->longitude,
                        'time' => Carbon::parse($p2->time)->format('H:i'),
                    ],
                    'duration' => $timeDiff,
                ];
            }
        }
        return $lossEvents;
    }

    private function findRawStops(Collection $points): array
    {
        $stops = [];
        $currentStopPoints = [];

        for ($i = 0; $i < $points->count(); $i++) {
            $point = $points[$i];
            $speedKmh = (float)($point->speed ?? 0) * 1.60934;

            if ($speedKmh < 10) {
                $currentStopPoints[] = $point;
            } else {
                if (count($currentStopPoints) > 0) {
                    $this->finalizeStop($currentStopPoints, $stops);
                    $currentStopPoints = [];
                }
            }
        }
        
        if (count($currentStopPoints) > 0) {
            $this->finalizeStop($currentStopPoints, $stops);
        }

        return $stops;
    }
    
    private function finalizeStop(array $stopPoints, array &$stops)
    {
        $startPoint = reset($stopPoints);
        $endPoint = end($stopPoints);
        $duration = Carbon::parse($startPoint->time)->diffInMinutes($endPoint->time);

        if ($duration >= 5) {
            $avgCoords = $this->getAverageCoordinates($stopPoints);
            $stops[] = [
                'start_time_obj' => Carbon::parse($startPoint->time),
                'end_time_obj' => Carbon::parse($endPoint->time),
                'duration' => $duration,
                'lat' => $avgCoords['lat'],
                'lon' => $avgCoords['lon'],
            ];
        }
    }

    private function groupNearbyStops(array $stops): array
    {
        if (count($stops) < 2) {
            return $stops;
        }

        // Сортируем на случай, если порядок нарушен
        usort($stops, fn($a, $b) => $a['start_time_obj'] <=> $b['start_time_obj']);
        
        $groupedStops = [];
        $currentGroup = array_shift($stops);

        foreach ($stops as $nextStop) {
            $timeDiff = $currentGroup['end_time_obj']->diffInMinutes($nextStop['start_time_obj']);
            $distance = $this->getDistance($currentGroup['lat'], $currentGroup['lon'], $nextStop['lat'], $nextStop['lon']);
            
            // Если остановки близки по времени и расстоянию - объединяем
            if ($timeDiff <= self::MAX_TIME_GAP_MINUTES && $distance <= self::MAX_DISTANCE_METERS) {
                // Расширяем временной диапазон текущей группы
                $currentGroup['end_time_obj'] = $nextStop['end_time_obj'];
            } else {
                // Иначе - завершаем текущую группу и начинаем новую
                $groupedStops[] = $currentGroup;
                $currentGroup = $nextStop;
            }
        }
        // Добавляем последнюю группу
        $groupedStops[] = $currentGroup;

        // Пересчитываем длительность и форматируем время для каждой группы
        return array_map(function ($group) {
            $group['duration'] = $group['start_time_obj']->diffInMinutes($group['end_time_obj']);
            $group['start_time'] = $group['start_time_obj']->format('H:i');
            $group['end_time'] = $group['end_time_obj']->format('H:i');
            unset($group['start_time_obj'], $group['end_time_obj']); // Удаляем вспомогательные поля
            return $group;
        }, $groupedStops);
    }

    private function classifyStops(array $stops): array
    {
        $serviceStops = [];
        $parkingStops = [];

        foreach ($stops as $stop) {
            if ($stop['duration'] < 5) continue; // Пропускаем короткие остановки после объединения

            $isServiceStop = false;
            foreach ($this->plannedTasksLocations as $taskId => $taskLocation) {
                if ($this->getDistance($stop['lat'], $stop['lon'], $taskLocation['lat'], $taskLocation['lon']) <= $this->serviceRadius) {
                    $stop['related_task_id'] = $taskId;
                    $serviceStops[] = $stop;
                    $isServiceStop = true;
                    break;
                }
            }
            if (!$isServiceStop) {
                $parkingStops[] = $stop;
            }
        }
        return ['serviceStops' => $serviceStops, 'parkingStops' => $parkingStops];
    }
    
    private function getAverageCoordinates($points)
    {
        $latSum = 0; $lonSum = 0; $count = count($points);
        if ($count == 0) return ['lat' => 0, 'lon' => 0];
        foreach ($points as $point) {
            $latSum += $point->latitude;
            $lonSum += $point->longitude;
        }
        return ['lat' => $latSum / $count, 'lon' => $lonSum / $count];
    }

    private function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        return ($dist * 60 * 1.1515 * 1.609344) * 1000; // в метрах
    }

}