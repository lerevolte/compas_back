<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Car;
use Carbon\Carbon;
use DB;

class FetchGpsData extends Command
{
    protected $signature = 'gps:fetch';
    protected $description = 'Fetch GPS data from Pilot GPS and save to car_points';


    public function handle()
    {
  
        $tenantId = 'opt6';
        
     
        // $integration = DB::table('integrations')->where('tenant_id', $tenantId)->where('type', 'pilot_gps')->first();
        // $login = $integration->login;
        // $password = $integration->password;
        // $node = $integration->node;
        
        // Пока хардкод для opt6
        $login = 'buh@opt6.ru';
        $password = '89296977709';
        $node = 9;

        if (!\Schema::hasColumn('cars', 'imei')) {
            $this->info('Column imei not found in cars table, skipping');
            return;
        }
        // Получаем машины с imei для этого тенанта
        $cars = Car::whereNotNull('imei')
            ->where('imei', '!=', '')
            ->get()
            ->keyBy('imei');

        if ($cars->isEmpty()) {
            $this->info('No cars with IMEI found');
            return;
        }

        $client = new Client();

        try {
            $response = $client->get("https://blade.pilot-gps.com/api/api.php?cmd=list&node={$node}", [
                'auth' => [$login, $password],
                'timeout' => 15
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['list']) || empty($data['list'])) {
                $this->info('No GPS data received');
                return;
            }

            $now = Carbon::now();
            $carUpdates = [];
            $pointInserts = [];

            foreach ($data['list'] as $item) {
                $imei = $item['imei'] ?? null;
                if (!$imei || !isset($cars[$imei])) continue;

                $car = $cars[$imei];
                $lat = $item['status']['lat'] ?? null;
                $lon = $item['status']['lon'] ?? null;
                $speed = round($item['status']['speed'] ?? 0);

                if (!$lat || !$lon) continue;

                // Save point
                $pointInserts[] = [
                    'car_id' => $car->id,
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'speed' => $speed,
                    'time' => $now,
                    'date' => $now->format('d.m.Y'),
                ];

                // Update car position
                $carUpdates[] = [
                    'id' => $car->id,
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'geo_updated_at' => $now,
                ];

                // Check if car is near any route task — update fact_time
                $this->checkFactTime($car->id, $lat, $lon, $now);
            }

            if (!empty($pointInserts)) {
                DB::table('car_points')->insert($pointInserts);
                $this->info('Inserted ' . count($pointInserts) . ' GPS points');
            }

            if (!empty($carUpdates)) {
                DB::table('cars')->upsert($carUpdates, 'id', ['latitude', 'longitude', 'geo_updated_at']);
            }

        } catch (\Exception $e) {
            $this->error('GPS fetch failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if car entered service radius of any task on today's routes
     * If so — set fact_time on the task (first entry only)
     */
    protected function checkFactTime($carId, $lat, $lon, Carbon $now)
    {
        $today = $now->format('Y-m-d');
        $radius = 500; // meters — service radius

        // Find today's routes for this car
        $routes = DB::table('routes')
            ->where('car_id', $carId)
            ->where('date', $today)
            ->pluck('id');

        if ($routes->isEmpty()) return;

        // Get tasks on these routes that don't have fact_time yet
        $tasks = DB::table('logistic_tasks')
            ->whereIn('route_id', $routes)
            ->whereNull('fact_time')
            ->get(['id', 'address']);

        foreach ($tasks as $task) {
            $address = json_decode($task->address, true);
            if (!$address || !isset($address['coords']) || count($address['coords']) < 2) continue;

            $taskLat = (float) $address['coords'][0];
            $taskLon = (float) $address['coords'][1];

            $distance = $this->haversineDistance($lat, $lon, $taskLat, $taskLon);

            if ($distance <= $radius) {
                DB::table('logistic_tasks')
                    ->where('id', $task->id)
                    ->whereNull('fact_time')
                    ->update(['fact_time' => $now]);

                $this->info("Task {$task->id}: fact_time set to {$now}");
            }
        }
    }

    /**
     * Calculate distance between two points in meters (Haversine formula)
     */
    protected function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371000; // Earth radius in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}