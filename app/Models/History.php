<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;


class History extends Model
{
    protected $fillable = ['entity', 'entity_id', 'user_id', 'text', 'module', 'old_value', 'new_value', 'event', 'field'];

    public function userId() {
        return $this->belongsToMany(Route::class, 'user_histories', 'history_id', 'user_id');
    }

    static function getDataList($items) 
    {
        $users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;
        $data = array(
            'fields' => array()
        );
        foreach($items as $item) {
            if(strstr($item->text, "Перенос в машину")) {
                $icon = '/img/car_add.svg';

                $action = 'Перенос в машину';
            } elseif(strstr($item->text, "Удаление из машины")) {
                $icon = '/img/car_remove.svg';
                $action = 'Удаление из машины';
            } else {
                $icon = '/img/edit.svg';
                $action = 'Изменение поля';
            }

            $user = $user_icon = $user_color = '';
            if(isset($users[$item->user_id])) {
                $value = json_decode($users[$item->user_id]->avatar,true);
                if(isset($value[0]['url'])) {
                    $user_icon = $value[0]['url'];
                }
                if(is_array($value) && !$user_icon) {
                    $value = \App\Models\File::where('id', $value[0]['id'])->first();
                    if(isset($value->path)) {
                        $user_icon =  \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$value->path)->heighten(200)->url();
                       
                    }
                }
                $name = $last_name = '';
                if($users[$item->user_id]->name)
                    $name = mb_substr($users[$item->user_id]->name,0,1);
                if($users[$item->user_id]->last_name)
                    $last_name = mb_substr($users[$item->user_id]->last_name,0,1);
                $user_color = $users[$item->user_id]->color;//$users[$item->user_id]->getColor();
                $user = ucfirst($name).ucfirst($last_name);
            }
            $text = explode(': ', $item->text);
            $value_type = 'text';
            if($text[0] == 'Состав')
                $value_type = 'json';
            $data['id'] = $item->entity_id;
            $data['slug'] = $item->entity;
            $data['fields'][] = array(
                'id' => $item->id,
                'date' => \Carbon\Carbon::parse($item->created_at)->format('d.m.Y'),
                'created_at' => \Carbon\Carbon::parse($item->created_at)->format('H:i:s'),
                'field' => ($action == 'Изменение поля') ? $text[0] : '',
                'field_text' => $text[1],
                'field_action' => $action,
                'field_type' => $value_type,
                
                // Информация по иконке пользователя
                'user_id' => $item->user_id,
                'user_icon' => $user_icon,
                'user_color' => $user_color,
                'user_ab' => $user,
                'user_name' => $users_arr[$item->user_id]->name.' '.$users_arr[$item->user_id]->last_name,

                // Иконка действия
                'icon_action' => $icon,
            );
        }

