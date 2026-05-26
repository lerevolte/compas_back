<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class SearchService
{
    public function find(array $params)
    {
        $settings = app('settings');
        $data = array();
        if(isset($params['entity'])) {
            $entity = $settings['models'][$params['entity']];
            $entity_class = $entity->model_name;
            $slug = $entity->slug;
        }
        

        if($params['q'] && mb_strlen($params['q']) >= 0 || isset($params['filter'])) {
            $q = mb_strtolower($params['q']);
            if(isset($params['field_id'])) {
                $data = collect($settings['list_values'][$params['field_id']])->filter(function ($item) use ($q) {
                    
                    $text = mb_strtolower($item['label']['text']);
                    return false !== stristr($text, $q) || $item['value'] == $q;
                })->toArray();
                if(isset($params['filter']) && count($data) && isset($slug)) {
                    $paginator = $entity_class::whereNull('deleted_at')->whereIntegerInRaw('id', array_keys($data));
                    foreach($params['filter'] as $field => $val) {
                        if($val == 'null')
                            $val = null;
                        if($settings[$slug]['fields'][$field]->type == 'date') {
                            if(strstr($val, ','))
                                $val = explode(',', $val);
                            if(is_array($val) && $val[0] && $val[1]) {
                                $paginator = $paginator->whereBetween($field, $val);
                            } elseif(is_array($val) && $val[0] && !$val[1]) {
                                $paginator = $paginator->whereDate($field, '>=', $val[0]);
                            } elseif(is_array($val) && !$val[0] && $val[1]) {
                                $paginator = $paginator->whereDate($field, '<=', $val[1]);
                            } else {
                                $paginator = $paginator->whereDate($field, date('Y-m-d', strtotime($val)));
                            }
                        } else {
                            if(isset($settings[$slug]['fields'][$field]) && $settings[$slug]['fields'][$field]->is_plural) {
                                if($settings[$slug]['fields'][$field]->type == 'relation' && $settings[$slug]['fields'][$field]->relation_table) {
                                    $paginator = $paginator->whereHas($settings[$slug]['fields'][$field]->relation_table, function($q) use($val) {
                                        $q->where('id', '=', (int)$val);
                                    });
                                } elseif($settings[$slug]['fields'][$field]->type == 'relation') {
                                    $paginator = $paginator->whereJsonContains($field, (int)$val);
                                } else {
                                    $paginator = $paginator->whereRaw('json_contains('.$field.', \''.$val.'\')');
                                }
                            } else {
                                if($field == 'category_id' && !$request->exclude_childs) {
                                    foreach($model_fields as $f) {
                                        if($f->field == $field) {
                                            $related_table = $field->relation_table;
                                            $dt = \DB::table('data_types')->where('name', $related_table)->first();
                                            $descendants = $dt->model_name::descendantsAndSelf($val)->pluck('id')->toArray();
                                        }
                                    }
                                    if(isset($descendants) && is_array($descendants)) {
                                        $paginator = $paginator->whereIntegerInRaw($field, $descendants);
                                    }
                                    
                                } else {
                                    if(is_array($val))
                                        $paginator = $paginator->whereIntegerInRaw($field, $val);
                                    else {
                                        if($settings[$slug]['fields'][$field]->type == 'address')
                                            $paginator = $paginator->where("{$field}->text",'like', "%{$val}%");
                                        elseif($settings[$slug]['fields'][$field]->type == 'text') 
                                            $paginator->where(function ($query) use ($val, $field) {
                                                $query->where($field, 'like', "%{$val}%")
                                                  ->orWhere("{$field}->value", 'like', "%{$val}%");
                                            });
                                        else
                                            $paginator = $paginator->where($field, $val);
                                        
                                    }
                                }
                                
                            }
                            
                        }
                    }
                    $items = $paginator->pluck('id')->toArray();
                    foreach($data as $key => $item) {
                        if(!in_array($key, $items))
                            unset($data[$key]);
                    }

                    
                }
                return array_values($data);
            } elseif(isset($params['entity'])) {
                $field_name = 'name';
                $q = str_replace(' ', '%', $params['q']);
                $q = '%'.$q.'%';
                
                
                if($params['entity'] == 'articles' || $params['entity'] == 'faq' || $params['entity'] == 'knowledge' || $params['entity'] == 'guides') {
                    $items = $entity_class::where(function($query) use ($field_name, $q, $params) {
                        $query->where($field_name, 'LIKE', $q)
                              ->orWhere("{$field_name}->value", 'LIKE', $q)
                              ->orWhere('id', (int)$params['q']);
                    })->whereNull('deleted_at')->where('is_active', 1)->limit(20)->get();
                } elseif($params['entity'] == 'logistic_tasks') {
                    // У задач логистики name часто пустой/служебный — ищем ещё
                    // по адресу и по id, чтобы поиск был полезен для пользователя.
                    $items = $entity_class::where(function($query) use ($field_name, $q, $params) {
                        $query->where($field_name, 'LIKE', $q)
                              ->orWhere("{$field_name}->value", 'LIKE', $q)
                              ->orWhere("address->text", 'LIKE', $q)
                              ->orWhere('id', (int)$params['q']);
                    })->whereNull('deleted_at')->limit(20)->get();
                } else {
                    $items = $entity_class::where(function($query) use ($field_name, $q, $params) {
                        $query->where($field_name, 'LIKE', $q)
                              ->orWhere("{$field_name}->value", 'LIKE', $q)
                              ->orWhere('id', (int)$params['q']);
                    })->whereNull('deleted_at')->limit(20)->get();
                }
                if(!$items) {

                    $items = $entity_class::whereNull('deleted_at')->limit(20)->get();
                }
            }
            
        } else {
            if(isset($params['field_id'])) {
                $data = collect($settings['list_values'][$params['field_id']])->toArray();
                return array_values($data);
                
            } elseif(isset($params['entity'])) {
                $items = $entity_class::whereNull('deleted_at')->limit(20)->get();
            }
        }
        if(isset($params['entity'])) {
            foreach($items as $item) {
                if($item->id == 290) {
                    info($item->photo);
                }
                $photo = isset($item->avatar) ? $item->avatar : '';
                if(!$photo)
                    $photo = isset($item->photo) ? $item->photo : '';
                $name = $item->name;
                if(ValueHelper::isJson($name)) {
                    $name = json_decode($item->name, true)['value'];
                }
                if($photo) {
                    $photo = json_decode($photo, true);
                    if(isset($photo[0]['url']))
                        $photo = $photo[0]['url'];
                }
                if($params['entity'] == 'products') {
                    $model_fields = $settings['products']['fields'];
                    $data_product = array(
                        'label' => [
                            'id' => $item->id,
                            'text' => $name.(isset($item->last_name) ? ' '.$item->last_name: ''),
                            'color' => isset($item->color) ? $item->color : '',
                            'file' => $photo,
                            'count' => $item->quantity,
                            'weight' => $item->weight,
                            'price' => $item->price
                        ],
                        'value' => $item->id
                    );
                
                
                    
                    foreach($model_fields as $field) {
                        if($field->type != 'text_group' && $field->field != 'name' && $field->field != 'id' && (!isset($settings['products']['perms'][$field->field]['read']) || $settings['products']['perms'][$field->field]['read'])) {
                            if($field->type == 'relation' && $field->relation_table) {
                                if($field->is_plural)
                                     $data_product['label'][$field->field]['value'] = $item->{$field->relation_table} ? $item->{$field->relation_table}->pluck('id')->toArray() : array();
                                else
                                     $data_product['label'][$field->field]['value'] = $item->{$field->field} ? array($item->{$field->field}) : array();
                                 $data_product['label'][$field->field]['localOptions'] =array_values($settings['list_values'][$field->id]);
                            } elseif($field->type == 'date') {
                                 $data_product['label'][$field->field] = \Carbon\Carbon::parse($item->{$field->field})->format('Y-m-d H:i:s');
                            } else {
                                 $data_product['label'][$field->field] = $item->{$field->field};
                            }
                        }
                    }
                    $data[] = $data_product;
                } else {
                    $item_data = array(
                        'label' => [
                            'id' => $item->id,
                            'text' => $name.(isset($item->last_name) ? ' '.$item->last_name: ''),
                            'color' => isset($item->color) ? $item->color : '',
                            'file' => $photo
                        ],
                        'value' => $item->id
                    );
                    if($params['entity'] == 'articles' || $params['entity'] == 'faq' || $params['entity'] == 'knowledge' || $params['entity'] == 'guides') {
                        $slug = json_decode($item->slug, true);
                        $item_data['label']['slug'] = is_array($slug) ? $slug['value'] : $item->slug;
                    }
                    // Для задач логистики прокидываем route_id и delivery_date,
                    // чтобы фронтовый поиск мог решить, куда вести по клику:
                    // в /logistic на конкретную дату с авто-выбором маршрута,
                    // на /logistic без маршрута, или в общий список с фильтром.
                    if($params['entity'] == 'logistic_tasks') {
                        $item_data['label']['route_id'] = $item->route_id;
                        $item_data['label']['delivery_date'] = $item->delivery_date
                            ? \Carbon\Carbon::parse($item->delivery_date)->format('Y-m-d')
                            : null;

                        // Имя у задачи часто пустое — собираем человекочитаемый
                        // лейбл из id и адреса, чтобы дропдаун был полезен.
                        $addressText = '';
                        if ($item->address) {
                            $addr = is_string($item->address) ? json_decode($item->address, true) : $item->address;
                            if (is_array($addr) && isset($addr['text'])) {
                                $addressText = $addr['text'];
                            } elseif (is_string($item->address)) {
                                $addressText = $item->address;
                            }
                        }
                        $displayParts = ['#'.$item->id];
                        if ($addressText) $displayParts[] = $addressText;
                        elseif ($name) $displayParts[] = $name;
                        $item_data['label']['text'] = implode(' — ', $displayParts);
                    }

                    $data[] = $item_data;
                }
            }
        }

        return array_values($data);
    }
}