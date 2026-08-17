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
    public function __construct()
    {
        $this->middleware('logistic.read')->except(['tasks', 'map_data', 'task_filter', 'tasks_view_fields']);
        $this->middleware('logistic.read:routes')->only(['tasks', 'map_data', 'task_filter', 'tasks_view_fields']);
    }

    /**
     * Поправочный коэффициент на пробки. Время в пути, которое отдаёт OSRM
     * (расчёт по свободной дороге), умножаем на него, чтобы время прибытия на
     * точки и общая длительность маршрута учитывали реальные пробки (8508).
     * Применяется только к driving-времени, не к service_time на точках.
     */
    const TRAFFIC_COEFFICIENT = 2;

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
        $field_perms = isset($settings['routes']['perms']) ? $settings['routes']['perms'] : array();

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
                if(isset($field_perms[$field->field]) && empty($field_perms[$field->field]['read']))
                    continue;
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
                    $value = $item->{$field->field};
                    $data[$field->field] = $value;
                    if(!is_integer($value))
                        $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;

                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $data[$field->field];
                        $route_list_values = \App\Models\Settings::resolve_list_values($settings, $field->id, is_array($values) ? $values : array($values));
                        $data[$field->field] = array();
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($route_list_values[$val]))
                                    $data[$field->field][] = $route_list_values[$val];
                            }
                        }
                    } elseif($field->type == 'relation' && ($route_option = \App\Models\Settings::list_option($settings, $field->id, $value))) {
                        $data[$field->field] = $route_option;
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
        $item->name = $entity->title_singular.' #'.$item->id;
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
                            $rows[$id][$field_name] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
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
                    if($field->type == 'status' && isset($settings['list_values'][$field->id])) {
                        $statuses = collect($settings['list_values'][$field->id]);
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
                            $history_text = $field->title.': '.$old_value.' -> '.$new_value;
                        } elseif($new_status) {
                            if($new_status->is_hidden) {
                                $new_value = $new_status->color;
                            } else {
                                $new_value = $new_status->value ? $new_status->value : 'Значение '.(array_search($new_status->id, $visible_statuses)+1);
                            }
                            $history_text = $field->title.': '.$objects[$row['id']][$field->field].' -> '.$new_value;
                        } else {
                            $history_text = $field->title.': '.$objects[$row['id']][$field->field].' -> '.$row[$field->field];
                        }
                    } elseif(isset($settings['list_values'][$field->id])) {

                        $list_values = $settings['list_values'][$field->id];
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

                            $history_text = $field->title.': '.$old_value.' -> '.$new_value;
                        } else {
                            $old_value = isset($list_values[$objects[$row['id']][$field->field]]) ? $list_values[$objects[$row['id']][$field->field]] : '';
                            $new_value = isset($list_values[$row[$field->field]]) ? $list_values[$row[$field->field]] : '';
                            $history_text = $field->title.': '.(is_array($old_value) ? $old_value['value'] : $old_value).' -> '.(is_array($new_value) ? $new_value['value'] : $new_value);
                        }
                    } else {
                        if(is_array($row[$field->field])) {
                            if($field->type == 'address') {
                                $old_addr = json_decode($objects[$row['id']][$field->field], true);
                                $history_text = $field->title.': '.(isset($old_addr['text']) ? $old_addr['text'] : '').' -> '.$row[$field->field]['text'];
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
                                
                                $history_text = $field->title.': '.implode(', ', $old_values).' -> '.implode(', ', $file_values);
                            } else {
                                $history_text = $field->title.': '.$objects[$row['id']][$field->field].' -> '.implode(', ', array_values($row[$field->field]));
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
                            $history_text = $field->title.': '.$old_value.' -> '.$row[$field->field];
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
            $history = new \App\Models\History(['entity' => 'routes', 'entity_id' => $id, 'user_id' => \Auth::user()->id, 'text' => $history_text, 'event' => 'OBJECT_DELETED']);
            $history->save();
            \App\Events\ObjectUpdated::dispatch('ObjectDeleted', $id);
        }

        // Сброс кэша настроек обязателен: list_values (опции relation-полей)
        // строятся по живым записям и кэшируются в memcached. Без сброса
        // удалённый маршрут продолжал отображаться в связях задач логистики
        // до естественного устаревания кэша (CrudService::delete кэш чистит,
        // а этот эндпоинт — не чистил).
        \App\Models\Settings::clear_cache();

        return response()->json(['success' => true]);
    }

    public function tasks($id, Request $request)
    {
        // Маршрут мог быть удалён (soft delete) — Route::find вернёт null.
        // Не падаем 500: отдаём пустой набор задач, фронт спокойно покажет
        // пустые «Задачи в машине» и карту.
        $route = Route::find($id);
        if(!$route) {
            return response()->json([
                'count' => 0, 'current_page' => 1, 'last_page' => 1,
                'per_page' => 0, 'total' => 0, 'from' => null, 'to' => null,
                'data' => [],
            ]);
        }
        // Подгружаем связь status (point_status → FieldValue), чтобы отдать цвет
        // статуса точки на карту (квадратик статуса у маркера маршрута).
        $tasks = $route->tasks()->with('status')->get();
        $settings = get_settings();
        $tenant = tenant('id');

        $entity = \DB::table('data_types')->where('slug', 'logistic_tasks')->first();
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        // Берём поля из свежего get_settings (как EntityObject::list во внешней
        // ссылке), а не из отдельно кэшируемого getFields — иначе после
        // добавления поля (например «Оплата, руб») его не было в выдаче, пока
        // не сброшен кэш getFields, и у админа поле выводилось пустым, хотя во
        // внешней ссылке значение было (8579).
        $model_fields = !empty($settings['logistic_tasks']['fields'])
            ? collect($settings['logistic_tasks']['fields'])->values()
            : $entity_class::getFields();

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );
        $field_perms = isset($settings['logistic_tasks']['perms']) ? $settings['logistic_tasks']['perms'] : array();

        $objects = array();
        $field_values = array();

        foreach ($tasks as $item) {
            $data = array(
                'id' => $item->id
            );
            foreach($model_fields as $field) {
                if(isset($field_perms[$field->field]) && empty($field_perms[$field->field]['read']))
                    continue;
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
                    $value = $item->{$field->field};
                    $data[$field->field] = $value;
                    if(!is_integer($value))
                        $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;

                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $data[$field->field];
                        $route_list_values = \App\Models\Settings::resolve_list_values($settings, $field->id, is_array($values) ? $values : array($values));
                        $data[$field->field] = array();
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($route_list_values[$val]))
                                    $data[$field->field][] = $route_list_values[$val];
                            }
                        }
                    } elseif($field->type == 'relation' && ($route_option = \App\Models\Settings::list_option($settings, $field->id, $value))) {
                        $data[$field->field] = $route_option;
                    } elseif($field->type == 'relation') {
                        $data[$field->field] = null;
                    };
                }
            }

            // Цвет статуса точки (для квадратика статуса на карте).
            $data['statusColor'] = $item->status->color ?? '#ccc';

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

    public function map_data($id, Request $request)
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        $colorValue = null;

        $carId = $route->car_id;
        if (is_string($carId) && ValueHelper::isJson($carId)) {
            $decoded = json_decode($carId, true);
            $carId = is_array($decoded) ? ($decoded[0] ?? null) : $carId;
        }
        if (is_array($carId)) {
            $carId = $carId[0] ?? null;
        }
        if ($carId && \Schema::hasColumn('cars', 'color_status')) {
            $carColorId = \DB::table('cars')->where('id', $carId)->value('color_status');
            if ($carColorId && is_numeric($carColorId)) {
                $colorValue = \DB::table('field_values')->where('id', $carColorId)->value('color');
            }
        }

        if (!$colorValue && $route->employee_id && \Schema::hasColumn('employees', 'color_status')) {
            $employeeId = $route->employee_id;
            if (is_string($employeeId) && ValueHelper::isJson($employeeId)) {
                $decoded = json_decode($employeeId, true);
                $employeeId = is_array($decoded) ? ($decoded[0] ?? null) : $employeeId;
            }
            if (is_array($employeeId)) {
                $employeeId = $employeeId[0] ?? null;
            }
            if ($employeeId) {
                $empColor = \DB::table('employees')->where('id', $employeeId)->value('color_status');
                if ($empColor && is_numeric($empColor)) {
                    $colorValue = \DB::table('field_values')->where('id', $empColor)->value('color');
                } elseif (is_string($empColor) && $empColor !== '') {
                    $colorValue = $empColor;
                }
            }
        }

        return response()->json([
            'actual_path' => [],
            'loading_time' => $route->loading_time ?? '07:00',
            'color' => $colorValue ?: '#b6b6b6',
            'name' => $route->name,
        ]);
    }

    private function writeRouteTasksHistory($route, array $oldIds, array $newIds)
    {
        $settings = get_settings();
        $field = collect($settings['routes']['fields'] ?? [])->firstWhere('field', 'task_id');
        if (!$field) {
            return;
        }

        $old = \App\Models\Field::getHumanValue($field, $oldIds);
        $new = \App\Models\Field::getHumanValue($field, $newIds);
        if ($old['res'] == $new['res']) {
            return;
        }

        $base = [
            'entity' => 'routes',
            'entity_id' => $route->id,
            'user_id' => \Auth::user() ? \Auth::user()->id : 1,
            'field' => 'task_id',
            'old_value' => implode(', ', $old['values']),
            'new_value' => implode(', ', $new['values']),
            'is_relation' => 1,
        ];

        $history = new \App\Models\History($base + [
            'text' => $field->title . ': ' . $old['res'] . ' -> ' . $new['res'],
            'event' => 'FIELD_UPDATED',
        ]);
        $history->saveQuietly();

        $removed = array_diff($old['arr_res'], $new['arr_res']);
        if (count($removed)) {
            $history = new \App\Models\History($base + [
                'text' => $field->title . ', удалена связь: ' . implode(', ', $removed),
                'event' => 'RELATION_DELETED',
                'color' => '#C74822',
            ]);
            $history->saveQuietly();
        }

        $added = array_diff($new['arr_res'], $old['arr_res']);
        if (count($added)) {
            $history = new \App\Models\History($base + [
                'text' => $field->title . ', добавлена связь: ' . implode(', ', $added),
                'event' => 'RELATION_ADDED',
                'color' => '#23704B',
            ]);
            $history->saveQuietly();
        }
    }

    private function taskEmployeeIds($task): array
    {
        $raw = $task->employee_id;
        if (is_array($raw)) {
            $decoded = $raw;
        } elseif (is_numeric($raw)) {
            return (int) $raw ? [(int) $raw] : [];
        } elseif (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return [];
            }
        } else {
            return [];
        }
        $ids = [];
        foreach ($decoded as $v) {
            if (is_numeric($v) && (int) $v) {
                $ids[] = (int) $v;
            }
        }
        return $ids;
    }

    private function writeTaskEmployeeHistory($taskId, array $oldIds, array $newIds)
    {
        sort($oldIds);
        sort($newIds);
        if ($oldIds === $newIds) {
            return;
        }
        $settings = get_settings();
        $field = collect($settings['logistic_tasks']['fields'] ?? [])->firstWhere('field', 'employee_id');
        if (!$field) {
            return;
        }

        $old = \App\Models\Field::getHumanValue($field, $oldIds);
        $new = \App\Models\Field::getHumanValue($field, $newIds);
        if ($old['res'] == $new['res']) {
            return;
        }

        $user_id = \Auth::user() ? \Auth::user()->id : 1;
        $base = [
            'entity' => 'logistic_tasks',
            'entity_id' => $taskId,
            'user_id' => $user_id,
            'field' => 'employee_id',
            'old_value' => implode(', ', $old['values']),
            'new_value' => implode(', ', $new['values']),
            'is_relation' => 1,
        ];

        $history = new \App\Models\History($base + [
            'text' => $field->title.': '.$old['res'].' -> '.$new['res'],
            'event' => 'FIELD_UPDATED',
        ]);
        $history->saveQuietly();

        $removed = array_diff($old['arr_res'], $new['arr_res']);
        if (count($removed)) {
            $history = new \App\Models\History($base + [
                'text' => $field->title.', удалена связь: '.implode(', ', $removed),
                'event' => 'RELATION_DELETED',
                'color' => '#C74822',
            ]);
            $history->saveQuietly();
        }

        $added = array_diff($new['arr_res'], $old['arr_res']);
        if (count($added)) {
            $history = new \App\Models\History($base + [
                'text' => $field->title.', добавлена связь: '.implode(', ', $added),
                'event' => 'RELATION_ADDED',
                'color' => '#23704B',
            ]);
            $history->saveQuietly();
        }
    }

    private function writeTaskRouteHistory(array $taskIds, $route, bool $added)
    {
        if (!count($taskIds)) {
            return;
        }
        $settings = get_settings();
        $field = collect($settings['logistic_tasks']['fields'] ?? [])->firstWhere('field', 'route_id');
        $title = $field->title ?? 'Маршрут';
        $user_id = \Auth::user() ? \Auth::user()->id : 1;
        $name = $route->name ?: 'Маршрут #'.$route->id;
        $link = "<span data-slug='routes' data-id='{$route->id}'>{$name}</span>";

        foreach ($taskIds as $taskId) {
            $base = [
                'entity' => 'logistic_tasks',
                'entity_id' => $taskId,
                'user_id' => $user_id,
                'field' => 'route_id',
                'old_value' => $added ? '' : $name,
                'new_value' => $added ? $name : '',
                'is_relation' => 1,
            ];

            $history = new \App\Models\History($base + [
                'text' => $title.': '.($added ? '' : $link).' -> '.($added ? $link : ''),
                'event' => 'FIELD_UPDATED',
            ]);
            $history->saveQuietly();

            $history = new \App\Models\History($base + [
                'text' => $title.', '.($added ? 'добавлена связь: ' : 'удалена связь: ').$link,
                'event' => $added ? 'RELATION_ADDED' : 'RELATION_DELETED',
                'color' => $added ? '#23704B' : '#C74822',
            ]);
            $history->saveQuietly();
        }
    }

    public function update_tasks($id, Request $request)
    {
        $route = Route::find($id);
        if(!$route) {
            return response()->json(['code' => 404, 'error' => 'Маршрут не найден или удалён'], 404);
        }
        $old_tasks = $route->tasks;
        // Пустой ids = из маршрута убрали последнюю задачу. В этом случае
        // implode даёт "FIELD(id, )" — невалидный SQL, поэтому new_tasks
        // оставляем пустой коллекцией: ниже все old_tasks отвяжутся (route_id=null).
        $new_tasks = collect();
        if (count($request->ids)) {
            $new_tasks = Task::whereIntegerInRaw('id', $request->ids)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->ids).")"))->get();
        }

        $currentTaskIds = $old_tasks->pluck('id')->map(fn ($v) => (int) $v)->values()->toArray();
        $requestedTaskIds = array_values(array_map('intval', (array) $request->ids));
        if ($currentTaskIds !== $requestedTaskIds) {
            $this->writeRouteTasksHistory($route, $currentTaskIds, $requestedTaskIds);
        }

        $routeEmployeeIds = $route->employeeIds();
        $detachedTaskIds = [];
        $attachedTaskIds = [];

        foreach($old_tasks as $task) {
            if(!in_array($task->id, $request->ids)) {
                $oldEmployeeIds = $this->taskEmployeeIds($task);
                $task->route_id = null;
                if (count($routeEmployeeIds)) {
                    $rest = array_values(array_diff($oldEmployeeIds, $routeEmployeeIds));
                    $task->employee_id = count($rest) ? $rest : null;
                }
                $task->save();
                if (count($routeEmployeeIds) && \Schema::hasTable('logistic_task_employee')) {
                    $task->employees()->detach($routeEmployeeIds);
                }
                $this->writeTaskEmployeeHistory($task->id, $oldEmployeeIds, $this->taskEmployeeIds($task));
                $detachedTaskIds[] = $task->id;
            }
        }

        $i = 0;
        foreach($new_tasks as $task) {
            if($task->route_id == $id) {
                $task->sort = $i;
                $task->saveQuietly();
            } else {
                $oldEmployeeIds = $this->taskEmployeeIds($task);
                $task->route_id = $id;
                $task->sort = $i;
                $task->save();
                $this->writeTaskEmployeeHistory($task->id, $oldEmployeeIds, $this->taskEmployeeIds($task));
                $attachedTaskIds[] = $task->id;
            }

            $i++;
        }

        $this->writeTaskRouteHistory($detachedTaskIds, $route, false);
        $this->writeTaskRouteHistory($attachedTaskIds, $route, true);

        $settings = get_settings();
        $tenant = tenant('id');

        $entity = \DB::table('data_types')->where('slug', 'logistic_tasks')->first();
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
        $field_perms = isset($settings['logistic_tasks']['perms']) ? $settings['logistic_tasks']['perms'] : array();

        $objects = array();
        $field_values = array();

        foreach ($new_tasks as $item) {
            $data = array(
                'id' => $item->id
            );
            foreach($model_fields as $field) {
                if(isset($field_perms[$field->field]) && empty($field_perms[$field->field]['read']))
                    continue;
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
                    $value = $item->{$field->field};
                    $data[$field->field] = $value;
                    if(!is_integer($value))
                        $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;

                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $data[$field->field];
                        $route_list_values = \App\Models\Settings::resolve_list_values($settings, $field->id, is_array($values) ? $values : array($values));
                        $data[$field->field] = array();
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($route_list_values[$val]))
                                    $data[$field->field][] = $route_list_values[$val];
                            }
                        }
                    } elseif($field->type == 'relation' && ($route_option = \App\Models\Settings::list_option($settings, $field->id, $value))) {
                        $data[$field->field] = $route_option;
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

        // Recalculate route statistics
        $route = Route::find($id);

        // Number of tasks
        $route->number_tasks = count($new_tasks);

        if (empty($route->loading_time)) {
            $start = $this->parseTimeWindowStart(optional($new_tasks->first())->time);
            if ($start) {
                $route->loading_time = $start;
            }
        }

        $route->weight = $route->tasks()->sum('weight');

        // Mileage and time — calculate via OSRM if tasks have addresses
        $addresses = [];
        $totalServiceTime = 0;
        foreach ($new_tasks as $task) {
            $address = json_decode($task->address, true);
            if ($address && isset($address['coords']) && count($address['coords']) == 2) {
                $addresses[] = $address['coords'][1] . ',' . $address['coords'][0];
            }
            $totalServiceTime += (int) ($task->service_time ?? 0); // service_time in minutes
        }

        if (count($addresses) >= 2) {
            $coordsStr = implode(';', $addresses);
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "http://compas-osrm.ru:5000/route/v1/driving/{$coordsStr}?overview=false");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $data = curl_exec($ch);
                curl_close($ch);
                
                $osrm = json_decode($data, true);
                if ($osrm && $osrm['code'] === 'Ok' && isset($osrm['routes'][0])) {
                    $route->mileage = (int) round($osrm['routes'][0]['distance'] / 1000);
                    $route->time = round($osrm['routes'][0]['duration'] * self::TRAFFIC_COEFFICIENT / 60) + $totalServiceTime; // driving (с поправкой на пробки) + service
                }
            } catch (\Exception $e) {
                // OSRM unavailable — at least save service time
                $route->time = $totalServiceTime;
            }
        } else {
            $route->mileage = 0;
            $route->time = $totalServiceTime;
        }

        if (count($addresses) >= 2 && isset($osrm) && $osrm && $osrm['code'] === 'Ok') {
            $legs = $osrm['routes'][0]['legs'] ?? [];
            $loadingTime = $route->loading_time ?? '07:00';
            $currentTime = \Carbon\Carbon::createFromFormat('H:i', $loadingTime);
            
            foreach ($new_tasks as $index => $task) {
                if ($index > 0 && isset($legs[$index - 1])) {
                    $travelMinutes = round($legs[$index - 1]['duration'] * self::TRAFFIC_COEFFICIENT / 60);
                    $currentTime->addMinutes($travelMinutes);
                }
                $task->plan_time = $currentTime->format('H:i');
                $task->saveQuietly();

                // $res['data'] собран ВЫШE (до пересчёта) со старым plan_time,
                // поэтому патчим его свежим значением — иначе фронт показывает
                // прежнее время прибытия и правка маршрута «ничего не меняет» (8508).
                foreach ($res['data'] as &$row) {
                    if (($row['id'] ?? null) == $task->id) {
                        $row['plan_time'] = $task->plan_time;
                        break;
                    }
                }
                unset($row);

                $serviceTime = (int) ($task->service_time ?? 0);
                $currentTime->addMinutes($serviceTime);
            }
        }

        // Fix tasks with null or deleted status — set default status
        $statusField = collect(get_settings()['logistic_tasks']['fields'])->where('field', 'point_status')->first();
        if ($statusField) {
            $validStatuses = \DB::table('field_values')
                ->where('field_id', $statusField->id)
                ->orderBy('sort')
                ->pluck('id')
                ->toArray();
            
            if (count($validStatuses)) {
                $defaultStatus = $validStatuses[0];
                
                foreach ($new_tasks as $task) {
                    if (!$task->point_status || !in_array($task->point_status, $validStatuses)) {
                        $task->point_status = $defaultStatus;
                        $task->saveQuietly();
                    }
                }
            }
        }

        if ($currentTaskIds === $requestedTaskIds) {
            $route->timestamps = false;
        }
        $route->save();
        $route->timestamps = true;

        return response()->json($res);
    }

    public function task_filter($id, Request $request)
    {
        $route = Route::find($id);

        return response()->json($route->getTaskFilters());
    }

    /**
     * Настройки полей вкладки «Маршрут списком» (RouteTasksView): какие колонки
     * задачи показывать и в каком порядке. Хранятся ОДНИМ общим конфигом на
     * портал (user_id=null), чтобы внешняя ссылка (анонимный зритель) показывала
     * ровно то же, что настроено внутри (8579).
     */
    public function tasks_view_fields()
    {
        return response()->json(['fields' => self::getTasksViewFields()]);
    }

    public static function getTasksViewFields(): array
    {
        $row = \DB::table('settings')
            ->where('type', 'route_tasks_view_fields')
            ->whereNull('user_id')
            ->first();
        if ($row && $row->value) {
            $decoded = json_decode($row->value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    public function save_tasks_view_fields(Request $request)
    {
        $fields = $request->input('fields', []);
        if (!is_array($fields)) {
            $fields = [];
        }
        $clean = [];
        foreach ($fields as $f) {
            if (!isset($f['key'])) {
                continue;
            }
            $clean[] = [
                'key'     => (string) $f['key'],
                'enabled' => !empty($f['enabled']),
                'sort'    => (int) ($f['sort'] ?? 0),
            ];
        }

        \DB::table('settings')->updateOrInsert(
            ['type' => 'route_tasks_view_fields', 'entity' => null, 'user_id' => null],
            ['key' => 'route_tasks_view_fields', 'value' => json_encode($clean, JSON_UNESCAPED_UNICODE)]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Создать задачу логистики из адреса «Справочника адресов» и (опционально)
     * прикрепить её к маршруту.
     *
     * Адрес-источник НЕ изменяется и НЕ удаляется — у него нет привязки к
     * маршруту. Создаётся новая запись logistic_tasks, поля которой
     * заполняются из адреса. Используется при drag&drop адреса в «Задачи в
     * машине» или на строку маршрута в таблице «Маршруты».
     *
     * Body: { address_id, route_id? }
     */
    public function task_from_address(Request $request)
    {
        $address = \App\Models\Address::find($request->address_id);
        if (!$address) {
            return response()->json(['code' => 404, 'error' => 'Адрес не найден'], 404);
        }

        $routeId = $request->route_id ? (int) $request->route_id : null;

        // Скалярные/JSON-поля копируем через CrudService (история + field_values).
        // client_id (плюральная связь) обрабатываем отдельно ниже, чтобы не
        // зависеть от формата хранения у адреса.
        $crud = app(\App\Services\CrudService::class);
        $row = [
            'id'                    => 0,
            'name'                  => $address->name,
            'address'               => $address->address,           // JSON-строка координат — пишется как есть
            'phone'                 => $address->phone,
            'time'                  => $address->time,
            'car_requirements'      => $address->car_requirements,
            'employee_requirements' => $address->employee_requirements,
            'service_time'          => $address->service_time,
            'photo'                 => $address->photo,
            'user_id'               => \Auth::id() ?? $address->user_id,
            'comment'               => $address->comment,
            'contact'               => $address->contact,
            'weight'                => $address->weight ?? null,
            'delivery_price'        => $address->delivery_price ?? null,
        ];

        $result = $crud->batch('logistic_tasks', [$row]);
        $newId = $result['id'] ?? null;

        if (!$newId) {
            return response()->json(['code' => 500, 'error' => 'Не удалось создать задачу'], 500);
        }

        \App\Models\ObjectRelation::link('addresses', $address->id, 'logistic_tasks', $newId);

        if ($address->client_id) {
            $task = Task::find($newId);
            if ($task) {
                $task->client_id = $address->client_id;
                $task->saveQuietly();
            }
        }

        // Прикрепление к маршруту — переиспользуем update_tasks (sort + пересчёт
        // километража/времени/веса маршрута). Добавляем задачу в конец списка.
        if ($routeId) {
            $ids = Task::where('route_id', $routeId)->orderBy('sort')->pluck('id')->toArray();
            $ids[] = $newId;
            return $this->update_tasks($routeId, new Request(['ids' => $ids]));
        }

        return response()->json(['success' => true, 'id' => $newId]);
    }

    /**
     * Создаёт задачу логистики из записи «Быстрые задачи» (warehouses) и при
     * наличии route_id прикрепляет к маршруту. Полный аналог task_from_address.
     *
     * Body: { warehouse_id, route_id? }
     */
    public function task_from_warehouse(Request $request)
    {
        $warehouse = \App\Models\Warehouse::find($request->warehouse_id);
        if (!$warehouse) {
            return response()->json(['code' => 404, 'error' => 'Быстрая задача не найдена'], 404);
        }

        $routeId = $request->route_id ? (int) $request->route_id : null;

        $crud = app(\App\Services\CrudService::class);
        $row = [
            'id'                    => 0,
            'name'                  => $warehouse->name,
            'address'               => $warehouse->address,
            'phone'                 => $warehouse->phone,
            'time'                  => $warehouse->time,
            'car_requirements'      => $warehouse->car_requirements,
            'employee_requirements' => $warehouse->employee_requirements,
            'service_time'          => $warehouse->service_time,
            'photo'                 => $warehouse->photo,
            'user_id'               => \Auth::id() ?? $warehouse->user_id,
            'comment'               => $warehouse->comment,
            'contact'               => $warehouse->contact,
            'weight'                => $warehouse->weight ?? null,
            'delivery_price'        => $warehouse->delivery_price ?? null,
            'volume'                => $warehouse->volume ?? null,
        ];

        $result = $crud->batch('logistic_tasks', [$row]);
        $newId = $result['id'] ?? null;

        if (!$newId) {
            return response()->json(['code' => 500, 'error' => 'Не удалось создать задачу'], 500);
        }

        if ($warehouse->client_id) {
            $task = Task::find($newId);
            if ($task) {
                $task->client_id = $warehouse->client_id;
                $task->saveQuietly();
            }
        }

        if ($routeId) {
            $ids = Task::where('route_id', $routeId)->orderBy('sort')->pluck('id')->toArray();
            $ids[] = $newId;
            return $this->update_tasks($routeId, new Request(['ids' => $ids]));
        }

        return response()->json(['success' => true, 'id' => $newId]);
    }

    private function parseTimeWindowStart($time)
    {
        if (!$time) {
            return null;
        }
        $part = explode('-', (string) $time)[0];
        if (preg_match('/(\d{1,2}):(\d{2})/', $part, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        return null;
    }
}