        return $data;
    }

    public function getData() 
    {
        $settings = get_settings();
        $users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;
        if(strstr($this->text, "Перенос в машину")) {
            $icon = '/img/car_add.svg';

            $action = 'Перенос в машину';
        } elseif(strstr($this->text, "Удаление из машины")) {
            $icon = '/img/car_remove.svg';
            $action = 'Удаление из машины';
        } else {
            $icon = '/img/edit.svg';
            $action = 'Изменение поля';
        }

        $user = $user_icon = $user_color = '';
        if(isset($users[$this->user_id])) {
            $value = json_decode($users[$this->user_id]->avatar,true);
            if(isset($value[0]['url'])) {
                $user_icon = $value[0]['url'];
            }
            if(is_array($value) && !$user_icon) {
                $value = \App\Models\File::where('id', $value[0]['id'])->first();
                if(isset($value->path)) {
                    $user_icon =  \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$value->path)->heighten(200)->url();
                   
                }
            }
            $name = $last_name = '';
            if($users[$this->user_id]->name)
                $name = mb_substr($users[$this->user_id]->name,0,1);
            if($users[$this->user_id]->last_name)
                $last_name = mb_substr($users[$this->user_id]->last_name,0,1);
            $user_color = $users[$this->user_id]->color;
            $user = ucfirst($name).ucfirst($last_name);
            
        }
        $text = explode(': ', $this->text);
        $value_type = 'text';
        if($text[0] == 'Состав')
            $value_type = 'json';
        $data = array(
            'fields' => array()
        );
        $data['id'] = $this->entity_id;
        $data['slug'] = $this->entity;
        $data['fields'][] = array(
            'id' => $this->id,
            'date' => \Carbon\Carbon::parse($this->created_at)->format('d.m.Y'),
            'created_at' => \Carbon\Carbon::parse($this->created_at)->format('H:i:s'),
            'field' => ($action == 'Изменение поля') ? $text[0] : '',
            'field_text' => $text[1],
            'field_action' => $action,
            'field_type' => $value_type,
            
            // Информация по иконке пользователя
            'user_id' => $this->user_id,
            'user_icon' => $user_icon,
            'user_color' => $user_color,
            'user_ab' => $user,
            'user_name' => $users_arr[$this->user_id]->name.' '.$users_arr[$this->user_id]->last_name,

            // Иконка действия
            'icon_action' => $icon,
        );

        return $data;
    }

    public static function list($model, $id, $module = null, Request $request = null)
    {
        $tenant = tenant('id');
        $settings = get_settings();
        if(!$request)
            $request = new Request();
        $page = $request->page ? $request->page : 1;


        $paginator = History::orderBy('created_at', 'DESC')->orderBy('id', 'DESC');
        $paginator = $paginator->where(['entity' => $model, 'entity_id' => $id]);
        if($module)
            $paginator = $paginator->whereJsonContains('module', $module);
        else
            $paginator = $paginator->whereNull('module');
        $paginator = $paginator->paginate(20);
        $data = array();
        $users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;

        foreach ($paginator->items() as $history_item) {
            if(strstr($history_item->text, "Перенос в машину")) {
                $icon = '/img/car_add.svg';

                $action = 'Перенос в машину';
            } elseif(strstr($history_item->text, "Удаление из машины")) {
                $icon = '/img/car_remove.svg';
                $action = 'Удаление из машины';
            } elseif($history_item->event == 'OBJECT_COPIED') {
                $icon = '/img/car_add.svg';
                $action = 'Копирование объекта';
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
                    }
                }
                $name = $last_name = '';
                if($users[$history_item->user_id]->name)
                    $name = mb_substr($users[$history_item->user_id]->name,0,1);
                if($users[$history_item->user_id]->last_name)
                    $last_name = mb_substr($users[$history_item->user_id]->last_name,0,1);
                $user_color = $users[$history_item->user_id]->color;
                $user = ucfirst($name).ucfirst($last_name);
                
            }
            if(mb_stripos($history_item->text, ':')) {
                $text = explode(': ', $history_item->text);
                $value_type = 'text';
                if($text[0] == 'Состав')
                    $value_type = 'json';
                if(isset($users_arr[$history_item->user_id]))
                    $data/*[$history_day]*/[] = array(
                        'id' => $history_item->id,
                        'event' => $history_item->event,
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
            } else {
                if(isset($users_arr[$history_item->user_id])) {
                    $history_arr = array(
                        'id' => $history_item->id,
                        'event' => $history_item->event,
                        'date' => \Carbon\Carbon::parse($history_item->created_at)->format('d.m.Y'),
                        'created_at' => \Carbon\Carbon::parse($history_item->created_at)->format('H:i:s'),
                        'field' => $history_item->text,
                        'field_action' => $action,
                        'field_type' => 'text',
                        
                        // Информация по иконке пользователя
                        'user_id' => $history_item->user_id,
                        'user_icon' => $user_icon,
                        'user_color' => $user_color,
                        'user_ab' => $user,
                        'user_name' => $users_arr[$history_item->user_id]->name.' '.$users_arr[$history_item->user_id]->last_name,

                        // Иконка действия
                        'icon_action' => $icon,
                    );
                    if($history_item->event == 'OBJECT_COPIED')
                        $history_arr['field_text'] = ($history_item->entity_id == $history_item->old_value ? $history_item->new_value : $history_item->old_value);
                    else
                        $history_arr['field_text'] = $history_item->old_value.' -> '.$history_item->new_value;
                    $data/*[$history_day]*/[] = $history_arr;
                }
            }
            
        }
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

        return $res;
    }
    
    public static function saveForObject($slug, $rows)
    {
        $settings = get_settings();
        $model_fields = $settings[$slug]['fields'];
        //$entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity = $settings['models'][$slug];
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        $keys = array();

        $ids = array();
        foreach($rows as $row) {
            $ids[] = $row['id'];
        };
        $changed_fields = array();
        $objects_collection = $entity_class::whereIntegerInRaw('id', $ids)->get();
        $objects = $objects_collection->keyBy('id')->toArray();
        $history_items = array();
        foreach($rows as $id => $row) {
            foreach($rows[$id] as $field_name => $value) {
                if(!$value && $field_name == 'name') {
                    continue;
                }
                if(!$value) {
                    $rows[$id][$field_name] = null;
                } elseif(is_array($value)) {
                    $rows[$id][$field_name] = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else {
                    foreach($model_fields as $field) {
                        if($field->only_read && $field->field != 'id' && $field->field == $field_name) {
                            unset($rows[$id][$field_name]);
                            continue;
                        }
                        if($field->field == $field_name && $field->type == 'date')
                            $rows[$id][$field_name] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');//$value = strtotime($value);
                        if($field->field == $field_name && $field->type == 'text')
                            $rows[$id][$field_name] = (string)$value;
                    }
                }
            }

            $keys = array_keys($row);
            
            
            foreach($model_fields as $field) {
                if($field->field == 'id' || $field->field == 'password')
                    continue;
                if(array_key_exists($field->field, $row) && array_key_exists($field->field, $objects[$row['id']]) && $objects[$row['id']][$field->field] !== $row[$field->field]) {
                    $changed_fields[] = $field->id;
                    if($field->type == 'status' && isset($settings[$slug]['list_values'][$field->field])) {
                        $statuses = collect($settings[$slug]['list_values'][$field->field]);
                        $visible_statuses = $statuses->filter(function ($status) {
                                return !$status['label']['is_hidden'];
                            })->pluck('value')->toArray();
                        $old_status = $statuses->firstWhere('value', $objects[$row['id']][$field->field]);
                        $new_status = $statuses->firstWhere('value', $row[$field->field]);
                        
                        if($old_status && $new_status) {
                            if($old_status['label']['is_hidden']) {
                                $old_value = $old_status['label']['color'];
                            } else {
                                $old_value = $old_status['label']['text'] ? $old_status['label']['text'] : '';//'Значение '.(array_search($old_status->id, $visible_statuses)+1);
                            }
                            if($new_status['label']['is_hidden']) {
                                $new_value = $new_status['label']['color'];
                            } else {
                                $new_value = $new_status['label']['text'] ? $new_status['label']['text'] : '';//'Значение '.(array_search($new_status->id, $visible_statuses)+1);
                            }
                            $history_text = $field->display_name.': '.$old_value.' -> '.$new_value;
                        } elseif($new_status) {
                            info('NEW STATUS');
                            if($new_status['label']['is_hidden']) {
                                $new_value = $new_status['label']['color'];
                            } else {
                                $new_value = $new_status['label']['text'] ? $new_status['label']['text'] : '';//'Значение '.(array_search($new_status->id, $visible_statuses)+1);
                            }
                            info($new_value);
                            $history_text = $field->display_name.': '.$objects[$row['id']][$field->field].' -> '.$new_value;
                        } else {
                            $history_text = $field->display_name.': '.$objects[$row['id']][$field->field].' -> '.$row[$field->field];
                        }
                    } elseif(isset($settings[$slug]['list_values'][$field->field])) {

                        $list_values = $settings[$slug]['list_values'][$field->field];
                        foreach($list_values as $k => $item) {
                            if(isset($item['label']))
                                $list_values[$item['value']] = $item['label'];
                        }
                        if($field->is_plural) {
                            $old_list = $new_list = array();
                            $arr = json_decode($objects[$row['id']][$field->field], true);
                            if(is_array($arr)) {
                                $old_list = $arr;
                            } elseif(is_integer($arr)) {
                                $old_list[] = $arr;
                            }
                            foreach($old_list as $k => $val) {
                                if(isset($list_values[$val]))
                                    $old_list[$k] = isset($list_values[$val]['text']) ? $list_values[$val]['text'] : $list_values[$val];
                                else
                                    unset($old_list[$k]);
                            }
                            
                            if(is_array($row[$field->field])) {
                                $new_list = $row[$field->field];
                            } elseif(is_integer($row[$field->field])) {
                                $new_list[] = $row[$field->field];
                            }
                            foreach($new_list as $k => $val) {
                                if(isset($list_values[$val]))
                                    $new_list[$k] = isset($list_values[$val]['text']) ? $list_values[$val]['text'] : $list_values[$val];
                                else
                                    unset($new_list[$k]);
                            }
                            $old_value = implode(', ', $old_list);

                            $new_value = implode(', ', $new_list);

                            $history_text = $field->display_name.': '.$old_value.' -> '.$new_value;
                        } else {
                            $old_value = isset($list_values[$objects[$row['id']][$field->field]]) ? $list_values[$objects[$row['id']][$field->field]] : '';

           
                            $new_value = isset($row[$field->field]) && isset($list_values[$row[$field->field]]) ? $list_values[$row[$field->field]] : '';

                            $history_text = $field->display_name.': '.(is_array($old_value) && isset($old_value['text']) ? $old_value['text'] : $old_value).' -> '.(is_array($new_value) && isset($new_value['text']) ? $new_value['text'] : $new_value);
                        }
                        if($field->type == 'relation') {
                            $related_table = json_decode($field->details, true)['table'];
                            if($related_table && isset($settings['models'][$related_table])) {
                                if(is_array($row[$field->field]) && count($row[$field->field])) {
                                    $new_values = $row[$field->field];
                                    if(is_array($arr)) 
                                        $new_values = array_diff($row[$field->field], $arr);
                                    if(count($new_values))
                                        \DB::table($related_table)->whereIntegerInRaw('id', $new_values)->update(['choosed_at' => \DB::raw('now()')]);
                                } elseif($row[$field->field]) {
                                    \DB::table($related_table)->where('id', $row[$field->field])->update(['choosed_at' => \DB::raw('now()')]);
                                }
                            }
                             
                        }
                    } else {
                        if(is_array($row[$field->field])) {
                            if($field->type == 'address') {
                                $old_addr = json_decode($objects[$row['id']][$field->field], true);
                                $history_text = $field->display_name.': '.(isset($old_addr['text']) ? $old_addr['text'] : '').' -> '.$row[$field->field]['text'];
                            } elseif($field->type == 'file') {
                                $file_values = array();
                                foreach($row[$field->field] as $v) {
                                    if(isset($v['name']))
                                        $file_values[] = $v['name'];
                                }
                                $old_values = array();
                                if($objects[$row['id']][$field->field]) {
                                    $old_files = json_decode($objects[$row['id']][$field->field], true);
                                    foreach($old_files as $v) {
                                        if(isset($v['name']))
                                            $old_values[] = $v['name'];
                                    }
                                }
                                $history_text = $field->display_name.': '.implode(', ', $old_values).' -> '.implode(', ', $file_values);
                            } else {
                                $old_value = $objects[$row['id']][$field->field];
                                if($field->is_external_link) {
                                    $arr_value = json_decode($objects[$row['id']][$field->field], true);
                                    if($arr_value && isset($arr_value['value']) && isset($arr_value['external_link']) && $arr_value['external_link'] && is_array($arr_value)) {
                                        $old_value = implode(', ', array_values($arr_value));
                                    }
                                    elseif(is_array($arr_value) && isset($arr_value['value']))
                                        $old_value = $arr_value['value'];
                                }
                                $new_value = array_filter($row[$field->field], fn($value) => !is_null($value) && $value !== '');
                                $history_text = $field->display_name.': '.$old_value.' -> '.implode(', ', array_values($new_value));
                            }
                        } else {
                            $old_value = $objects[$row['id']][$field->field];
                            if($field->type == 'file') {
                                $old_values = array();
                                if($objects[$row['id']][$field->field]) {
                                    $old_files = json_decode($objects[$row['id']][$field->field], true);
                                    foreach($old_files as $v) {
                                        if(isset($v['name']))
                                            $old_values[] = $v['name'];
                                    }
                                }
                                $old_value = implode(', ', $old_values);
                            }

                            if($field->is_external_link) {
                                $arr_value = json_decode($objects[$row['id']][$field->field], true);
                                if(isset($arr_value['value']) && isset($arr_value['external_link']) && $arr_value['external_link']) {
                                    $old_value = implode(', ', array_values($arr_value));
                                }
                            }
                            $history_text = $field->display_name.': '.$old_value.' -> '.$row[$field->field];
                        }
                        
                    }
                    if($history_text) {
                        $module = $field->module;
                        if(isset(\Auth::user()->id))
                            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $row['id'], 'user_id' => \Auth::user()->id, 'text' => $history_text, 'module' => $module]);
                        else
                            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $row['id'], 'user_id' => 0, 'text' => $history_text, 'module' => $module]);

                        $history->saveQuietly();
                        if($module) {
                            $history_replic = $history->replicate();
                            $history_replic->module = null;
                            $history_replic->save();
                        }
                        $history_items[] = $history;
                        
                    }
                }
            }
        }

        if(count($history_items)) {
            $data = \App\Models\History::getDataList($history_items);

            \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);
        }
        $objects_collection_by_key = $objects_collection->keyBy('id');

        return array('by_key' => $objects_collection_by_key, 'changed_fields' => $changed_fields);
    }
    public static function createObject($slug, $object)
    {
        $settings = get_settings();
        $model_fields = $settings[$slug]['fields'];
        $entity = $settings['models'][$slug];
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;

        $object = $object->toArray();
        $history_items = array();
        $history_text = 'Создана запись: '.$object['id'];
        if(isset(\Auth::user()->id))
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $object['id'], 'user_id' => \Auth::user()->id, 'text' => $history_text]);
        else
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $object['id'], 'user_id' => 0, 'text' => $history_text]);
        $history->save();
        $history_items[] = $history;
        foreach($object as $field_name => $value) {
            if(!$value && $field_name == 'name') {
                continue;
            }
            foreach($model_fields as $field) {
                if($field->only_read && $field->field == $field_name && $field->field != 'id') {
                    unset($object[$field_name]);
                    continue;
                }
                if($field->field == $field_name && $field->type == 'date')
                    $object[$field_name] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');//$value = strtotime($value);
                if($field->field == $field_name && $field->type == 'text')
                    $object[$field_name] = (string)$value;
            }
        }

        foreach($model_fields as $field) {
            if($field->field == 'id' || $field->field == 'password')
                continue;
            if(array_key_exists($field->field, $object) && array_key_exists($field->field, $object)) {
                if($field->type == 'status' && isset($settings[$slug]['list_values'][$field->field])) {
                    $statuses = collect($settings[$slug]['list_values'][$field->field]);
                    $visible_statuses = $statuses->filter(function ($status) {
                            return !$status['label']['is_hidden'];
                        })->pluck('value')->toArray();
                    $new_status = $statuses->firstWhere('value', $object[$field->field]);
                    
                    if($new_status) {
                        if($new_status['label']['is_hidden']) {
                            $new_value = $new_status['label']['color'];
                        } else {
                            $new_value = $new_status['label']['text'] ? $new_status['label']['text'] : '';//'Значение '.(array_search($new_status->id, $visible_statuses)+1);
                        }
                        $history_text = $field->display_name.': '.$object[$field->field].' -> '.$new_value;
                    } else {
                        $history_text = $field->display_name.': -> '.$object[$field->field];
                    }
                } elseif(isset($settings[$slug]['list_values'][$field->field])) {

                    $list_values = $settings[$slug]['list_values'][$field->field];
                    foreach($list_values as $k => $item) {
                        if(isset($item['label']))
                            $list_values[$item['value']] = $item['label'];
                    }
                    if($field->is_plural) {
                        if(is_array($object[$field->field])) {
                            $new_list = $object[$field->field];
                        } elseif(is_integer($object[$field->field])) {
                            $new_list[] = $object[$field->field];
                        }
                        foreach($new_list as $k => $val) {
                            if(isset($list_values[$val]))
                                $new_list[$k] = isset($list_values[$val]['text']) ? $list_values[$val]['text'] : $list_values[$val];
                            else
                                unset($new_list[$k]);
                        }

                        $new_value = implode(', ', $new_list);

                        $history_text = $field->display_name.': -> '.$new_value;
                    } else {
       
                        $new_value = isset($list_values[$object[$field->field]]) ? $list_values[$object[$field->field]] : '';

                        $history_text = $field->display_name.': -> '.(is_array($new_value) && isset($new_value['text']) ? $new_value['text'] : $new_value);
                    }
                    if($field->type == 'relation') {
                        $related_table = json_decode($field->details, true)['table'];
                        if($related_table && isset($settings['models'][$related_table])) {
                            if(is_array($object[$field->field]) && count($object[$field->field])) {
                                $new_values = $object[$field->field];
                                if(is_array($arr)) 
                                    $new_values = array_diff($object[$field->field], $arr);
                                if(count($new_values))
                                    \DB::table($related_table)->whereIntegerInRaw('id', $new_values)->update(['choosed_at' => \DB::raw('now()')]);
                            } elseif($object[$field->field]) {
                                \DB::table($related_table)->where('id', $object[$field->field])->update(['choosed_at' => \DB::raw('now()')]);
                            }
                        }
                         
                    }
                } else {
                    if(is_array($object[$field->field])) {
                        if($field->type == 'address') {
                            $history_text = $field->display_name.': -> '.$object[$field->field]['text'];
                        } elseif($field->type == 'file') {
                            $file_values = array();
                            foreach($object[$field->field] as $v) {
                                if(isset($v['name']))
                                    $file_values[] = $v['name'];
                            }
                            $history_text = $field->display_name.': -> '.implode(', ', $file_values);
                        } else {

                            $new_value = array_filter($object[$field->field], fn($value) => !is_null($value) && $value !== '');
                            $history_text = $field->display_name.': -> '.implode(', ', array_values($new_value));
                        }
                    } else {
                        $history_text = $field->display_name.': -> '.$object[$field->field];
                    }
                    
                }
                if($history_text) {
                    $module = $field->module;
                    if(isset(\Auth::user()->id))
                        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $object['id'], 'user_id' => \Auth::user()->id, 'text' => $history_text, 'module' => $module]);
                    else
                        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $object['id'], 'user_id' => 0, 'text' => $history_text, 'module' => $module]);

                    $history->saveQuietly();
                    if($module) {
                        $history_replic = $history->replicate();
                        $history_replic->module = null;
                        $history_replic->save();
                    }
                    $history_items[] = $history;
                    
                }
            }
        }

        if(count($history_items)) {
            $data = \App\Models\History::getDataList($history_items);

            \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);
        }
    }
}