<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\EntityObject;

class LogisticController extends Controller
{
    public function __construct()
    {
        $this->middleware('logistic.read');
    }

    public function get_sections(Request $request)
    {
        $tenant = tenant('id');
        $sections = \DB::table('settings')->where([
            'type' => 'logistic_section',
            'user_id' => \Auth::user()->id
        ])->select('id', 'key', 'value')->get();
        if(!count($sections) || count($sections) < 4) {
            \DB::table('settings')->insert([[
                'key' => 'routes',
                'type' => 'logistic_section',
                'value' => json_encode(['position' => 1, 'height' => 300, 'column' => 1]),
                'user_id' => \Auth::user()->id
            ],[
                'key' => 'map',
                'type' => 'logistic_section',
                'value' => json_encode(['position' => 2, 'height' => 300, 'column' => 1]),
                'user_id' => \Auth::user()->id
            ],[
                'key' => 'route_tasks',
                'type' => 'logistic_section',
                'value' => json_encode(['position' => 3, 'height' => 300, 'column' => 1]),
                'user_id' => \Auth::user()->id
            ],[
                'key' => 'tasks',
                'type' => 'logistic_section',
                'value' => json_encode(['position' => 4, 'height' => 300, 'column' => 1]),
                'user_id' => \Auth::user()->id
            ]]);
            

            $sections = \DB::table('settings')->where([
                'type' => 'logistic_section',
                'user_id' => \Auth::user()->id
            ])->select('id', 'key', 'value')->get();
        }

        foreach ($sections as $key => $section) {
            $sections[$key]->value = json_decode($section->value, true);
        }

        return response()->json($sections);
    }

    public function update_section($id, Request $request)
    {
        $section = \DB::table('settings')->where('id', $id)->first();
        if(!$section) {
            return response()->json([
                'error' => array(
                    'message' => 'Секция не найдена',
                    'code' => 404
                )
            ]);
        }
        $value = json_decode($section->value, true);
        $value = [
            'position' => $request->position ? (int)$request->position : $value['position'],
            'height' => $request->height ? (int)$request->height : $value['height'],
            'column' => $request->column ? (int)$request->column : $value['column'],
        ];
        
        \DB::table('settings')->where('id', $id)->update(['value' => json_encode($value)]);
        $section->value = $value;

        return response()->json($section);
    }
}