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
        $settings = \DB::table('settings')->where('key', 'yandex_key')->first();
        //$ch = curl_init('https://geocode-maps.yandex.ru/1.x/?apikey='.$settings->value.'&format=json&geocode=' . urlencode($request->address));
        $ch = curl_init('https://suggest-maps.yandex.ru/suggest-geo?callback=&apikey='.$settings->value.'&v=5&search_type=tp&part='.urlencode($request->address).'&lang=ru_RU&n=6&origin=jsapi2Geocoder&bbox=-180%2C-90%2C180%2C90');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
         
        $res = json_decode($res, true);
        $data = array();
        if(isset($res[1])) {
            foreach($res[1] as $item) {
                $data[] = $item[2];
            }
        }

        return response()->json($data);
    }

    public function geocode(Request $request)
    {
        $settings = \DB::table('settings')->where('key', 'yandex_key')->first();
        //$ch = curl_init('https://geocode-maps.yandex.ru/1.x/?apikey='.$settings->value.'&format=json&geocode=' . urlencode($request->address));
        $ch = curl_init('https://geocode-maps.yandex.ru/1.x/?results=1&strictBounds=true&apikey='.$settings->value.'&format=json&geocode=' . urlencode($request->address));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
         
        $res = json_decode($res, true);
        $data = array();
        if(isset($res['response']['GeoObjectCollection']['featureMember'])) {

            $data = array();
            foreach($res['response']['GeoObjectCollection']['featureMember'] as $location) {
                $coordinates = $location['GeoObject']['Point']['pos'];
                $coordinates = explode(' ', $coordinates);
                $data[] = array(
                    'text' => $location['GeoObject']['metaDataProperty']['GeocoderMetaData']['text'],
                    'coords' => $coordinates
                );
            }
        }

        return response()->json($data);
    }
}