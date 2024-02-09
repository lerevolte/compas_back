<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\History;

class HistoryController extends Controller
{
    public function index($model, $id, $module = null, Request $request)
    {
        $tenant = tenant('id');
    	$page = $request->page ? $request->page : 1;
    	// $history_days = History::where(['entity' => $model, 'entity_id' => $id])->orderBy('created_at', 'DESC')->get()->groupBy(function ($val) {
        //         return \Carbon\Carbon::parse($val->created_at)->format('d.m.Y');
        //     });
        $paginator = History::orderBy('created_at', 'DESC')->orderBy('id', 'DESC');
        $paginator = $paginator->where(['entity' => $model, 'entity_id' => $id]);
        if($module)
            $paginator = $paginator->whereJsonContains('module', $module);//$paginator->where(['module' => $module]);
        else
            $paginator = $paginator->whereNull('module');
        $paginator = $paginator->paginate(20);
        //$history_items = History::where(['entity' => $model, 'entity_id' => $id])->orderBy('created_at', 'DESC')->get();
    	$data = array();
    	$users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;
    	//foreach($history_days as $history_day => $history_items) {

            foreach ($paginator->items() as $history_item) {
            	if(strstr($history_item->text, "Перенос в машину")) {
	            	$icon = '/img/car_add.svg';

	            	$action = 'Перенос в машину';
	            } elseif(strstr($history_item->text, "Удаление из машины")) {
	            	$icon = '/img/car_remove.svg';
	            	$action = 'Удаление из машины';
	            } else {
	            	$icon = '/img/edit.svg';
	            	$action = 'Изменение поля';
	            }
	            $user = $user_icon = $user_color = '';
	            if(isset($users[$history_item->user_id])) {
                    $value = json_decode($users[$history_item->user_id]->avatar,true);
                    if(isset($value[0]['url'])) {
                        $user_icon = $value[0]['url'];
                    }
                    if(is_array($value) && !$user_icon) {
                        $value = \App\Models\File::where('id', $value[0]['id'])->first();
                        if(isset($value->path)) {
                            $user_icon =  \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$value->path)->heighten(200)->url();
                           
                        } else {
                            $name = $last_name = '';
                            if($users[$history_item->user_id]->name)
                                $name = mb_substr($users[$history_item->user_id]->name,0,1);
                            if($users[$history_item->user_id]->last_name)
                                $last_name = mb_substr($users[$history_item->user_id]->last_name,0,1);
                            $user_color = $users[$history_item->user_id]->getColor();
                            $user = ucfirst($name).ucfirst($last_name);
                        }
                    }
                    
                }
            	$text = explode(': ', $history_item->text);
            	$value_type = 'text';
            	if($text[0] == 'Состав')
            		$value_type = 'json';
            	$data/*[$history_day]*/[] = array(
                    'id' => $history_item->id,
            		'date' => \Carbon\Carbon::parse($history_item->created_at)->format('d.m.Y'),
	                'created_at' => \Carbon\Carbon::parse($history_item->created_at)->format('H:i:s'),
	                'field' => ($action == 'Изменение поля') ? $text[0] : '',
	                'field_text' => $text[1],
	                'field_action' => $action,
	                'field_type' => $value_type,
	                
	                // Информация по иконке пользователя
	                'user_id' => $history_item->user_id,
	                'user_icon' => $user_icon,
	                'user_color' => $user_color,
	                'user_ab' => $user,
	                'user_name' => $users_arr[$history_item->user_id]->name.' '.$users_arr[$history_item->user_id]->last_name,

	                // Иконка действия
	                'icon_action' => $icon,
	    		);
            }
        //}
        $res = array(
        	'count' => $paginator->count(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'data' => $data
        );

    	return response()->json($res);
    }

    public function table($model, $id, Request $request)
    {
    	$limit = $request->per_page ? $request->per_page : 25;
        $page = $request->page ? $request->page : 1;

    	$history = History::where(['entity' => $model, 'entity_id' => $id])->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->paginate($limit);
    	$data = array();
    	$users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;
        $data = array();
    	foreach($history->items() as $history_item) {
        	if(strstr($history_item->text, "Перенос в машину")) {
            	$icon = '/img/car_add.svg';

            	$action = 'Перенос в машину';
            } elseif(strstr($history_item->text, "Удаление из машины")) {
            	$icon = '/img/car_remove.svg';
            	$action = 'Удаление из машины';
            } else {
            	$icon = '/img/edit.svg';
            	$action = 'Изменение поля';
            }
            $user = $user_icon = $user_color = '';
            if(isset($users[$history_item->user_id])) {
                $value = json_decode($users[$history_item->user_id]->avatar,true);
                if(isset($value[0]['url'])) {
                    $user_icon = $value[0]['url'];
                }
                if(is_array($value) && !$user_icon) {
                    $value = \App\Models\File::where('id', $value[0]['id'])->first();
                    if(isset($value->path)) {
                        $user_icon =  \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$value->path)->heighten(200)->url();
                       
                    } else {
                        $name = $last_name = '';
                        if($users[$history_item->user_id]->name)
                            $name = mb_substr($users[$history_item->user_id]->name,0,1);
                        if($users[$history_item->user_id]->last_name)
                            $last_name = mb_substr($users[$history_item->user_id]->last_name,0,1);
                        $user_color = $users[$history_item->user_id]->getColor();
                        $user = ucfirst($name).ucfirst($last_name);
                    }
                }
                
            }
        	$text = explode(': ', $history_item->text);
        	$value_type = 'text';
        	if($text[0] == 'Состав')
        		$value_type = 'json';
        	$data[] = array(
                'id' => $history_item->id,
                'date' => \Carbon\Carbon::parse($history_item->created_at)->format('d.m.Y'),
                'created_at' => \Carbon\Carbon::parse($history_item->created_at)->format('H:i:s'),
                'field' => ($action == 'Изменение поля') ? $text[0] : '',
                'field_text' => $text[1],
                'field_action' => $action,
                'field_type' => $value_type,
                
                // Информация по иконке пользователя
                'user_id' => $history_item->user_id,
                'user_icon' => $user_icon,
                'user_color' => $user_color,
                'user_ab' => $user,
                'user_name' => $users_arr[$history_item->user_id]->name.' '.$users_arr[$history_item->user_id]->last_name,

                // Иконка действия
                'icon_action' => $icon,
    		);
        }

    	return response()->json($data);
    }
}