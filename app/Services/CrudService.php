<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CrudService
{
    public function batch(string $slug, array $rows)
    {

        $settings = \App\Models\Settings::get();
        $user_id = \Auth::user() ? \Auth::user()->id : 1;

        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {

            return [
                'message' => 'Entity not found',
                'status' => 404
            ];
        }
        foreach($rows as $k => $row) {
            $rows[$k]['id'] = (int)$row['id'];
        }
        info('$rows');
        info($rows);
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];
        foreach($rows as $k => $row) {
            if($slug == 'users' && $row['id'] == 1 && array_key_exists('is_admin', $row) && !$row['is_admin']) {
                return [
                    'message' => 'Вы не можете убрать права администратора базовому пользователю',
                    'status' => 403
                ];
            }
            if ($slug == 'logistic_tasks' && array_key_exists('delivery_date', $row) && $row['id'] && !isset($row['copy'])) {
                // Проверяем, что задача привязана к СУЩЕСТВУЮЩЕМУ маршруту.
                // Route использует SoftDeletes: после удаления маршрута route_id
                // у задачи остаётся, но самого маршрута уже нет. Route::where()
                // учитывает SoftDeletes-скоуп (удалённые не считаются) — иначе
                // delivery_date блокировалась бы навсегда у осиротевших задач
                // («Дату доставки нужно менять у связанного маршрута»).
                $route_id = $entity_class::where('id', $row['id'])
                    ->whereNotNull('route_id')
                    ->value('route_id');
                $is_routed = $route_id && \App\Models\Route::where('id', $route_id)->exists();

                if ($is_routed) {
                    return [
                        'message' => 'Дату доставки нужно менять у связанного маршрута',
                        'status' => 403
                    ];
                }
            }
            // --- НАЧАЛО НОВЫХ ИЗМЕНЕНИЙ (АВТОЗАПОЛНЕНИЕ COMPANY_ID ДЛЯ ROUTES) ---
            if ($slug == 'routes' && $row['id'] == 0 && (!isset($row['company_id']) || !$row['company_id'])) {
                $found_company_id = null;

                // 1. Пробуем найти через сотрудника
                if (isset($row['employee_id']) && $row['employee_id']) {
                    // Нормализуем ID, так как с фронта может прийти массив или ['value' => id]
                    $emp_id = $row['employee_id'];
                    if (is_array($emp_id)) {
                        $emp_id = $emp_id['value'] ?? ($emp_id[0] ?? null);
                    }

                    if ($emp_id) {
                        $employee = \App\Models\Employee::find((int)$emp_id);
                        // Теперь $employee точно модель, а не коллекция
                        if ($employee && $employee->company_id) {
                            $found_company_id = $employee->company_id;
                        }
                    }
                }

                // 2. Если не нашли, пробуем найти через машину
                if (!$found_company_id && isset($row['car_id']) && $row['car_id']) {
                    // Нормализуем ID машины
                    $car_id = $row['car_id'];
                    if (is_array($car_id)) {
                        $car_id = $car_id['value'] ?? ($car_id[0] ?? null);
                    }

                    if ($car_id) {
                        $car = \App\Models\Car::find((int)$car_id);
                        if ($car && $car->company_id) {
                            $found_company_id = $car->company_id;
                        }
                    }
                }

                // Если нашли компанию, записываем её в массив rows
                if ($found_company_id) {
                    $rows[$k]['company_id'] = $found_company_id;
                    $row['company_id'] = $found_company_id; 
                }
            }
            // --- КОНЕЦ НОВЫХ ИЗМЕНЕНИЙ ---
            foreach($row as $field => $value) {
                $val = $value;
                if(is_array($val)) {
                    $val = isset($val['value']) ? $val['value'] : (isset($val['test']) ? $val['text'] : $value);
                }
                if(isset($model_fields[$field]->is_unique) && $model_fields[$field]->is_unique && $field != 'id' && $val) {
                    if($entity_class::withTrashed()->where($field, $val)->where('id', '!=', $row['id'])->exists() || 
                        $entity_class::withTrashed()->where("{$field}->value", $val)->where('id', '!=', $row['id'])->exists() || 
                        isset($row['copy']) && ($entity_class::withTrashed()->where("{$field}->value", $val)->exists() || $entity_class::withTrashed()->where($field, $val)->exists())) {
                        return [
                            'message' =>  "Поле {$model_fields[$field]->title} должно быть уникальным",
                            'status' => 409
                        ];
                    }
                }
            }
            if(isset($row['copy'])) {
                $obj = $entity_class::findOrFail($row['id']);
                unset($row['id']);
                unset($row['copy']);

                $data = \App\Models\EntityObject::copy($slug, $obj, $row);
                $data['status'] = 200;

                return $data;
            }

            
        }

        $tariff = \App\Models\Tariff::current();
        $restrictions = [];
        if($tariff->restrictions) {
            $restrictions_tariff = json_decode($tariff->restrictions,true);

            if(isset($restrictions_tariff['objects'][$slug])) {
                $restrictions = $restrictions_tariff['objects'][$slug];
            }
            
        }
        $new_object = null;
        $ids = array();
        $history_response_fields = $history_response_events = array();
        $copied_ids = array();
        foreach($rows as $k => $row) {
            if($row['id'] == 0) {
                if(isset($restrictions['count']) && $restrictions['count'] <= $entity_class::count()) {
                    return [
                        'message' => "По вашему тарифу допустимо создать {$restrictions['count']} объектов данного типа",
                        'status' => 403
                    ];
                };
                if($slug == 'gibdd_queries' && (!isset($row['name']) || !$row['name']))
                    continue;
                $new_object = $entity_class::create(['name' => '']);
                $rows[$k]['created_at'] = $new_object->created_at;
                $rows[$k]['updated_at'] = $new_object->created_at;
                $rows[$k]['id'] = $new_object->id;
                $rows[$k]['is_new'] = true;

                $ids[] = $new_object->id;
                if(!isset($row['name']) || !$row['name'])
                    $rows[$k]['name'] = $entity->title_singular.' #'.$new_object->id;
            } else {
                $ids[] = $row['id'];
            }
            if(isset($row['copy'])) {
                if(isset($restrictions['count']) && $restrictions['count'] <= $entity_class::count()) {
                    return [
                        'message' => "По вашему тарифу допустимо создать {$restrictions['count']} объектов данного типа",
                        'status' => 403
                    ];
                };
                $copied_ids[] = $row['id'];
            }
        };
        $objects = array();
        if(count($ids)) {
            $objects_collection = $entity_class::whereIntegerInRaw('id', $ids)->get();
            $objects = $objects_collection->keyBy('id');
        }
        if($new_object)
            $objects[0] = $new_object;

        $history_items = array();
        
        $old_relations = array();
        $new_relations = array();
        foreach($rows as $k => $row) {
            foreach($row as $field => $value) {
                if($field == 'copy' || $field == 'is_new')
                    continue;
                $ob = $objects[$row['id']];
                
                if(isset($model_fields[$field]) && $model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural && $model_fields[$field]->relation_table) {

                    $relation_table = $model_fields[$field]->relation_table;
                    if(!isset($old_relations[$row['id']]))
                        $old_relations[$row['id']] = array('field' => $field, 'entities' => array());
                    if(!isset($new_relations[$row['id']]))
                        $new_relations[$row['id']] = array('field' => $field, 'entities' => array());
                    $rel = $ob->{$relation_table};
                    $old_relations[$row['id']]['entities'][$relation_table] = $rel ? $rel->pluck('id')->toArray() : [];
                    $new_relations[$row['id']]['entities'][$relation_table] = array_filter($value, 'is_int');
                }
                
            }
        }
        foreach($new_relations as $id => $change_relations) {
            foreach($change_relations['entities'] as $relation_table => $relations) {
                $related_key = $model_fields[$change_relations['field']]->related_field;

                $entity_relation = $settings['models'][$relation_table];
                $entity_relation_class = $entity_relation->model_name;
                $ids = array_unique(array_merge($relations, $old_relations[$id]['entities'][$relation_table]));
                $relation_objects = $entity_relation_class::whereIntegerInRaw('id', $ids)->get();
                $i = 0;
                foreach($relation_objects as $related_item) {
                    if(isset($related_item->{$slug})) {
                        
                        $values = $related_item->{$slug}->pluck('id')->toArray();
                        if(in_array($related_item->id, $relations) && !in_array($id, $values)) {
                            $values[] = $id;
                            if($relation_table == 'logistic_tasks') {
                                $related_item->sort = $i;
                                $related_item->saveQuietly();
                                $i++;
                            }
                            $related_item->sync_history($related_key, $values);
                        } elseif(!in_array($related_item->id, $relations) && in_array($id, $values)) {
                            unset($values[array_search($id, $values)]);
                            $related_item->sync_history($related_key, $values);
                        }
                    }
                }
            }
        }
        foreach($objects as $k => $item) {
            if(in_array($item->id, $copied_ids)) {
                $current = $item->replicate();
                $current->saveQuietly();
                $copy = $current;
                $objects[$current->id] = $current;
                foreach($rows as $r => $row) {
                    if($row['id'] == $item->id) {
                        $rows[$r]['user_id'] = $user_id;
                    }
                }

                if(is_array($item->name))
                    $val = $item->name['value'];
                elseif(!is_array($item->name) && json_decode($item->name, true))
                    $val = json_decode($item->name, true)['value'];
                else
                    $val = $item->name;
                $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $current->id, 'user_id' => $user_id, 'event' => 'OBJECT_COPIED', 'text' => "Создана копия на основании: <span data-slug='$slug' data-id='$item->id'>$val</span>", 'old_value' => $current->id, 'new_value' => $item->id]);
                $history->saveQuietly();
                $history_events[] = $history;
                if(is_array($current->name))
                    $val =  $current->name['value'];
                elseif(!is_array($current->name) && json_decode($current->name, true))
                    $val =  json_decode($current->name, true)['value'];
                else
                    $val = $current->name;
                
                $history_events[] = $history;
                $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => $user_id, 'event' => 'OBJECT_COPIED', 'text' => "Создана копия: <span data-slug='$slug' data-id='$current->id'>$val</span>", 'old_value' => $item->id, 'new_value' => $current->id]);
                $history->saveQuietly();
                $history_items = \App\Models\History::getDataList($history_events);
                $history_response_events = array_merge($history_response_events, $history_items['fields']);
                unset($objects[$k]);

            }
        }
        $hobjects = \App\Models\History::saveForObject($slug, $rows);

 
        foreach($objects as $k => $item) {
            if(in_array($item->id, $copied_ids)) {
                $current = $item;
                $copy = $current;
                $objects[$current->id] = $current;
                foreach($rows as $r => $row) {
                    if($row['id'] == $item->id) {
                        $rows[$r]['id'] = $current->id;
                    }
                }
                unset($objects[$k]);
            }
        }
        $objects_collection_by_key = $hobjects['by_key'];
        $changed_fields = $hobjects['changed_fields'];


        foreach($objects_collection_by_key as $k => $item) {
            if(in_array($item->id, $copied_ids)) {
                $current = $item;
                $current->user_id = $user_id;
                $current->saveQuietly();
                $copy = $current;
                $objects[$current->id] = $current;
                foreach($rows as $r => $row) {
                    if($row['id'] == $item->id) {
                        $rows[$r]['id'] = $current->id;
                    }
                }


            }
        }
        
        foreach($hobjects['data']['fields'] as $h_item) {
            if($h_item['event'] == 'FIELD_UPDATED') {
                $history_response_fields[] = $h_item;
            } else {
                $history_response_events[] = $h_item;
            }
        }
        foreach($rows as $k => $row) {

            $id = $row['id'];
            unset($row['id']);
            if(isset($row['password'])) {
                $row['password'] = Hash::make($row['password']);
            }
            if($slug == 'users' && isset($row['email']) && is_array($row['email'])) {
                $row['email'] = $row['email']['value'];
            }
            $ob = $objects_collection_by_key[$id];

            foreach($row as $field => $value) {
                if($field == 'copy' || $field == 'is_new' || !isset($model_fields[$field]))
                    continue;
                if($model_fields[$field]->type == 'relation' && !$model_fields[$field]->is_plural && is_array($value)) {
                    info('$field');
                    info($field);
                    
                    $value = array_pop($value);
                    info($value);
                }
                if($model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural && $model_fields[$field]->relation_table) {
                    $relation_table = $model_fields[$field]->relation_table;
                    if (!method_exists($ob, $relation_table)) {
                        continue;
                    }
                    $new_values = array_filter($value, 'is_int');

                    foreach($new_values as $nv) {
                        $new_values_keyby[$nv] = $nv;
                    }
                    if(method_exists($ob->{$relation_table}(), 'sync')) {
                        // Связи с мягко удалёнными записями сохраняем: фронт их
                        // не показывает (SoftDeletes-scope), поэтому их нет в
                        // присланном списке, и обычный sync молча отвязал бы их —
                        // после восстановления из корзины связь была бы потеряна.
                        $sync_values = $new_values;
                        $related_model = $ob->{$relation_table}()->getRelated();
                        if(in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($related_model))) {
                            $trashed_linked = $ob->{$relation_table}()
                                ->onlyTrashed()
                                ->pluck($related_model->getQualifiedKeyName())
                                ->toArray();
                            $sync_values = array_values(array_unique(array_merge($new_values, $trashed_linked)));
                        }
                        $ob->{$relation_table}()->sync($sync_values);
                    }
                    $old_values = $ob->{$relation_table}->pluck('id')->toArray();
                    $old = array_diff($old_values, $new_values);
                    $new = array_diff($new_values, $old_values);
                    $related_rows = array();
                    foreach($old as $o_item) {
                        $related_rows[] = array('id' => $o_item, $model_fields[$field]->related_field => null);
                    }
                    foreach($new as $n_item) {
                        $related_rows[] = array('id' => $n_item, $model_fields[$field]->related_field => $ob->id);
                    }
                    
                    $h = \App\Models\History::saveForObject($relation_table, $related_rows);//for companies delete
                    if($slug == 'companies')
                        \DB::table($relation_table)->whereIntegerInRaw('id', $old_values)->update([$model_fields[$field]->related_field => null/*, 'choosed_at' => null*/]);


                    if($slug != 'companies') {
                        if(is_array($old_values)) 
                            $only_new_values = array_diff($new_values, $old_values);
                        foreach($only_new_values as $sort => $v) {
                            \DB::table($relation_table)->where('id', $v)->update([$model_fields[$field]->related_field => $ob->id, 'choosed_at' => now()->addSeconds($sort)]);
                        }
                    }
                    \DB::table($relation_table)->whereIntegerInRaw('id', $new_values)->update([$model_fields[$field]->related_field => $ob->id]);
                    $ob->load($relation_table);
                    
                    
                } elseif ($model_fields[$field]->type == 'relation' && $model_fields[$field]->field != 'user_id') {
                    $related_rows = array();
                    $relation_table = $model_fields[$field]->relation_table;
                    $entity_relation = $settings['models'][$relation_table];
                    $entity_relation_class = $entity_relation->model_name;
                    if($ob->{$model_fields[$field]->field}) {
                        $old_rel = $entity_relation_class::where('id', $ob->{$model_fields[$field]->field})->first();
                        $old_el_relations = array();
                        if($old_rel) {
                            $first = $entity_relation_class::where('id', $ob->{$model_fields[$field]->field})->first();
                            if(isset($first->{$slug}))
                                $old_el_relations = $first->{$slug}()->pluck('id')->toArray();
                        }

                        if(in_array($ob->id, $old_el_relations)) {
                            unset($old_el_relations[array_search($ob->id, $old_el_relations)]);
                            if($relation_table == 'companies')
                                $ob->choosed_at = null;
                            
                            $related_rows[] = array('id' => $ob->{$model_fields[$field]->field}, $model_fields[$field]->related_field => $old_el_relations);
                        }
                    }
                    
                    if($value && $relation_table == 'companies')
                        $ob->choosed_at = now();
                    if($value) {
                        $rel_obj = $entity_relation_class::where('id',$value)->first();
                        $rel_obj->choosed_at = now();
                        $rel_obj->saveQuietly();
                        if($rel_obj && method_exists($rel_obj, $slug))
                            $new_el_relations = $entity_relation_class::where('id',$value)->first()->{$slug}()->pluck('id')->toArray();
                        $new_el_relations[] = $ob->id;
                        $related_rows[] = array('id' => $value, $model_fields[$field]->related_field => $new_el_relations);
                    }
                    $h = \App\Models\History::saveForObject($relation_table, $related_rows);
                }


                if(!is_array($value) && is_array(json_decode($value, true))) {
                    // Значение уже пришло валидной JSON-строкой (например, detail_text
                    // у поля type=redactor). Повторный json_encode давал двойное
                    // кодирование ("[{\"type\"...]") и статья переставала выводиться.
                    // Сохраняем как есть — в колонке остаётся одинарно закодированный JSON.
                    $ob->{$field} = $value;
                } else {
                    $ob->{$field} = $value;
                }
            }
            if(!$ob->name) {
                
                $ob->name = $entity->title_singular.' #'.$ob->id;
            }
            if(in_array($ob->id, $copied_ids)) {
                $copy = $ob;
            };
            info($ob->toArray());
            $ob->save();
        }

       
        $ids = array();
        foreach($rows as $row) {
            $ids[] = $row['id'];
        }
        if($new_object) {
            $ids[] = $new_object->id;
        }
        $settings = \App\Models\Settings::get(true);
        $objects_collection = $entity_class::whereIntegerInRaw('id', $ids)->get();
        $objects = $objects_collection->keyBy('id');
        foreach($rows as $k => $row) {
            if($new_object && !$row['id']) {
                $ob = $objects[$new_object->id];
                
            } else {
                $ob = $objects[$row['id']];
            }
            $data = $ob->getData($changed_fields, $settings);
            if($new_object) {
                \App\Events\ObjectUpdated::dispatch('ObjectCreated', $data);
            } else {
                \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);
            }
        };
        \App\Models\Settings::clear_cache();
        if($new_object) {
            if(!$ob['name'])
                $ob['name'] = $entity->title_singular;
            if(ValueHelper::isJson($ob['name'])) {
                $title = json_decode($ob['name'], true);
                if(isset($title['value'])) {
                    $title = $title['value'];
                } else {
                    $title = null;
                }
            } else {
                $title = $ob['name'];
            }
            $title = "$title | $entity->title_plural | Compas.pro";
            $data = ['id' =>$ob['id'], 'title'=>$title, 'details' => $data['viewDetail'], 'success' => true, 'history_events' => $history_response_events, 'history_fields' => $history_response_fields];
        } elseif(isset($copy)) {
            if(ValueHelper::isJson($copy->name)) {
                if(is_array($copy->name))
                    $title = $copy->name;
                else
                    $title = json_decode($copy->name, true);
                if(isset($title['value'])) {
                    $title = $title['value'];
                } else {
                    $title = null;
                }
            } else {
                $title = $copy->name;
            }
            $data = ['id' =>$copy->id,'title'=>$title, 'details' => $data['viewDetail'], 'success' => true, 'history_events' => $history_response_events, 'history_fields' => $history_response_fields];
        } else {
            if(isset($data['viewDetail']))
                $data = ['success' => true, 'details' => $data['viewDetail'], 'history_events' => $history_response_events, 'history_fields' => $history_response_fields];
        }

        $data['status'] = 200;

        return $data;
    }

    public function delete(string $slug, array $ids)
    {
        $settings = app('settings');
        $user_id = \Auth::user() ? \Auth::user()->id : 1;
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return [
                'message' => 'Entity not found',
                'status' => 404
            ];
        }
        
        if(in_array(1, $ids) && $slug == 'users')
            return [
                'message' => 'Администратор не может быть удален',
                'status' => 403
            ];
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $items = $entity_class::whereIntegerInRaw('id', $ids)->get();
        // Связи при удалении НЕ отвязываем: записи удаляются мягко (SoftDeletes),
        // и при восстановлении из корзины сущность должна вернуться со всеми
        // своими связями. У неудалённых сущностей удалённые при этом нигде не
        // отображаются: выборки связей идут через Eloquent-отношения (глобальный
        // scope SoftDeletes отсекает удалённых) и через settings['list_values']
        // (строится с whereNull('deleted_at'), см. Settings::field_values).
        foreach($items as $current) {
            $history_text = 'Удалена запись: '.$current->id;
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $current->id, 'user_id' => $user_id, 'text' => $history_text, 'event' => 'OBJECT_DELETED']);
            $history->save();
            if($slug == 'users') {
                $current->api_token = null;
                $current->saveQuietly();
            }
                
            $current->delete();
            \App\Events\ObjectUpdated::dispatch('ObjectDeleted', $current->getData());
        }

        \App\Jobs\SettingsClearJob::dispatch();
        $data = array('success' => true, 'status' => 200);
        return $data;
    }

    public function restore(string $slug, array $ids)
    {
        $settings = app('settings');
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return [
                'message' => 'Entity not found',
                'status' => 404
            ];
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $tariff = \App\Models\Tariff::current();
        $restrictions = [];
        if($tariff->restrictions) {
            $restrictions_tariff = json_decode($tariff->restrictions,true);

            if(isset($restrictions_tariff['objects'][$slug])) {
                $restrictions = $restrictions_tariff['objects'][$slug];
                if(isset($restrictions['count']) && $restrictions['count'] < ($entity_class::count() + count($ids))) {
                    return [
                        'message' => "По вашему тарифу допустимо {$restrictions['count']} активных объектов данного типа",
                        'status' => 403
                    ];
                };
            }
        }
        $items = $entity_class::withTrashed()->whereIntegerInRaw('id', $ids)->get();
        foreach($items as $k => $item) {
            $history_text = 'Восстановлена запись: '.$item->id;
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'text' => $history_text, 'event' => 'OBJECT_RESTORED']);
            $history->save();
            $data = \App\Models\History::getDataList([$history]);
            $item->restore();
            \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);
            \App\Events\ObjectUpdated::dispatch('ObjectRestored', $item->getData());
        }

        \App\Models\Settings::clear_cache();
        

        return ['data' => $items, 'status' => 200];
    }
}