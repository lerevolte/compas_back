<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\YandexRouteLog;

class MapController extends Controller
{
    public function logRoute(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'event'    => 'required|string|in:script_ok,script_fail,route_ok,route_fail',
                'api_key'  => 'nullable|string|max:64',
                'page'     => 'nullable|string|max:255',
                'address'  => 'nullable|string|max:512',
                'distance' => 'nullable|string|max:64',
            ]);

            if ($validator->fails()) {
                return response()->json(['ok' => false], 422);
            }

            $data = $validator->validated();

            YandexRouteLog::create([
                'ip'         => $request->ip(),
                'api_key'    => $data['api_key'] ?? null,
                'event'      => $data['event'],
                'page'       => $data['page'] ?? null,
                'address'    => $data['address'] ?? null,
                'distance'   => $data['distance'] ?? null,
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }

    public function suggest(Request $request)
    {
        $dadata = new \App\Services\Dadata('1aae835b4ef406e670f2fed34e0e1f44a7a2fc46', '12b85f4474f0fab219a2307f13a33c05f8418355');
        $dadata->init();

        $fields = array("query" => $request->address, "count" => 5);
        if($request->restrict) {
            $restrict = array(
                'from_bound' => ['value' => $request->restrict],
                'to_bound' => ['value' => $request->restrict]
            );
            $fields = array_merge($fields, $restrict);
            $countries = array(
                'locations' => [
                    'country' => '*'
                ]
            );
            $fields = array_merge($fields, $countries);
        }
        $result = $dadata->suggest("address", $fields);

        $dadata->close();


        $data = array();
        if(isset($result['suggestions'])) {
            foreach($result['suggestions'] as $item) {
                if($request->format == 'list') {
                    $data[] = array(
                        'label' => ['text' => $item['value']],
                        'value' => $item['value']
                    );
                } else {
                    $data[] = $item['value'];
                }
                
            }
        }

        return response()->json($data);
    }

    public function geocode(Request $request)
    {
        $dadata = new \App\Services\Dadata('1aae835b4ef406e670f2fed34e0e1f44a7a2fc46', '12b85f4474f0fab219a2307f13a33c05f8418355');
        $dadata->init();
        
        $address = $request->address;
        $data = array();
        
        $coordPattern = '/^[\s]*(-?\d+\.?\d*)[,\s]+(-?\d+\.?\d*)[\s]*$/';
        if (preg_match($coordPattern, $address, $matches)) {
            $lat = floatval($matches[1]);
            $lon = floatval($matches[2]);
            
            $result = $dadata->geolocate($lat, $lon, 5, 1);

            if (isset($result['suggestions'])) {
                foreach ($result['suggestions'] as $item) {
                    $isHouse = !empty($item['data']['house']);
                    $data[] = array(
                        'text' => $isHouse ? $item['value'] : $lat.', '.$lon,
                        'coords' => $isHouse
                            ? array($item['data']['geo_lat'], $item['data']['geo_lon'])
                            : array($lat, $lon)
                    );
                }
            }

            if (!count($data)) {
                $data[] = array(
                    'text' => $lat.', '.$lon,
                    'coords' => array($lat, $lon)
                );
            }
        } else {
            $fields = array("query" => $address, "count" => 5);
            if ($request->restrict) {
                $fields = array_merge($fields, [
                    'from_bound' => ['value' => $request->restrict],
                    'to_bound' => ['value' => $request->restrict],
                    'locations' => [['country' => '*']],
                ]);
            }
            $result = $dadata->suggest("address", $fields);

            if (isset($result['suggestions'])) {
                foreach ($result['suggestions'] as $item) {
                    $data[] = array(
                        'text' => $item['value'],
                        'coords' => array(
                            $item['data']['geo_lat'],
                            $item['data']['geo_lon']
                        )
                    );
                }
            }
        }
        
        $dadata->close();
        return response()->json($data);
    }
}