<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Route;
use App\Models\Task;

class RouteController extends Controller
{
    public function list(Request $request) 
    {
        $limit = $request->per_page ? $request->per_page : 25;
        $page = $request->page ? $request->page : 1;
        $sort_field = $request->sort_field ? $request->sort_field : 'id';
        $sort_order = $request->sort_order ? $request->sort_order : 'desc';
        $settings = get_settings();
        $tenant = tenant('id');

        $entity = \DB::table('data_types')->where('slug', 'routes')->first();
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        if($sort_field == 'id')
            $paginator = $entity_class::orderByRaw("CAST($sort_field AS DECIMAL) $sort_order");
        else
            $paginator = $entity_class::orderBy($sort_field, $sort_order);

        if($request->date) {
            $paginator = $paginator->where('date', $request->date);
        } else {
            $paginator = $paginator->where('date', date('d.m.Y'));
        }

        $paginator = $paginator->paginate($limit);
        
        $objects = array();
        $field_values = array();
        
        foreach ($paginator->items() as $item) {
            $data = array(
                'id' => $item->id
            );
            foreach($model_fields as $field) {
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
                    $value = $item->{$field->field};
                    $data[$field->field] = $value;
                    if(!is_integer($value))
                        $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;

                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $data[$field->field];
                        $data[$field->field] = array();
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($settings['routes']['list_values'][$field->field][$val]))
                                    $data[$field->field][] = $settings['routes']['list_values'][$field->field][$val];
                            }
                        }
                    } elseif($field->type == 'relation' && isset($settings['routes']['list_values'][$field->field][$value])) {
                        $data[$field->field] = $settings['routes']['list_values'][$field->field][$value];
                    } elseif($field->type == 'relation') {
                        $data[$field->field] = null;
                    };
                }
            }
            
            $objects[] = $data;
        }
        $res = array(
            'count' => $paginator->count(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'data' => $objects
        );
        return response()->json($res);
    }

    public function store(Request $request)
    {
        $request->validate([
        ]);

        $entity = \DB::table('data_types')->where('slug', 'routes')->first();
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();

        $data = $request->all();
        $item = $entity_class::create($data);
        $item->name = $entity->display_name_singular.' #'.$item->id;
        $item->user_id = \Auth::user()->id;
        $item->date = date('d.m.Y');
        if($request->date)
            $item->date = $request->date;
        $item->save();

        $history_text = 'Создана запись: '.$item->id;
        $history = new \App\Models\History(['entity' => 'routes', 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
        $history->save();

        $history_text = 'Название: '.(isset($item->store_name) ? $item->store_name : $item->name);
        $history = new \App\Models\History(['entity' => 'routes', 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
        $history->save();

        $data = $item->getData();
        \App\Events\ObjectUpdated::dispatch('ObjectCreated', $data);
        $data = $history->getData();
        \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);

        return response()->json($item);
    }

    public function batch(Request $request)
    {
        $entity = \DB::table('data_types')->where('slug', 'routes')->first();
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();
        $settings = get_settings();

        $rows = $request->rows;
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
                    if($field->type == 'status' && isset($settings['routes']['list_values'][$field->field])) {
                        $statuses = collect($settings['routes']['list_values'][$field->field]);
                        $visible_statuses = $statuses->filter(function ($status) {
                                return !$status->is_hidden;
                            })->pluck('id')->toArray();
                        $old_status = $statuses->firstWhere('id', $objects[$row['id']][$field->field]);
                        $new_status = $statuses->firstWhere('id', $row[$field->field]);
                        
                        if($old_status && $new_status) {
                            if($old_status->is_hidden) {
                                $old_value = $old_status->color;
                            } else {
                                $old_value = $old_status->value ? $old_status->value : 'Значение '.(array_search($old_status->id, $visible_statuses)+1);
                            }
                            if($new_status->is_hidden) {
                                $new_value = $new_status->color;
                            } else {
                                $new_value = $new_status->value ? $new_status->value : 'Значение '.(array_search($new_status->id, $visible_statuses)+1);
                            }
                            $history_text = $field->display_name.': '.$old_value.' -> '.$new_value;
                        } elseif($new_status) {
                            if($new_status->is_hidden) {
                                $new_value = $new_status->color;
                            } else {
                                $new_value = $new_status->value ? $new_status->value : 'Значение '.(array_search($new_status->id, $visible_statuses)+1);
                            }
                            $history_text = $field->display_name.': '.$objects[$row['id']][$field->field].' -> '.$new_value;
                        } else {
                            $history_text = $field->display_name.': '.$objects[$row['id']][$field->field].' -> '.$row[$field->field];
                        }
                    } elseif(isset($settings['routes']['list_values'][$field->field])) {

                        $list_values = $settings['routes']['list_values'][$field->field];
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
                                    $old_list[$k] = isset($list_values[$val]['value']) ? $list_values[$val]['value'] : $list_values[$val];
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
                                    $new_list[$k] = isset($list_values[$val]['value']) ? $list_values[$val]['value'] : $list_values[$val];
                                else
                                    unset($new_list[$k]);
                            }

                            $old_value = implode(', ', $old_list);
                            $new_value = implode(', ', $new_list);

                            $history_text = $field->display_name.': '.$old_value.' -> '.$new_value;
                        } else {
                            $old_value = isset($list_values[$objects[$row['id']][$field->field]]) ? $list_values[$objects[$row['id']][$field->field]] : '';
                            $new_value = isset($list_values[$row[$field->field]]) ? $list_values[$row[$field->field]] : '';
                            $history_text = $field->display_name.': '.(is_array($old_value) ? $old_value['value'] : $old_value).' -> '.(is_array($new_value) ? $new_value['value'] : $new_value);
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
                                $history_text = $field->display_name.': '.$objects[$row['id']][$field->field].' -> '.implode(', ', array_values($row[$field->field]));
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
                            $history_text = $field->display_name.': '.$old_value.' -> '.$row[$field->field];
                        }
                        
                    }
                    if($history_text) {
                        $module = $field->module;
                        if(isset(\Auth::user()->id))
                            $history = new \App\Models\History(['entity' => 'routes', 'entity_id' => $row['id'], 'user_id' => \Auth::user()->id, 'text' => $history_text, 'module' => $module]);
                        else
                            $history = new \App\Models\History(['entity' => 'routes', 'entity_id' => $row['id'], 'user_id' => 0, 'text' => $history_text, 'module' => $module]);
                        $history->save();

                        $history_items[] = $history;
                        
                    }
                }
            }
            
            
        }
        if(count($history_items)) {
            $data = \App\Models\History::getDataList($history_items);
            \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);
        }
        $ids = array();
        $objects_collection_by_key = $objects_collection->keyBy('id');
        foreach($rows as $k => $row) {
            $id = $row['id'];
            $ids[] = $id;
            unset($row['id']);
            if(isset($row['password'])) {
                $row['password'] = Hash::make($row['password']);
            }

            $ob = $entity_class::where('id', $id)->first();
            foreach($row as $field => $value) {
                $ob->{$field} = $value;
            }
            $ob->save();

            $data = $ob->getData($changed_fields);
            \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);
        }
        foreach($request->rows as $row) {
            if(isset($row['role_id'])) {
                $objects_collection_by_key[$row['id']]->roles()->sync(array_values($row['role_id']));
            }
        }

        cache()->flush();
        

        return response()->json(['success' => true]);
    }

    public function delete(Request $request)
    {
        $entity = \DB::table('data_types')->where('slug', 'routes')->first();
        $entity_class = $entity->model_name;
        $items = $entity_class::whereIntegerInRaw('id', $request->ids)->delete();
        foreach($request->ids as $id) {
            $history_text = 'Удалена запись: '.$id;
            $history = new \App\Models\History(['entity' => 'routes', 'entity_id' => $id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
            $history->save();
            \App\Events\ObjectUpdated::dispatch('ObjectDeleted', $id);
        }

        return response()->json(['success' => true]);
    }

    public function tasks($id, Request $request) 
    {
        $tasks = Route::find($id)->tasks;
        $settings = get_settings();
        $tenant = tenant('id');

        $entity = \DB::table('data_types')->where('slug', 'tasks')->first();
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );
        
        $objects = array();
        $field_values = array();
        
        foreach ($tasks as $item) {
            $data = array(
                'id' => $item->id
            );
            foreach($model_fields as $field) {
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
                    $value = $item->{$field->field};
                    $data[$field->field] = $value;
                    if(!is_integer($value))
                        $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;

                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $data[$field->field];
                        $data[$field->field] = array();
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($settings['routes']['list_values'][$field->field][$val]))
                                    $data[$field->field][] = $settings['routes']['list_values'][$field->field][$val];
                            }
                        }
                    } elseif($field->type == 'relation' && isset($settings['routes']['list_values'][$field->field][$value])) {
                        $data[$field->field] = $settings['routes']['list_values'][$field->field][$value];
                    } elseif($field->type == 'relation') {
                        $data[$field->field] = null;
                    };
                }
            }
            
            $objects[] = $data;
        }
        $res = array(
            'count' => count($tasks),
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 25,
            'total' => count($tasks),
            'from' => 1,
            'to' => count($tasks),
            'data' => $objects
        );
        return response()->json($res);
    }

    public function update_tasks($id, Request $request) 
    {
        $old_tasks = Route::find($id)->tasks;
        $new_tasks = Task::whereIntegerInRaw('id', $request->ids)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->ids).")"))->get();

        foreach($old_tasks as $task) {
            if(!in_array($task->id, $request->ids)) {
                $task->route_id = null;
                $task->save();
            }
        }

        $i = 0;
        foreach($new_tasks as $task) {
            if($task->route_id == $id) {
                $task->sort = $i;
                $task->saveQuietly();
            } else {
                $task->route_id = $id;
                $task->sort = $i;
                $task->save();
            }

            $i++;
        }

        $settings = get_settings();
        $tenant = tenant('id');

        $entity = \DB::table('data_types')->where('slug', 'tasks')->first();
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );
        
        $objects = array();
        $field_values = array();
        
        foreach ($new_tasks as $item) {
            $data = array(
                'id' => $item->id
            );
            foreach($model_fields as $field) {
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
                    $value = $item->{$field->field};
                    $data[$field->field] = $value;
                    if(!is_integer($value))
                        $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;

                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $data[$field->field];
                        $data[$field->field] = array();
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($settings['routes']['list_values'][$field->field][$val]))
                                    $data[$field->field][] = $settings['routes']['list_values'][$field->field][$val];
                            }
                        }
                    } elseif($field->type == 'relation' && isset($settings['routes']['list_values'][$field->field][$value])) {
                        $data[$field->field] = $settings['routes']['list_values'][$field->field][$value];
                    } elseif($field->type == 'relation') {
                        $data[$field->field] = null;
                    };
                }
            }
            
            $objects[] = $data;
        }

        $res = array(
            'count' => count($new_tasks),
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 25,
            'total' => count($new_tasks),
            'from' => 1,
            'to' => count($new_tasks),
            'data' => $objects
        );
        return response()->json($res);
    }
}