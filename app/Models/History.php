<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Helpers\ValueHelper;


class History extends Model
{
    protected $fillable = ['entity', 'entity_id', 'user_id', 'text', 'module', 'old_value', 'new_value', 'event', 'field', 'color', 'is_relation'];

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
        $settings = get_settings();//app('settings');//get_settings();
        $t = \DB::table('settings')->where([
            'type' => 'account',
            'key' => 'timezone'
        ])->first();

        if(!$t) {
            \DB::table('settings')->insert([
                'key' => 'timezone',
                'display_name' => 'Часовой пояс',
                'type' => 'account',
                'value' => 'Europe/Moscow'
            ]);


            $t = \DB::table('settings')->where([
                'type' => 'account',
                'key' => 'timezone'
            ])->first();
        }
        $tz = $t->value;
        $model_fields = $settings[$items[0]->entity]['fields'];
        $copied_elements = array();
        
        foreach ($items as $item) {
            if($item->event == 'OBJECT_COPIED') {
                $copied_elements[] = $item->old_value;
                $copied_elements[] = $item->new_value;
            }
        };
        if(count($copied_elements)) {
            $copied_elements = \DB::table($items[0]->entity)->select(['id', 'name'])->whereIntegerInRaw('id', $copied_elements)->get()->keyBy('id');
        }
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

