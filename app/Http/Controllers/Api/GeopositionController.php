<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GeopositionController extends Controller
{
    public function store(Request $request)
    {
        $points = $request->json()->all();
        if (!is_array($points)) {
            return response()->json(['ok' => true]);
        }

        $byTenant = [];
        foreach ($points as $point) {
            if (!is_array($point)) {
                continue;
            }
            $tenantId = $point['accountId'] ?? null;
            $userId = $point['userId'] ?? null;
            $location = $point['location'] ?? null;
            if (!$tenantId || !$userId || !is_array($location)) {
                continue;
            }
            if (!isset($location['latitude']) || !isset($location['longitude'])) {
                continue;
            }
            $byTenant[(string) $tenantId][] = $point;
        }

        foreach ($byTenant as $tenantId => $rows) {
            try {
                $tenant = Tenant::find($tenantId);
                if (!$tenant) {
                    continue;
                }
                $tenant->run(function () use ($rows) {
                    if (!Schema::hasTable('user_geopositions')) {
                        return;
                    }
                    $now = now();
                    $insert = [];
                    $latest = [];
                    foreach ($rows as $point) {
                        $location = $point['location'];
                        $userId = (int) $point['userId'];
                        if (!$userId) {
                            continue;
                        }
                        $clientTime = isset($point['timestamp']) && is_numeric($point['timestamp'])
                            ? (int) round(((float) $point['timestamp']) / 1000)
                            : null;
                        $gpsTime = isset($location['time']) && is_numeric($location['time'])
                            ? (int) round((float) $location['time'])
                            : null;
                        $insert[] = [
                            'user_id' => $userId,
                            'lat' => (float) $location['latitude'],
                            'lng' => (float) $location['longitude'],
                            'accuracy' => isset($location['accuracy']) && is_numeric($location['accuracy']) ? (float) $location['accuracy'] : null,
                            'altitude' => isset($location['altitude']) && is_numeric($location['altitude']) ? (float) $location['altitude'] : null,
                            'speed' => isset($location['speed']) && is_numeric($location['speed']) ? (float) $location['speed'] : null,
                            'heading' => isset($location['heading']) && is_numeric($location['heading']) ? (float) $location['heading'] : null,
                            'provider' => isset($location['provider']) ? mb_substr((string) $location['provider'], 0, 32) : null,
                            'is_mock' => isset($location['isMock']) ? (int) ((bool) $location['isMock']) : null,
                            'satellites' => isset($location['satelliteNumber']) && is_numeric($location['satelliteNumber']) ? (int) $location['satelliteNumber'] : null,
                            'gps_time' => $gpsTime,
                            'client_time' => $clientTime,
                            'created_at' => $now,
                        ];
                        $pointTime = $clientTime ?: $gpsTime ?: ($now->getTimestamp() * 1000);
                        if (!isset($latest[$userId]) || $pointTime >= $latest[$userId]['time']) {
                            $latest[$userId] = [
                                'lat' => (float) $location['latitude'],
                                'lng' => (float) $location['longitude'],
                                'accuracy' => isset($location['accuracy']) && is_numeric($location['accuracy']) ? (float) $location['accuracy'] : null,
                                'time' => $pointTime,
                            ];
                        }
                    }
                    if (count($insert)) {
                        DB::table('user_geopositions')->insert($insert);
                    }
                    if (count($latest) && Schema::hasColumn('users', 'geoposition')) {
                        foreach ($latest as $userId => $value) {
                            DB::table('users')->where('id', $userId)->update([
                                'geoposition' => json_encode($value),
                            ]);
                        }
                    }
                });
            } catch (\Throwable $e) {
                Log::warning('set_geoposition failed: '.$e->getMessage(), ['tenant' => $tenantId]);
            }
        }

        return response()->json(['ok' => true]);
    }
}
