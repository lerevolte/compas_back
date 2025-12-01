<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;

class MapController extends Controller
{
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
        //$result = $dadata->clean("address", "москва сухонская 11 89");
        // echo '<pre>';
        // print_r($result);
        // echo '</pre>';
        $dadata->close();

        // $settings = \DB::table('settings')->where('key', 'yandex_key')->first();
        // //$ch = curl_init('https://geocode-maps.yandex.ru/1.x/?apikey='.$settings->value.'&format=json&geocode=' . urlencode($request->address));
        // $ch = curl_init('https://suggest-maps.yandex.ru/suggest-geo?callback=&apikey='.$settings->value.'&v=5&search_type=tp&part='.urlencode($request->address).'&lang=ru_RU&n=6&origin=jsapi2Geocoder&bbox=-180%2C-90%2C180%2C90');
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_HEADER, false);
        // $res = curl_exec($ch);
        // curl_close($ch);
         
        //$res = json_decode($res, true);
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

        $fields = array("query" => $request->address, "count" => 5);
        $result = $dadata->suggest("address", $fields);
        $dadata->close();


        // $settings = \DB::table('settings')->where('key', 'yandex_key')->first();
        // //$ch = curl_init('https://geocode-maps.yandex.ru/1.x/?apikey='.$settings->value.'&format=json&geocode=' . urlencode($request->address));
        // $ch = curl_init('https://geocode-maps.yandex.ru/1.x/?results=1&strictBounds=true&apikey='.$settings->value.'&format=json&geocode=' . urlencode($request->address));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_HEADER, false);
        // $res = curl_exec($ch);
        // curl_close($ch);
         
        // $res = json_decode($res, true);
        // $data = array();
        // if(isset($res['response']['GeoObjectCollection']['featureMember'])) {

        //     $data = array();
        //     foreach($res['response']['GeoObjectCollection']['featureMember'] as $location) {
        //         $coordinates = $location['GeoObject']['Point']['pos'];
        //         $coordinates = explode(' ', $coordinates);
        //         $data[] = array(
        //             'text' => $location['GeoObject']['metaDataProperty']['GeocoderMetaData']['text'],
        //             'coords' => $coordinates
        //         );
        //     }
        // }

        $data = array();
        if(isset($result['suggestions'])) {
            foreach($result['suggestions'] as $item) {
                $data[] = array(
                    'text' => $item['value'],
                    'coords' => array(
                        $item['data']['geo_lat'],
                        $item['data']['geo_lon']
                    )
                );
            }
        }

        return response()->json($data);
    }
}