            $user = $user_icon = $user_color = $user_name = '';
            if(isset($users[$item->user_id])) {
                $value = json_decode($users[$item->user_id]->avatar,true);
                if(isset($value[0]['url'])) {
                    $user_icon = $value[0]['url'];
                }
                if(is_array($value) && !$user_icon && isset($value[0]['id'])) {
                    $value = \App\Models\File::where('id', $value[0]['id'])->first();
                    if(isset($value->path)) {
                        $user_icon =  \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$value->path)->heighten(200)->url();
                       
                    }
                }
                $name = $last_name = $user_name = '';
                if($users[$item->user_id]->name) {
                    if(ValueHelper::isJson($users[$item->user_id]->name)) {
                        $name_arr = json_decode($users[$item->user_id]->name, true);
                        $user_name = $name_arr['value'];
                        $name = mb_substr($name_arr['value'],0,1);
                    } else {
                        $user_name = $users[$item->user_id]->name;
                        $name = mb_substr($users[$item->user_id]->name,0,1);
                    }
                }
                if($users[$item->user_id]->last_name) {
                    if(ValueHelper::isJson($users[$item->user_id]->last_name)) {
                        $last_name_arr = json_decode($users[$item->user_id]->last_name, true);
                        $user_name.= ' '.$last_name_arr['value'];
                        $last_name = mb_substr($last_name_arr['value'],0,1);
                    } else {
                        $last_name = mb_substr($users[$item->user_id]->last_name,0,1);
                        $user_name.= ' '.$users[$item->user_id]->last_name;
                    }
                }
                $user_color = $users[$item->user_id]->color;//$users[$item->user_id]->getColor();
                $user = ucfirst($name).ucfirst($last_name);
            }
            $text = explode(': ', $item->text);
            $value_type = 'text';
            if($text[0] == 'Состав')
                $value_type = 'json';
            $values = array('', '');
            if(isset($text[1]))
                $values = explode('->', $text[1]);
            if($item->event == 'FIELD_UPDATED' && !$item->is_relation) {
                $values[0] = $item->old_value;
                $values[1] = $item->new_value;
            }
            $data['id'] = $item->entity_id;
            $data['slug'] = $item->entity;
            $used_in_modules = array();
            foreach($model_fields as $field) {
                if($item->field == $field->field && $field->module) {
                    $used_in_modules = json_decode($field->module, true);
                } 
            }
            $title = $item->event == 'FIELD_UPDATED' ? $text[0] : $item->text;

            if($item->event == 'OBJECT_COPIED') {
                $changed_value = $item->new_value;
                if(isset($copied_elements[$changed_value])) {
                    $arr = json_decode($copied_elements[$changed_value]->name, true);
                    if(isset($arr['value']))
                        $title = preg_replace('/(<span.*?>).*?(<\/span>)/', '${1}'.($arr['value'] ? $arr['value'] : $copied_elements[$changed_value]->id).'$2', $title);
                    else
                        $title = preg_replace('/(<span.*?>).*?(<\/span>)/', '${1}'.($copied_elements[$changed_value]->name ? $copied_elements[$changed_value]->name : $copied_elements[$changed_value]->id).'$2', $title);
                }
            }
            $data['fields'][] = array(
                'id' => $item->id,
                'event' => $item->event,
                'date' => \Carbon\Carbon::parse($item->created_at)->timezone($tz)->format('Y-m-d'),
                'created_at' => \Carbon\Carbon::parse($item->created_at)->timezone($tz)->format('H:i:s'),
                'field' => array(
                    'title' => $title,
                    'prev_value' => $values[0],
                    'next_value' => isset($values[1]) ? $values[1] : '',
                ),
                'user' => array(
                    'id' => $item->user_id,
                    'ab' => $user,
                    'name' => $user_name,
                    'color' => $user_color,
                    'icon' => $user_icon
                ),
                'used_in_modules' => $used_in_modules,
                'show_title' => $item->event == 'FIELD_UPDATED' ? false : true,
                'color' => $item->color


                // 'id' => $item->id,
                // 'date' => \Carbon\Carbon::parse($item->created_at)->format('d.m.Y'),
                // 'created_at' => \Carbon\Carbon::parse($item->created_at)->format('H:i:s'),
                // 'field' => ($action == 'Изменение поля') ? $text[0] : '',
                // 'field_text' => $text[1],
                // 'field_action' => $action,
                // 'field_type' => $value_type,
                
                // // Информация по иконке пользователя
                // 'user_id' => $item->user_id,
                // 'user_icon' => $user_icon,
                // 'user_color' => $user_color,
                // 'user_ab' => $user,
                // 'user_name' => $users_arr[$item->user_id]->name.' '.$users_arr[$item->user_id]->last_name,

                // // Иконка действия
                // 'icon_action' => $icon,
            );
        }

        return $data;
    }

    public function getData() 
    {
        $settings = app('settings');//get_settings();
        $t = \DB::table('settings')->where([
            'type' => 'account',
            'key' => 'timezone'
        ])->first();

        if(!$t) {
            \DB::table('settings')->insert([
                'key' => 'timezone',
                'display_name' => 'Часовой пояс',
                'type' => 'account',
                'value' => 'Europe/Moscow'
            ]);


            $t = \DB::table('settings')->where([
                'type' => 'account',
                'key' => 'timezone'
            ])->first();
        }
        $tz = $t->value;
        $users = \App\Models\User::get();
        $copied_elements = array($this->id);
        
        if(count($copied_elements)) {
            $copied_elements = \DB::table($this->entity)->select(['id', 'name'])->whereIntegerInRaw('id', $copied_elements)->get()->keyBy('id');
        }

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
            if(is_array($value) && !$user_icon && isset($value[0]['id'])) {
                $value = \App\Models\File::where('id', $value[0]['id'])->first();
                if(isset($value->path)) {
                    $user_icon =  \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$value->path)->heighten(200)->url();
                   
                }
            }
            $name = $last_name = $user_name = '';
            if($users[$this->user_id]->name) {
                if(ValueHelper::isJson($users[$this->user_id]->name)) {
                    $name_arr = json_decode($users[$this->user_id]->name, true);
                    $user_name = $name_arr['value'];
                    $name = mb_substr($name_arr['value'],0,1);
                } else {
                    $name = mb_substr($users[$this->user_id]->name,0,1);
                    $user_name = $users[$this->user_id]->name;
                }
            }
            if($users[$this->user_id]->last_name) {
                if(ValueHelper::isJson($users[$this->user_id]->last_name)) {
                    $last_name_arr = json_decode($users[$this->user_id]->last_name, true);
                    $user_name.=' '.$last_name_arr['value'];
                    $last_name = mb_substr($last_name_arr['value'],0,1);
                } else {
                    $user_name.=' '.$users[$this->user_id]->last_name;
                    $last_name = mb_substr($users[$this->user_id]->last_name,0,1);
                }
            }
            $user_color = $users[$this->user_id]->color;
            $user = ucfirst($name).ucfirst($last_name);
        }
        $text = explode(': ', $this->text);
        $value_type = 'text';
        $values = array('', '');
        if(isset($text[1]))
            $values = explode('->', $text[1]);
        if($text[0] == 'Состав')
            $value_type = 'json';
        if($this->event == 'FIELD_UPDATED' && !$this->is_relation) {
            $values[0] = $this->old_value;
            $values[1] = $this->new_value;
        }
        $data = array(
            'fields' => array()
        );
        $data['id'] = $this->entity_id;
        $data['slug'] = $this->entity;
        $title = $this->event == 'FIELD_UPDATED' ? $text[0] : $this->text;
        if($this->event == 'OBJECT_COPIED') {
            $changed_value = $this->new_value;
            if(isset($copied_elements[$changed_value])) {
                $arr = json_decode($copied_elements[$changed_value]->name, true);
                if(isset($arr['value']))
                    $title = preg_replace('/(<span.*?>).*?(<\/span>)/', '${1}'.($arr['value'] ? $arr['value'] : $copied_elements[$changed_value]->id).'$2', $title);
                else
                    $title = preg_replace('/(<span.*?>).*?(<\/span>)/', '${1}'.($copied_elements[$changed_value]->name ? $copied_elements[$changed_value]->name : $copied_elements[$changed_value]->id).'$2', $title);
            }
        }
        $data['fields'][] = array(
            'id' => $this->id,
            'event' => $this->event,
            'date' => \Carbon\Carbon::parse($this->created_at)->timezone($tz)->format('Y-m-d'),
            'created_at' => \Carbon\Carbon::parse($this->created_at)->timezone($tz)->format('H:i:s'),
            'field' => array(
                'title' => $title,
                'prev_value' => $values[0],
                'next_value' => isset($values[1]) ? $values[1] : ''
            ),
            'user' => array(
                'id' => $item->user_id,
                'ab' => $user,
                'name' => $user_name,
                'color' => $user_color,
                'icon' => $user_icon
            ),
            'show_title' => $item->event == 'FIELD_UPDATED' ? false : true,
            'color' => $item->color
        );
        // $data['fields'][] = array(
        //     'id' => $this->id,
        //     'date' => \Carbon\Carbon::parse($this->created_at)->format('d.m.Y'),
        //     'created_at' => \Carbon\Carbon::parse($this->created_at)->format('H:i:s'),
        //     'field' => ($action == 'Изменение поля') ? $text[0] : '',
        //     'field_text' => $text[1],
        //     'field_action' => $action,
        //     'field_type' => $value_type,
            
        //     // Информация по иконке пользователя
        //     'user_id' => $this->user_id,
        //     'user_icon' => $user_icon,
        //     'user_color' => $user_color,
        //     'user_ab' => $user,
        //     'user_name' => $users_arr[$this->user_id]->name.' '.$users_arr[$this->user_id]->last_name,

        //     // Иконка действия
        //     'icon_action' => $icon,
        // );

        return $data;
    }

    public static function list($model, $id, $module = null, Request $request = null, $settings = null)
    {
        $tenant = tenant('id');
        $t = \DB::table('settings')->where([
            'type' => 'account',
            'key' => 'timezone'
        ])->first();

        if(!$t) {
            \DB::table('settings')->insert([
                'key' => 'timezone',
                'display_name' => 'Часовой пояс',
                'type' => 'account',
                'value' => 'Europe/Moscow'
            ]);


            $t = \DB::table('settings')->where([
                'type' => 'account',
                'key' => 'timezone'
            ])->first();
        }
        $tz = $t->value;
        $settings = $settings ? $settings : app('settings');
        if(!$request)
            $request = new Request();
        $page = $request->page ? $request->page : 1;


        $paginator = History::orderBy('created_at', 'DESC')->orderBy('id', 'DESC');
        $paginator = $paginator->where(['entity' => $model, 'entity_id' => $id]);
        if($module)
            $paginator = $paginator->where('module', $module);//$paginator->whereJsonContains('module', $module);
        else
            $paginator = $paginator->whereNull('module');
        if($request->filter == 'events'){
            $paginator = $paginator->whereNotNull('event')->where('event', '!=', 'FIELD_UPDATED');
            
        } else {
            $paginator = $paginator->where(function($query){
                $query->whereNull('event')->orWhere('event', 'FIELD_UPDATED');
            });
        }
        $paginator = $paginator->paginate(15);
        $data = array();
        $users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;

        $copied_elements = array();
        $items = $paginator->items();
        foreach ($items as $item) {
            if($item->event == 'OBJECT_COPIED') {
                $copied_elements[] = $item->old_value;
                $copied_elements[] = $item->new_value;
            }
        };
        if(count($copied_elements)) {
            $copied_elements = \DB::table($items[0]->entity)->select(['id', 'name'])->whereIntegerInRaw('id', $copied_elements)->get()->keyBy('id');
        }
        foreach ($items as $item) {
            if($item->event == 'OBJECT_COPIED') {
                $icon = '/img/car_add.svg';
                $action = 'Копирование объекта';
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
                if(is_array($value) && !$user_icon && isset($value[0]['id'])) {

                    $value = \App\Models\File::where('id', $value[0]['id'])->first();
                    if(isset($value->path)) {
                        $user_icon =  \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$value->path)->heighten(200)->url();
                    }
                }
                $name = $last_name = $user_name = '';
                if($users[$item->user_id]->name) {
                    if(ValueHelper::isJson($users[$item->user_id]->name)) {
                        $name_arr = json_decode($users[$item->user_id]->name, true);
                        $user_name = $name_arr['value'];
                        $name = mb_substr($name_arr['value'],0,1);
                    } else {
                        $user_name = $users[$item->user_id]->name;
                        $name = mb_substr($users[$item->user_id]->name,0,1);
                    }
                }
                if($users[$item->user_id]->last_name) {
                    if(ValueHelper::isJson($users[$item->user_id]->last_name)) {
                        $last_name_arr = json_decode($users[$item->user_id]->last_name, true);
                        $user_name.= ' '.$last_name_arr['value'];
                        $last_name = mb_substr($last_name_arr['value'],0,1);
                    } else {
                        $user_name.= ' '.$users[$item->user_id]->last_name;
                        $last_name = mb_substr($users[$item->user_id]->last_name,0,1);
                    }
                }
                $user_color = $users[$item->user_id]->color;
                $user = ucfirst($name).ucfirst($last_name);
            }
            if(mb_stripos($item->text, ':')) {
                $text = explode(': ', $item->text);
                $value_type = 'text';
                if($text[0] == 'Состав')
                    $value_type = 'json';
                $values = array('', '');
                if(isset($text[1]))
                    $values = explode('->', $text[1]);
                if($item->event == 'FIELD_UPDATED' && !$item->is_relation) {
                    $values[0] = $item->old_value;
                    $values[1] = $item->new_value;
                }
                $title = $item->event == 'FIELD_UPDATED' ? $text[0] : $item->text;
                if($item->event == 'OBJECT_COPIED') {
                    $changed_value = $item->new_value;
                    if(isset($copied_elements[$changed_value])) {
                        $arr = json_decode($copied_elements[$changed_value]->name, true);
                        if(isset($arr['value']))
                            $title = preg_replace('/(<span.*?>).*?(<\/span>)/', '${1}'.($arr['value'] ? $arr['value'] : $copied_elements[$changed_value]->id).'$2', $title);
                        else
                            $title = preg_replace('/(<span.*?>).*?(<\/span>)/', '${1}'.($copied_elements[$changed_value]->name ? $copied_elements[$changed_value]->name : $copied_elements[$changed_value]->id).'$2', $title);
                    }
                }
                if($item->is_relation && isset($settings[$model]['fields'][$item->field])) {
                    $field_id = $settings[$model]['fields'][$item->field]->id;
                    if($item->old_value) {
                        $old_value = explode(', ', $item->old_value);
                        if(isset($settings['list_values'][$field_id][$old_value[0]])) {
                            $title = $text[0].': ';
                            $names = array();
                            foreach($old_value as $v) {
                                $rel_table = $settings[$model]['fields'][$item->field]->relation_table;

                                if(isset($settings['list_values'][$field_id][$v])){
                                    $new_name = $settings['list_values'][$field_id][$v]['label']['text'];
                                    $new_name = $new_name ? $new_name : $v;
                                    if($rel_table == 'roles')
                                        $names[] =  "<span class='history-item__subtitle'>$new_name</span>";
                                    else
                                        $names[] = "<span data-slug='$rel_table' data-id='$v'>$new_name</span>";
                                }
                                $copied_elements[] = $v;
                            }
                            $title.=implode(', ', $names);
                            $values[0] = implode(', ', $names);
                        }
                    }
                    if($item->new_value && $item->event != 'RELATION_DELETED') {
                        $new_value = explode(', ', $item->new_value);
                        if(isset($settings['list_values'][$field_id][$new_value[0]])) {
                            $title = $text[0].': ';
                            $names = array();
                            foreach($new_value as $v) {
                                $rel_table = $settings[$model]['fields'][$item->field]->relation_table;
                                if(isset($settings['list_values'][$field_id][$v])) {
                                    $new_name = $settings['list_values'][$field_id][$v]['label']['text'];
                                    $new_name = $new_name ? $new_name : $v;
                                    if(isset($settings['list_values'][$field_id][$v])) {
                                        if($rel_table == 'roles')
                                            $names[] =  "<span class='history-item__subtitle'>$new_name</span>";
                                        else
                                            $names[] = "<span data-slug='$rel_table' data-id='$v'>$new_name</span>";
                                    }
                                    $copied_elements[] = $v;
                                }
                            }
                            $title.=implode(', ', $names);
                            $values[1] = implode(', ', $names);
                        }
                    }
                    if($item->event == 'FIELD_UPDATED')
                        $title = $text[0];
                }
                if(isset($users_arr[$item->user_id]))
                    $data/*[$history_day]*/[] = array(
                        'id' => $item->id,
                        'event' => $item->event,
                        'date' => \Carbon\Carbon::parse($item->created_at)->timezone($tz)->format('Y-m-d'),
                        'created_at' => \Carbon\Carbon::parse($item->created_at)->timezone($tz)->format('H:i:s'),
                        'field' => array(
                            'title' => $title,
                            'prev_value' => $values[0],
                            'next_value' => isset($values[1]) ? $values[1] : ''
                        ),
                        'user' => array(
                            'id' => $item->user_id,
                            'ab' => $user,
                            'name' => $user_name,
                            'color' => $user_color,
                            'icon' => $user_icon
                        ),
                        'show_title' => $item->event == 'FIELD_UPDATED' ? false : true,
                        'color' => $item->color
                    );
            } else {
                if(isset($users_arr[$item->user_id])) {
                    $history_arr = array(
                        'id' => $item->id,
                        'event' => $item->event,
                        'date' => \Carbon\Carbon::parse($item->created_at)->timezone($tz)->format('Y-m-d'),
                        'created_at' => \Carbon\Carbon::parse($item->created_at)->timezone($tz)->format('H:i:s'),
                        'field' => array(
                            'title' => $item->text,//$text[0],
                            'prev_value' => $item->text,
                            'next_value' => $item->text
                        ),
                        'user' => array(
                            'id' => $item->user_id,
                            'ab' => $user,
                            'name' => $user_name,
                            'color' => $user_color,
                            'icon' => $user_icon
                        ),
                        'show_title' => $item->event == 'FIELD_UPDATED' ? false : true,
                        'color' => $item->color
                    );
                    $data[] = $history_arr;
                }
            }
            
        }
        $res = array(
            'count' => $paginator->count(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            //'total' => $paginator->total(),
            //'from' => $paginator->firstItem(),
            //'to' => $paginator->lastItem(),
            'data' => $data
        );

        return $res;
    }
    
    public static function saveForObject($slug, $rows, $fire_event = true, $old_values = array(), $settings = array())
    {
        if(!count($settings))
            $settings = \App\Models\Settings::get();//\App\Models\Settings::get();
        $user_id = \Auth::user() ? \Auth::user()->id : 1;
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

        $ids = array();
        $new_object = null;
        $history_items = array();
        $new_id = null;
        foreach($rows as $row) {
            if($row['id'] == 0) {
                // $new_object = new $entity_class;
                // $new_object->save();
                // $ids[] = $new_object->id;
            } else {
                $ids[] = $row['id'];
            }
            if(isset($row['is_new'])) {
                $new_id = $row['id'];
            }
        };
        $changed_fields = array();
        $objects = array();
        if(count($ids)) {
            $objects_collection = $entity_class::whereIntegerInRaw('id', $ids)->get()->keyBy('id');
            $objects = $objects_collection->toArray();
        }
        if($new_id)
            foreach($objects as $object) {
                if($object['id'] == $new_id)
                    $new_object = $objects_collection[$object['id']];
            }
        if($new_object) {
            $objects[0] = $new_object;
            //$new_object->save();
            $history_text = 'Создана запись: '.$new_object->id;
            $history = new \App\Models\History(['entity' => $slug, 'event' => 'OBJECT_CREATED', 'entity_id' => $new_object->id, 'user_id' => $user_id, 'text' => $history_text]);
            $history->save();
            $history_items[] = $history;
        }
        foreach($rows as $id => $row) {
            if(!$row['id'] && $new_object)
                $row['id'] = $new_object->id;
            foreach($rows[$id] as $field_name => $value) {
                if($field_name == 'is_new')
                    continue;
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
                            $rows[$id][$field_name] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
                        if($field->field == $field_name && $field->type == 'text')
                            $rows[$id][$field_name] = (string)$value;
                    }
                }
            }
            
            foreach($model_fields as $field) {
                if($field->field == 'created_at' || $field->field == 'updated_at')
                    $changed_fields[] = $field->id;
                if($field->field == 'id' || $field->field == 'password' || $field->field == 'created_at' || $field->field == 'updated_at')
                    continue;
                if(isset($objects[$row['id']]) && array_key_exists($field->field, $row) && array_key_exists($field->field, $objects[$row['id']]) && $objects[$row['id']][$field->field] !== $row[$field->field]) {


                    $changed_fields[] = $field->id;
                    if($field->group_id)
                        $changed_fields[] = $field->group_id;
                    $old_value = array('res' => null, 'arr_res' => array(), 'values' => array());
                    if(!$new_object || $row['id'] != $new_object->id) {
                        if($field->type == 'relation' && $field->is_plural) {
                            $relation = $objects_collection[$row['id']]->{$field->relation_table};
                            $old_value = \App\Models\Field::getHumanValue($field, $relation ? $relation->pluck('id')->toArray() : []);
                        } else {
                            $old_value = \App\Models\Field::getHumanValue($field, $objects[$row['id']][$field->field]);
                        }
                    }
                    $new_value = \App\Models\Field::getHumanValue($field, $row[$field->field]);

                    $history_text = '';
                    if($old_value['res'] != $new_value['res']) {
                        if($field->type == 'relation') {
                            if(count($old_value['arr_res']) || count($new_value['arr_res'])) {
                                $old = array_diff($old_value['arr_res'], $new_value['arr_res']);
                                $new = array_diff($new_value['arr_res'], $old_value['arr_res']);
                                $old_values = array_diff($old_value['values'], $new_value['values']);
                                $new_values = array_diff($new_value['values'], $old_value['values']);

                                $history_text = $field->title.': '.$old_value['res'].' -> '.$new_value['res'];
                                $module = $field->module;
                                $history_data = array(
                                    'entity' => $slug, 
                                    'entity_id' => $row['id'], 
                                    'user_id' => $user_id,
                                    'text' => $history_text,
                                    'field' => $field->field,
                                    'old_value' => implode(', ', $old_value['values']),//implode(', ', $old_values),
                                    'new_value' => implode(', ', $new_value['values']),//implode(', ', $new_values),
                                    'event' => 'FIELD_UPDATED',
                                    'is_relation' => 1
                                );
                                // if($module) {
                                //     $modules = json_decode($field->module,true);
                                //     foreach($modules as $m) {
                                //         $history_data['module'] = $m;
                                //         $history = new \App\Models\History($history_data);
                                //     }
                                // } else {
                                    $history = new \App\Models\History($history_data);
                                //}

                                $history->saveQuietly();
                                $history_items[] = $history;
                                if(count($old)) {
                                    $history_text = $field->title.', удалена связь: '.implode(', ', $old);
                                    $module = $field->module;
                                    $history_data = array(
                                        'entity' => $slug, 
                                        'entity_id' => $row['id'], 
                                        'user_id' => $user_id,
                                        'text' => $history_text,
                                        'field' => $field->field,
                                        'old_value' => implode(', ', $old_values),
                                        'new_value' => implode(', ', $new_values),
                                        'event' => 'RELATION_DELETED',
                                        'color' => '#C74822',
                                        'is_relation' => 1
                                    );
                                    if($module) {
                                        $modules = json_decode($field->module,true);
                                        foreach($modules as $m) {
                                            $history_data['module'] = $m;
                                            $history = new \App\Models\History($history_data);
                                        }
                                    } else {
                                        $history = new \App\Models\History($history_data);
                                    }

                                    $history->saveQuietly();
                                    if($module) {
                                        $history_replic = $history->replicate();
                                        $history_replic->module = null;
                                        $history_replic->save();
                                    }
                                    $history_items[] = $history;
                                }
                                
                                if(count($new)) {
                                    $history_text = $field->title.', добавлена связь: '.implode(', ', $new);
                                    $module = $field->module;
                                    $history_data = array(
                                        'entity' => $slug, 
                                        'entity_id' => $row['id'], 
                                        'user_id' => $user_id,
                                        'text' => $history_text,
                                        'field' => $field->field,
                                        'old_value' => implode(', ', $old_values),
                                        'new_value' => implode(', ', $new_values),
                                        'event' => 'RELATION_ADDED',
                                        'color' => '#23704B',
                                        'is_relation' => 1
                                    );
                                    if($module) {
                                        $modules = json_decode($field->module,true);
                                        foreach($modules as $m) {
                                            $history_data['module'] = $m;
                                            $history = new \App\Models\History($history_data);
                                        }
                                    } else {
                                        $history = new \App\Models\History($history_data);
                                    }

                                    $history->saveQuietly();
                                    if($module) {
                                        $history_replic = $history->replicate();
                                        $history_replic->module = null;
                                        $history_replic->save();
                                    }
                                    $history_items[] = $history;
                                }


                            }
                        } elseif($field->type == 'redactor') {
                            // Контент redactor (detail_text) может весить десятки КБ и
                            // переполняет text-колонки histories (max 65535). Полный JSON
                            // в истории бесполезен — фиксируем только факт изменения.
                            $history_text = $field->title.': контент обновлён';
                            $history_data = array(
                                'entity' => $slug,
                                'event' => 'FIELD_UPDATED',
                                'entity_id' => $row['id'],
                                'user_id' => $user_id,
                                'text' => $history_text,
                                'field' => $field->field,
                                'old_value' => null,
                                'new_value' => null
                            );
                            $history = new \App\Models\History($history_data);
                            $history->saveQuietly();
                            $history_items[] = $history;
                        } else {
                            $history_text = $field->title.': '.$old_value['res'].' -> '.$new_value['res'];
                            $module = $field->module;
                            $history_data = array(
                                'entity' => $slug,
                                'event' => 'FIELD_UPDATED',
                                'entity_id' => $row['id'], 
                                'user_id' => $user_id, 
                                'text' => $history_text,
                                'field' => $field->field,
                                'old_value' => $old_value['res'],
                                'new_value' => $new_value['res']
                            );
                            // if($module) {
                            //     $modules = json_decode($field->module,true);
                            //     foreach($modules as $m) {
                            //         $history_data['module'] = $m;
                            //         $history = new \App\Models\History($history_data);
                            //     }
                            // } else {
                                $history = new \App\Models\History($history_data);
                            //}

                            $history->saveQuietly();
                            // if($module) {
                            //     $history_replic = $history->replicate();
                            //     $history_replic->module = null;
                            //     $history_replic->save();
                            // }
                            $history_items[] = $history;
                        }

                    } 
                    // if($field->type == 'status' && isset($settings['list_values'][$field->id])) {
                    //     $statuses = collect($settings['list_values'][$field->id]);
                    //     $visible_statuses = $statuses->filter(function ($status) {
                    //             return !$status['label']['is_hidden'];
                    //         })->pluck('value')->toArray();
                    //     $old_status = $statuses->firstWhere('value', $objects[$row['id']][$field->field]);
                    //     $new_status = $statuses->firstWhere('value', $row[$field->field]);
                        
                    //     if($old_status && $new_status) {
                    //         if($old_status['label']['is_hidden']) {
                    //             $old_value = $old_status['label']['color'];
                    //         } else {
                    //             $old_value = $old_status['label']['text'] ? $old_status['label']['text'] : '';
                    //         }
                    //         if($new_status['label']['is_hidden']) {
                    //             $new_value = $new_status['label']['color'];
                    //         } else {
                    //             $new_value = $new_status['label']['text'] ? $new_status['label']['text'] : '';
                    //         }
                    //         $history_text = $field->title.': '.$old_value.' -> '.$new_value;
                    //     } elseif($new_status) {
                    //         if($new_status['label']['is_hidden']) {
                    //             $new_value = $new_status['label']['color'];
                    //         } else {
                    //             $new_value = $new_status['label']['text'] ? $new_status['label']['text'] : '';
                    //         }
                    //         $history_text = $field->title.': '.$objects[$row['id']][$field->field].' -> '.$new_value;
                    //     } else {
                    //         $history_text = $field->title.': '.$objects[$row['id']][$field->field].' -> '.$row[$field->field];
                    //     }
                    // } elseif(isset($settings['list_values'][$field->id])) {

                    //     $list_values = $settings['list_values'][$field->id];
                    //     foreach($list_values as $k => $item) {
                    //         if(isset($item['label']))
                    //             $list_values[$item['value']] = $item['label'];
                    //     }
                    //     if($field->is_plural) {
                    //         $old_list = $new_list = array();
                    //         $arr = json_decode($objects[$row['id']][$field->field], true);
                    //         if(is_array($arr)) {
                    //             $old_list = $arr;
                    //         } elseif(is_integer($arr)) {
                    //             $old_list[] = $arr;
                    //         }
                    //         foreach($old_list as $k => $val) {
                    //             if(isset($list_values[$val]))
                    //                 $old_list[$k] = isset($list_values[$val]['text']) ? $list_values[$val]['text'] : $list_values[$val];
                    //             else
                    //                 unset($old_list[$k]);
                    //         }
                            
                    //         if(is_array($row[$field->field])) {
                    //             $new_list = $row[$field->field];
                    //         } elseif(is_integer($row[$field->field])) {
                    //             $new_list[] = $row[$field->field];
                    //         }
                    //         foreach($new_list as $k => $val) {
                    //             if(isset($list_values[$val]))
                    //                 $new_list[$k] = isset($list_values[$val]['text']) ? $list_values[$val]['text'] : $list_values[$val];
                    //             else
                    //                 unset($new_list[$k]);
                    //         }
                    //         $old_value = implode(', ', $old_list);

                    //         $new_value = implode(', ', $new_list);
                    //         if($old_value != $new_value)
                    //             $history_text = $field->title.': '.$old_value.' -> '.$new_value;
                    //     } else {
                    //         $old_value = isset($list_values[$objects[$row['id']][$field->field]]) ? $list_values[$objects[$row['id']][$field->field]] : '';

                    //         $new_value = isset($row[$field->field][0]) && isset($list_values[$row[$field->field][0]]) ? $list_values[$row[$field->field][0]] : '';

                    //         $history_text = $field->title.': '.(is_array($old_value) && isset($old_value['text']) ? $old_value['text'] : $old_value).' -> '.(is_array($new_value) && isset($new_value['text']) ? $new_value['text'] : $new_value);
                    //     }
                    //     if($field->type == 'relation') {
                    //         $related_table = json_decode($field->details, true)['table'];
                    //         if($related_table && isset($settings['models'][$related_table])) {
                    //             if(is_array($row[$field->field]) && count($row[$field->field])) {
                    //                 $new_values = $row[$field->field];
                    //                 if(isset($arr) && is_array($arr)) 
                    //                     $new_values = array_diff($row[$field->field], $arr);
                    //                 if(count($new_values))
                    //                     \DB::table($related_table)->whereIntegerInRaw('id', $new_values)->update(['choosed_at' => \DB::raw('now()')]);
                    //             } elseif($row[$field->field]) {
                    //                 \DB::table($related_table)->where('id', $row[$field->field])->update(['choosed_at' => \DB::raw('now()')]);
                    //             }
                    //         }
                             
                    //     }
                    // } else {
                    //     if(is_array($row[$field->field])) {
                    //         if($field->type == 'address') {
                    //             $old_addr = json_decode($objects[$row['id']][$field->field], true);
                    //             $history_text = $field->title.': '.(isset($old_addr['text']) ? $old_addr['text'] : '').' -> '.$row[$field->field]['text'];
                    //         } elseif($field->type == 'file') {
                    //             $file_values = array();
                    //             foreach($row[$field->field] as $v) {
                    //                 if(isset($v['name']))
                    //                     $file_values[] = $v['name'];
                    //             }
                    //             $old_values = array();
                    //             if($objects[$row['id']][$field->field]) {
                    //                 $old_files = json_decode($objects[$row['id']][$field->field], true);
                    //                 foreach($old_files as $v) {
                    //                     if(isset($v['name']))
                    //                         $old_values[] = $v['name'];
                    //                 }
                    //             }
                    //             $history_text = $field->title.': '.implode(', ', $old_values).' -> '.implode(', ', $file_values);
                    //         } else {
                    //             $old_value = $objects[$row['id']][$field->field];
                    //             if($arr_value = json_decode($objects[$row['id']][$field->field], true)) {

                    //                 if($arr_value && isset($arr_value['value']) && isset($arr_value['external_link']) && $arr_value['external_link'] && is_array($arr_value)) {
                    //                     $old_value = implode(', ', array_values($arr_value));
                    //                 }
                    //                 elseif(is_array($arr_value) && isset($arr_value['value']))
                    //                     $old_value = $arr_value['value'];
                    //             }
                    //             $new_value = array_filter($row[$field->field], fn($value) => !is_null($value) && $value !== '');
                    //             $history_text = $field->title.': '.$old_value.' -> '.implode(', ', array_values($new_value));
                    //         }
                    //     } else {
                    //         $old_value = $objects[$row['id']][$field->field];
                    //         if($field->type == 'file') {
                    //             $old_values = array();
                    //             if($objects[$row['id']][$field->field]) {
                    //                 $old_files = json_decode($objects[$row['id']][$field->field], true);
                    //                 foreach($old_files as $v) {
                    //                     if(isset($v['name']))
                    //                         $old_values[] = $v['name'];
                    //                 }
                    //             }
                    //             $old_value = implode(', ', $old_values);
                    //         }

                    //         if($field->is_external_link) {
                    //             $arr_value = json_decode($objects[$row['id']][$field->field], true);
                    //             if(isset($arr_value['value']) && isset($arr_value['external_link']) && $arr_value['external_link']) {
                    //                 $old_value = implode(', ', array_values($arr_value));
                    //             }
                    //         }
                    //         $history_text = $field->title.': '.$old_value.' -> '.$row[$field->field];
                    //     }
                        
                    // }
                    

                    // if($field->type == 'relation' && $field->is_plural && $field->relation_table) {
                    //     sync_history($field, $new_value)
                    // }
                }
            }
        }

        $data = array('fields' => array());
        
        if(count($history_items) && $fire_event) {
            $data = \App\Models\History::getDataList($history_items);
            \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);
        }
        $objects_collection_by_key = array();
        if(isset($objects_collection))
            $objects_collection_by_key = $objects_collection->keyBy('id');
        if($new_object) {
            $objects_collection_by_key[0] = $new_object;
        }

        return array('by_key' => $objects_collection_by_key, 'changed_fields' => $changed_fields, 'data' => $data);
    }
    public static function createObject($slug, $object)
    {
        $settings = app('settings');//get_settings();
        $user_id = \Auth::user() ? \Auth::user()->id : 1;
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
        $history = new \App\Models\History(['entity' => $slug, 'event' => 'OBJECT_CREATED', 'entity_id' => $object['id'], 'user_id' => $user_id, 'text' => $history_text]);
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
                $history_text = '';
                if($field->type == 'redactor') {
                    // Не пишем в историю весь JSON detail_text (десятки КБ) — только факт.
                    $history_text = $object[$field->field] ? $field->title.': контент добавлен' : '';
                } elseif($field->type == 'status' && isset($settings['list_values'][$field->id])) {
                    $statuses = collect($settings['list_values'][$field->id]);
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
                        if($new_value && $new_value != $object[$field->field])
                            $history_text = $field->title.': '.$object[$field->field].' -> '.$new_value;
                    } else {
                        if($object[$field->field])
                            $history_text = $field->title.': -> '.$object[$field->field];
                    }
                } elseif(isset($settings['list_values'][$field->id])) {

                    $list_values = $settings['list_values'][$field->id];
                    foreach($list_values as $k => $item) {
                        if(isset($item['label']))
                            $list_values[$item['value']] = $item['label'];
                    }
                    if($field->is_plural) {
                        $new_list = array();
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

                        if($new_value)
                            $history_text = $field->title.': -> '.$new_value;
                    } else {
       
                        $new_value = isset($list_values[$object[$field->field]]) ? $list_values[$object[$field->field]] : '';
                        if((is_array($new_value) && isset($new_value['text']) ? $new_value['text'] : $new_value))
                            $history_text = $field->title.': -> '.(is_array($new_value) && isset($new_value['text']) ? $new_value['text'] : $new_value);
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
                            $history_text = $field->title.': -> '.$object[$field->field]['text'];
                        } elseif($field->type == 'file') {
                            $file_values = array();
                            foreach($object[$field->field] as $v) {
                                if(isset($v['name']))
                                    $file_values[] = $v['name'];
                            }
                            if(implode(', ', $file_values))
                                $history_text = $field->title.': -> '.implode(', ', $file_values);
                        } else {

                            $new_value = array_filter($object[$field->field], fn($value) => !is_null($value) && $value !== '');
                            
                            if(implode(', ', array_values($new_value)))
                                $history_text = $field->title.': -> '.implode(', ', array_values($new_value));
                        }
                    } else {
                        if($object[$field->field])
                            $history_text = $field->title.': -> '.$object[$field->field];
                    }
                    
                }
                if($history_text) {
                    //$module = $field->module;
                        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $object['id'], 'user_id' => $user_id, 'text' => $history_text, 'module' => null]);

                    $history->saveQuietly();
                    // if($module) {
                    //     $history_replic = $history->replicate();
                    //     $history_replic->module = null;
                    //     $history_replic->save();
                    // }
                    $history_items[] = $history;
                    
                }
            }
        }

        if(count($history_items)) {
            $data = \App\Models\History::getDataList($history_items);

            \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);
        }
    }

    public static function deleteObject($slug, $object)
    {
        $user_id = \Auth::user() ? \Auth::user()->id : 1;
        $history_text = 'Удалена запись: '.$object->id;
        $history = new \App\Models\History(['entity' => $slug, 'event' => 'OBJECT_DELETED', 'entity_id' => $object->id, 'user_id' => $user_id, 'text' => $history_text]);
        $history->save();
    }
}