<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use App\Models\Field;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Exports\ObjectExport;
use Maatwebsite\Excel\Facades\Excel;

class ObjectController extends Controller
{
    public function list($slug, Request $request)
    {
       
        $data = \App\Models\EntityObject::list($slug, $request);

        return response()->json($data);
        // if(!$request->filter && $request->is_slug) {
        //     return response()->json([]);
        // }
        // $limit = $request->per_page ? $request->per_page : 25;
        // $page = $request->page ? $request->page : 1;
        // $sort_field = $request->sort_field ? $request->sort_field : 'id';
        // $sort_order = $request->sort_order ? $request->sort_order : 'desc';

        // $settings = get_settings();
        // $tenant = tenant('id');
        // if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
        //     return response()->json([
        //         'message' => 'Entity not found'
        //     ], 404);
        // }
        // $entity = $settings['models'][$slug];

        
        // $entity_class = $entity->model_name;
        // $model_fields = collect($settings[$slug]['fields']);//$entity_class::getFields();

        // $field_colors = array();
        // $perms = array(
        //     'read' => array(),
        //     'write' => array(),
        // );


        // if($sort_order == 'asc')
        //     $paginator = $entity_class::orderByRaw("$sort_field REGEXP '^-?[0-9\.]+$' AND LENGTH($sort_field) - LENGTH(REPLACE($sort_field, '.', '')) < 2 DESC, CAST($sort_field AS UNSIGNED), $sort_field");
        // else
        //     $paginator = $entity_class::orderByRaw("$sort_field REGEXP '^-?[0-9\.]+$' AND LENGTH($sort_field) - LENGTH(REPLACE($sort_field, '.', '')) < 2 DESC, CAST($sort_field AS UNSIGNED), $sort_field desc");
        // if($sort_field == 'id')
        //     $paginator = $entity_class::orderByRaw("CAST($sort_field AS DECIMAL) $sort_order");
        // foreach($model_fields as $field) {
        //     if($field->field == $sort_field && $field->type == 'file')
        //         $paginator = $entity_class::orderBy($sort_field, $sort_order);
        // }
        
        // // $paginator = $entity_class::orderBy($sort_field, $sort_order);
        // if($request->trashed) {
        //     $paginator = $paginator->onlyTrashed();
        // }
        // if($request->filter && is_array($request->filter)){

        //     foreach($request->filter as $field => $val) {
        //         if($field == 'created_at' || $field == 'updated_at')
        //             $paginator = $paginator->whereDate($field, $val);
        //         elseif($settings[$slug]['fields'][$field]->type == 'date')
        //             $paginator = $paginator->whereDate($field, date('Y-m-d', strtotime($val)));
        //         else {
        //             if(isset($settings[$slug]['fields'][$field]) && $settings[$slug]['fields'][$field]->is_plural) {
        //                 if($settings[$slug]['fields'][$field]->type == 'relation') {
        //                     $paginator = $paginator->whereJsonContains($field, (int)$val);
        //                 }
        //                 else {
        //                     //->whereRaw("json_contains(`client_id`, ?)", [15])->whereRaw('json_contains(`tip_tk`, \'"'.$str.'"\')')
        //                     $paginator = $paginator->whereRaw('json_contains('.$field.', \'"'.$val.'"\')');
        //                 }
        //             } else {
        //                 if($field == 'category_id' && !$request->exclude_childs) {
        //                     foreach($model_fields as $f) {
        //                         if($f->field == $field) {
        //                             $related_table = json_decode($f->details, true)['table'];
        //                             $dt = \DB::table('data_types')->where('name', $related_table)->firstOrFail();
        //                             $descendants = $dt->model_name::descendantsAndSelf($val)->pluck('id')->toArray();
        //                         }
        //                     }
        //                     if(isset($descendants) && is_array($descendants)) {
        //                         $paginator = $paginator->whereIntegerInRaw($field, $descendants);
        //                     }
                            
        //                 } else {
        //                     if(is_array($val))
        //                         $paginator = $paginator->whereIntegerInRaw($field, $val);
        //                     else {
        //                         // $paginator = $paginator->where(function ($query) use ($search_columns, $q) {
        //                         //     foreach ($search_columns as $column) {
        //                         //         $query->orWhere($column, 'like', "%{$q}%");
        //                         //     }
        //                         // });
        //                         if($settings[$slug]['fields'][$field]->type == 'text')
        //                             $paginator = $paginator->where($field, 'like', "%{$val}%");
        //                         else
        //                             $paginator = $paginator->where($field, $val);
                                
        //                     }
        //                 }
                        
        //             }
                    
        //         }
        //     }
        // }
        // if($request->order_id && $slug == 'products') {
        //     $order = \App\Models\Task::findOrFail($request->order_id);
        //     if($order) {
        //         $products = json_decode($order->products, true);
        //         $product_ids = array();
        //         $fix_order = false;
        //         if(is_array($products)) {
        //             foreach($products as $product_k => $product) {
        //                 if(!isset($product['id'])) {
        //                     $prod = \Modules\Products\Entities\Product::where('name', $product['name'])->firstOrFail();
        //                     if($prod) {
        //                         $fix_order = true;
        //                         $product_ids[] = $prod->id;
        //                         $products[$product_k]['id'] = $prod->id;
        //                     }
                            
        //                 } else {
        //                     $product_ids[] = $product['id'];
        //                 }
        //             }
        //             if($fix_order) {
        //                 $order->products = json_encode($products, JSON_UNESCAPED_UNICODE);
        //                 $order->saveQuietly();
        //             }
        //             $paginator = $paginator->whereIntegerInRaw('id', $product_ids);
        //         } else {
        //             return response()->json([]);
        //         }
        //     }
        // };

        // if($request->q) {
        //     $q = $request->q;

        //     $search_columns = $model_fields->filter(function ($field) {
        //                         return ($field->type != 'relation' && $field->type != 'status' && $field->type != 'text_group');
        //                     })->pluck('field')->toArray();
            
        //     $paginator = $paginator->where(function ($query) use ($search_columns, $q) {
        //         foreach ($search_columns as $column) {
        //             $query->orWhere($column, 'like', "%{$q}%");
        //         }
        //     });
        //     foreach($model_fields as $field) {
        //         if($field->type == 'relation') {
        //             $relations = collect($settings[$slug]['list_values'][$field->field])->filter(function ($item) use ($q) {
        //                 return mb_stristr($item['label']['text'], $q);
        //             })->pluck('value')->toArray();
        //             if(count($relations) && $field->is_plural) {
        //                 $paginator = $paginator->orWhere(function ($query) use ($field, $relations) {
        //                    foreach ($relations as $id) {
        //                        $query->orWhereJsonContains($field->field, $id);
        //                    }
        //                 });
        //                 //$paginator = $paginator->whereJsonContains($field->field, (int)$val);
        //             } elseif(count($relations)) {
        //                 $paginator = $paginator->orWhere(function ($query) use ($field, $relations) {
        //                    foreach ($relations as $id) {
        //                        $query->orWhere($field->field, $id);
        //                    }
        //                 });
        //             }
        //         }
        //     }
        // };

        // $paginator = $paginator->paginate($limit);
        
        // $objects = array();
        // $field_values = array();
        
        // foreach ($paginator->items() as $item) {
        //     $data = array(
        //         'id' => $item->id
        //     );
        //     foreach($model_fields as $field) {
        //         if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
        //             //$data[$field->field] = Field::getDataByObjectForList($field, $slug, $item);
        //             $value = $item->{$field->field};
        //             $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;

        //             if($field->type == 'relation' && $field->is_plural) {
        //                 $values = $data[$field->field];
        //                 $data[$field->field] = array();
        //                 if(is_array($values)) {
        //                     foreach($values as $val) {
        //                         if(isset($settings[$slug]['list_values'][$field->field][$val]))
        //                             $data[$field->field][] = $settings[$slug]['list_values'][$field->field][$val];
        //                     }
        //                 }
        //             } elseif($field->type == 'relation' && isset($settings[$slug]['list_values'][$field->field][$value])) {
        //                 $data[$field->field] = $settings[$slug]['list_values'][$field->field][$value];
        //             } elseif($field->type == 'relation') {
        //                 $data[$field->field] = null;
        //             };
        //         }
        //     }
        //     if(isset($order) && $order && $slug == 'products') {
        //         $products = json_decode($order->products, true);
        //         if(is_array($products)) {
        //             foreach($products as $num => $product) {
        //                 if($product['id'] == $item->id) {
        //                     $data['product_name'] = isset($product['product_name']) ? $product['product_name'] : $product['name'];
        //                     $data['product_price'] = $product['price'];
        //                     $data['product_count'] = $product['count'];
        //                     $data['product_weight'] = $product['weight'];
        //                     $data['product_sum'] = $product['sum'];
        //                     $data['sort'] = $num;
        //                 }
        //             }
        //         }
                
        //     }
            
        //     $objects[] = $data;
        // }
        // if($sort_order == 'asc')
        //     array_sort_by_column($objects, $sort_field, SORT_ASC, SORT_NATURAL);
        // else
        //     array_sort_by_column($objects, $sort_field, SORT_DESC, SORT_NATURAL);
        
        // $res = array(
        //     'count' => $paginator->count(),
        //     'current_page' => $paginator->currentPage(),
        //     'last_page' => $paginator->lastPage(),
        //     'per_page' => $paginator->perPage(),
        //     'total' => $paginator->total(),
        //     'from' => $paginator->firstItem(),
        //     'to' => $paginator->lastItem(),
        //     'data' => $objects,
        //     'buttons' => $settings[$slug]['buttons']
        // );
        // return response()->json($res);

    }

    public function copy($slug, $id, Request $request)
    {
        $settings = get_settings();
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();
        $item = $entity_class::withTrashed()->where(['id' => $id])->firstOrFail();
        // $new_item = new $entity_class;
        // $new_item->saveQuietly();
        // $new_item->saveHistory($item->toArray());
        // $new_item->update($item->toArray());
        // $item = $new_item;
        $current = $item->replicate();
        foreach($model_fields as $field) {
            if($field->type == 'relation' && $field->is_plural)
                $current->{$field->field} = null;
        };
        $current->saveQuietly();
        $data = $current->getData();
        \App\Events\ObjectUpdated::dispatch('ObjectCreated', $data);

        $data = array(
            'id' => $current->id
        );
        $history_items = array();
        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $current->id, 'user_id' => \Auth::user()->id, 'event' => 'OBJECT_COPIED', 'text' => 'Создана копия', 'old_value' => $item->id, 'new_value' => $current->id]);
        $history->saveQuietly();
        $history_items[] = $history;
        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'event' => 'OBJECT_COPIED', 'text' => 'Создана копия на основании', 'old_value' => $item->id, 'new_value' => $current->id]);
        $history->saveQuietly();

        $history_items[] = $history;
        foreach($model_fields as $field) {
            if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
                //$data[$field->field] = Field::getDataByObjectForList($field, $slug, $current);
                $value = $current->{$field->field};
                $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;

                if($field->type == 'relation' && $field->is_plural) {
                    $values = $data[$field->field];
                    $data[$field->field] = array();
                    if(is_array($values)) {
                        foreach($values as $val) {
                            if(isset($settings[$slug]['list_values'][$field->field][$val]))
                                $data[$field->field][] = $settings[$slug]['list_values'][$field->field][$val];
                        }
                    }
                } elseif($field->type == 'relation' && isset($settings[$slug]['list_values'][$field->field][$value])) {
                    $data[$field->field] = $settings[$slug]['list_values'][$field->field][$value];
                } elseif($field->type == 'relation') {
                    $data[$field->field] = null;
                };
            }

            if($field->field == 'id' || $field->field == 'password' || $field->field == 'created_at' || $field->field == 'updated_at' || $field->field == 'deleted_at' || !$current->{$field->field})
                continue;
            if($field->type == 'status' && isset($settings[$slug]['list_values'][$field->field])) {
                //info($settings[$slug]['list_values'][$field->field]);
                $statuses = collect($settings[$slug]['list_values'][$field->field]);
                $visible_statuses = $statuses->filter(function ($status) {
                        return !$status['label']['is_hidden'];
                    })->pluck('id')->toArray();
                $new_status = $statuses->firstWhere('id', $current->{$field->field});
                
                if($new_status) {
                    if($new_status->is_hidden) {
                        $new_value = $new_status->color;
                    } else {
                        $new_value = $new_status->value ? $new_status->value : '';//'Значение '.(array_search($new_status->id, $visible_statuses)+1);
                    }
                    $history_text = $field->display_name.': -> '.$new_value;
                } else {
                    $history_text = $field->display_name.': -> '.$current->{$field->field};
                }
            } elseif(isset($settings[$slug]['list_values'][$field->field])) {

                $list_values = $settings[$slug]['list_values'][$field->field];
                foreach($list_values as $k => $item) {
                    if(isset($item['label']['text']))
                        $list_values[$item['value']] = $item['label']['text'];
                }
                if($field->is_plural) {
                    $new_list = array();
                    $new_val = json_decode($current->{$field->field}, true);
                    if(is_array($new_val)) {
                        $new_list = $new_val;
                    } elseif(is_integer($current->{$field->field})) {
                        $new_list[] = $current->{$field->field};
                    }
                    foreach($new_list as $k => $val) {
                        if(isset($list_values[$val]))
                            $new_list[$k] = isset($list_values[$val]['value']) ? $list_values[$val]['value'] : $list_values[$val];
                        else
                            unset($new_list[$k]);
                    }

                    $new_value = implode(', ', $new_list);
                    $history_text = $field->display_name.': -> '.$new_value;
                } else {
                    $new_value = isset($list_values[$current->{$field->field}]) ? $list_values[$current->{$field->field}] : '';
                    $history_text = $field->display_name.': -> '.(is_array($new_value) ? $new_value['value'] : $new_value);
                }
                if($field->type == 'relation') {
                    $related_table = json_decode($field->details, true)['table'];
                    if($related_table && isset($settings['models'][$related_table])) {
                        $new_val = json_decode($current->{$field->field}, true);
                        if(is_array($new_val) && count($new_val)) {
                            $new_values = $new_val;
                            if(count($new_values))
                                \DB::table($related_table)->whereIntegerInRaw('id', $new_values)->update(['choosed_at' => \DB::raw('now()')]);
                        } elseif($current->{$field->field}) {
                            \DB::table($related_table)->where('id', $current->{$field->field})->update(['choosed_at' => \DB::raw('now()')]);
                        }
                    }
                     
                }
            } else {
                $new_val = json_decode($current->{$field->field}, true);
                if(is_array($new_val)) {
                    if($field->type == 'address') {
                        $history_text = $field->display_name.': -> '.$new_val['text'];
                    } elseif($field->type == 'file') {
                        $file_values = array();
                        foreach($new_val as $v) {
                            if(isset($v['name']))
                                $file_values[] = $v['name'];
                        }
                        $history_text = $field->display_name.': -> '.implode(', ', $file_values);
                    } elseif($field->field != 'products') {
                        $new_value = array_filter($new_val, fn($value) => !is_null($value) && $value !== '');



                        $history_text = $field->display_name.': -> '.implode(', ', array_values($new_value));
                    }
                } else {
                    $history_text = $field->display_name.': -> '.$current->{$field->field};
                }
                
            }
            if(isset($history_text) && $history_text) {
                $module = $field->module;
                if(isset(\Auth::user()->id))
                    $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $current->id, 'user_id' => \Auth::user()->id, 'text' => $history_text, 'module' => $module]);
                else
                    $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $current->id, 'user_id' => 0, 'text' => $history_text, 'module' => $module]);

                $history->saveQuietly();
                if($module) {
                    $history_replic = $history->replicate();
                    $history_replic->module = null;
                    $history_replic->save();
                }
                $history_items[] = $history;
            }
        }


        // if(count($history_items)) {
        //     //$data = \App\Models\History::getDataList($history_items);
        //     \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);
        // }
        return response()->json($data);
    }

    public function compose_list($slug, Request $request)
    {
        //api/tables/$slug
        $table = \App\Models\Table::get($slug);
        if(isset($table['error'])) {
            return response()->json([
                    'message' => $table['error']['message']
                ], $table['error']['code']);
        }
        $sort_field = 'id';
        $sort_order = 'desc';
        if($request->sort_field && $request->sort_order && $request->sort_field != 'null') {
            $sort_field = $request->sort_field;
            $sort_order = $request->sort_order;
        } else {
            foreach($table as $column) {
                if($column['sort_order']) {
                    $sort_field = $column['key'];
                    $sort_order = $column['sort_order'];
                    break;
                }
            }
        }
        
        //api/objects/$slug
        $sort_params = ['sort_order' => $sort_order, 'sort_field' => $sort_field];
        $request->merge($sort_params);
        $list = \App\Models\EntityObject::list($slug, $request);

        // //api/objects/products?order_id=$id
        // $products = array();
        // if($slug == 'tasks') {
        //     $request = new Request(['order_id' => $id]);
        //     $products = \App\Models\EntityObject::list($slug, $request);
        // }
        
        //api/fields/$slug
        $fields = \App\Models\Field::list($slug);
        //api/entities
        $entities = \DB::table('data_types')->select(['slug', 'display_name_singular', 'display_name_plural', 'color'])->get();
        //api/roles
        $roles = \App\Models\Role::list();
        //api/profile
        $profile = \Auth::user();
        //api/sidebar/get
        $sidebar = \Auth::user()->getSidebar();
        //api/filter/$slug
        $filters = \App\Models\Filter::list($slug);
        $categories = array();
        if($slug == 'products') {
            $categories = \Modules\Products\Entities\Category::get()->toTree()->toArray();
        }
        if($slug == 'instructions') {
            $categories = \Modules\Instructions\Entities\Category::get()->toTree()->toArray();
        }
        
        $data = array(
            'list' => $list,
            'table' => $table,
            'fields' => $fields,
            'entities' => $entities,
            'roles' => $roles,
            'profile' => $profile,
            'sidebar' => $sidebar,
            'filters' => $filters,
            'categories' => $categories
        );

        return response()->json($data);
    }

    public function compose_show($slug, $id, Request $request)
    {
        //api/objects/$slug/$id
        $request = new Request([]);
        $detail = \App\Models\EntityObject::detail($slug, $id, $request);
        if(isset($detail['error'])) {
            return response()->json([
                    'message' => $detail['error']['message']
                ], $detail['error']['code']);
        }
        //api/objects/products?order_id=$id
        $products = array();
        if($slug == 'logistic_tasks') {
            $request = new Request(['order_id' => $id]);
            $products = \App\Models\EntityObject::list('products', $request);
        }
        //api/tables/order_products
        $table = \App\Models\Table::get_order_products();
        //api/entities
        $entities = \DB::table('data_types')->select(['slug', 'display_name_singular', 'display_name_plural', 'color'])->get();
        //api/history/$slug/$id
        $history = \App\Models\History::list($slug, $id);
        //api/roles
        //$roles = \App\Models\Role::list();
        //api/profile
        //$profile = \Auth::user();
        //api/sidebar/get
       // $sidebar = \Auth::user()->getSidebar();
        //api/entities/$slug/menu
        $menu = \App\Models\Menu::get($slug);
        
        $data = array(
            'detail' => $detail,
            'products' => $products,
            'table' => $table,
            'history' => $history,
            //'entities' => $entities,
            //'roles' => $roles,
            //'profile' => $profile,
            //'sidebar' => $sidebar,
            'menu' => $menu
        );

        return response()->json($data);
    }

    public function compose_show_module($slug, $id, $module, Request $request)
    {
        //api/objects/$slug/$id
        $request = new Request([]);
        $detail = \App\Models\EntityObject::detail_module($slug, $id, $module, $request);
        if(isset($detail['error'])) {
            return response()->json([
                    'message' => $detail['error']['message']
                ], $detail['error']['code']);
        }
        //api/objects/products?order_id=$id
        $products = array();
        if($slug == 'logistic_tasks') {
            $request = new Request(['order_id' => $id]);
            $products = \App\Models\EntityObject::list('products', $request);
        }
        //api/tables/order_products
        $table = \App\Models\Table::get_order_products();
        //api/entities
        $entities = \DB::table('data_types')->select(['slug', 'display_name_singular', 'display_name_plural', 'color'])->get();
        //api/history/$slug/$id
        $history = \App\Models\History::list($slug, $id, $module);
        //api/roles
        //$roles = \App\Models\Role::list();
        //api/profile
        //$profile = \Auth::user();
        //api/sidebar/get
       // $sidebar = \Auth::user()->getSidebar();
        //api/entities/$slug/menu
        $menu = \App\Models\Menu::get($slug);
        
        $data = array(
            'detail' => $detail,
            'products' => $products,
            'table' => $table,
            'history' => $history,
            //'entities' => $entities,
            //'roles' => $roles,
            //'profile' => $profile,
            //'sidebar' => $sidebar,
            'menu' => $menu
        );

        return response()->json($data);
    }

    public function show($slug, $id, Request $request)
    {
        $data = array(
            'title' => '',
            'deleted_at' => null,
            'columns' => array(
                'column_1' => array(),
                'column_2' => array()
            )
        );
        $perms = array(
            'read' => array(),
            'write' => array(),
        );
        $settings = get_settings();
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }

        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();
        $fields_data = array();
        $current = $entity_class::withTrashed()->where(['id' => $id])->firstOrFail();
        if(!$current) {
            return response()->json([
                'message' => 'Object not found'
            ], 404);
        }

        $data['title'] = $current->name;
        if(ValueHelper::isJson($data['title'])) {
            $data['title'] = json_decode($data['title'], true);
            if(isset($data['title']['value'])) {
                //$data['title'] = $data['title']['value'];
                $data['title'] = array(
                    'name' => $data['title']['value'],
                    'key' => 'name'
                );
            } else {
                $data['title'] = null;
            }
        } else {
            $data['title'] = array(
                'name' => $data['title'],
                'key' => 'name'
            );
        }
        if(isset($current->deleted_at) && $current->deleted_at)
            $data['deleted_at'] = $current->deleted_at;
        foreach($model_fields as $field) {
            if(!array_key_exists($field->field, $fields_data)) {
                $fields_data[$field->field] = Field::getDataByObject($field, $slug, $current);
                
            }
        }

        
        $hidden_fields = \App\Models\Field::getHiddenFields($slug);
        $sections_1 = \App\Models\FieldSection::get($slug, 1);
        $sections_2 = \App\Models\FieldSection::get($slug, 2);
        
        $products = array();
        if($slug == 'logistic_tasks' && $current->products)
            $products = json_decode($current->products, true);

        $sum = $count = $weight = 0;
        foreach($products as $product) {
            $sum+=(int)$product['price']*(int)$product['count'];
            $count+=(int)$product['count'];
            $weight+=(int)$product['weight']*(int)$product['count'];
        }

        // $history_days = \App\Models\History::where(['entity' => $slug, 'entity_id' => $current->id])->orderBy('created_at', 'DESC')->get()->groupBy(function ($val) {
        //         return \Carbon\Carbon::parse($val->created_at)->format('d.m.Y');
        //     });
        $users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;

        foreach($sections_1 as $section) {
            $fields = array();

            if($section->fields && count($section->fields)) {
                foreach($section->fields as $k => $field) {
                    if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !\Auth::user()->isAdmin())
                        continue;
                    $fields[] = $fields_data[$field->field];
                }
            }
            $children = array();
            if($ch = $section->children) {
                $subfields = array();
                foreach($ch as $subsection) {
                    if($subsection->fields && count($subsection->fields)) {
                        foreach($subsection->fields as $i => $subfield) {
                            if(isset($settings[$slug]['perms'][$subfield->field]) && $settings[$slug]['perms'][$subfield->field]['read'] == 'disabled' && !\Auth::user()->isAdmin())
                                continue;
                            $subfields[] = $fields_data[$subfield->field];
                        }
                    }
                    $children[] = array(
                        'id' => $subsection->id,
                        'name' => $subsection->name,
                        'sort' => $subsection->sort,
                        'fields' => $subfields,
                        'children' => array()
                    );
                }
            }
            $data['columns']['column_1'][] = array(
                'id' => $section->id,
                'name' => $section->name,
                'sort' => $section->sort,
                'fields' => $fields,
                'children' => $children
            );
        }

        foreach($sections_2 as $section) {
            $fields = array();

            if($section->fields && count($section->fields)) {
                foreach($section->fields as $k => $field) {
                    if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !\Auth::user()->isAdmin())
                        continue;
                    $fields[] = $fields_data[$field->field];
                }
            }
            $children = array();
            if($ch = $section->children) {
                $subfields = array();
                foreach($ch as $subsection) {
                    if($subsection->fields && count($subsection->fields)) {
                        foreach($subsection->fields as $i => $subfield) {
                            if(isset($settings[$slug]['perms'][$subfield->field]) && $settings[$slug]['perms'][$subfield->field]['read'] == 'disabled' && !\Auth::user()->isAdmin())
                                continue;
                            $subfields[] = $fields_data[$subfield->field];
                        }
                    }
                    $children[] = array(
                        'id' => $subsection->id,
                        'name' => $subsection->name,
                        'sort' => $subsection->sort,
                        'fields' => $subfields,
                        'children' => array()
                    );
                }
            }
            $data['columns']['column_2'][] = array(
                'id' => $section->id,
                'name' => $section->name,
                'sort' => $section->sort,
                'fields' => $fields,
                'children' => $children
            );
        }
        $data['hidden_fields'] = array();
        foreach($hidden_fields as $field) {
            if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !\Auth::user()->isAdmin())
                        continue;
                $data['hidden_fields'][] = $fields_data[$field->field];
            
        }

        return response()->json($data);
        
    }

    public function show_module($slug, $id, $module, Request $request)
    {
        // $comparisons = \DB::table('comparison_fields')->where([
        //     'module' => $module,
        //     'entity' => $slug,
        // ])->get()->keyBy('module_field')->toArray();
        
        $data = array(
            'title' => '',
            'deleted_at' => null,
            'columns' => array(
                'column_1' => array(),
                'column_2' => array()
            ),
            //'comparison_count'
        );
        
        $perms = array(
            'read' => array(),
            'write' => array(),
        );
        $settings = get_settings();
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $entity_fields = $entity_class::getFields();
        $model_fields = $entity_class::getFieldsByModule($module);
        $fields_data = array();
        $current = $entity_class::withTrashed()->where(['id' => $id])->firstOrFail();
        if(!$current) {
            return response()->json([
                'message' => 'Object not found'
            ], 404);
        }
        $data['title'] = $current->name;
        if(ValueHelper::isJson($data['title'])) {
            $data['title'] = json_decode($data['title'], true);
            if(isset($data['title']['value']))
                $data['title'] = $data['title']['value'];
        }
        $data['title'] = array(
                'name' => $data['title'],
                'key' => 'name'
            );
        if(isset($current->deleted_at) && $current->deleted_at)
            $data['deleted_at'] = $current->deleted_at;
        foreach($model_fields as $field) {
            // if($field->type == 'status')
            //     $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            // $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
            if(!array_key_exists($field->field, $fields_data)) {
                $fields_data[$field->field] = Field::getDataByObject($field, $slug, $current);
                
            }
        }
        $fields_data_entity = array();
        foreach($entity_fields as $field) {
            if($field->module)
                continue;
            // if($field->type == 'status')
            //     $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            // $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
            if(!array_key_exists($field->field, $fields_data_entity)) {
                $fields_data_entity[$field->field] = Field::getDataByObject($field, $slug, $current);
                
            }
        }
        
        
        $hidden_fields = \App\Models\Field::getHiddenFields($slug);
        $sections_1 = \App\Models\FieldSection::get($slug, 1, $module);
        $sections_2 = \App\Models\FieldSection::get($slug, 2, $module);
        
        $products = array();
        if($slug == 'logistic_tasks' && $current->products)
            $products = json_decode($current->products, true);

        $sum = $count = $weight = 0;
        foreach($products as $product) {
            $sum+=(int)$product['price']*(int)$product['count'];
            $count+=(int)$product['count'];
            $weight+=(int)$product['weight']*(int)$product['count'];
        }

        // $history_days = \App\Models\History::where(['entity' => $slug, 'entity_id' => $current->id])->orderBy('created_at', 'DESC')->get()->groupBy(function ($val) {
        //         return \Carbon\Carbon::parse($val->created_at)->format('d.m.Y');
        //     });
        $users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;

        foreach($sections_1 as $section) {
            $fields = array();
            $module_fields = $section->module_fields();
            if($module_fields && count($module_fields)) {
                foreach($module_fields as $k => $field) {
                    if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !\Auth::user()->isAdmin())
                        continue;
                    $fields[] = $fields_data[$field->field];
                }
            }
            $data['columns']['column_1'][] = array(
                'id' => $section->id,
                'name' => $section->name,
                'sort' => $section->sort,
                'fields' => $fields
            );
        }

        foreach($sections_2 as $section) {
            $fields = array();
            $module_fields = $section->module_fields();
            if($module_fields && count($module_fields)) {
                foreach($module_fields as $k => $field) {
                    if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !\Auth::user()->isAdmin())
                        continue;
                    $fields[] = $fields_data[$field->field];
                }
            }
            $data['columns']['column_2'][] = array(
                'id' => $section->id,
                'name' => $section->name,
                'sort' => $section->sort,
                'fields' => $fields
            );
        }
        $data['hidden_fields'] = array();
        $data['entity_fields'] = $fields_data_entity;
        foreach($hidden_fields as $field) {
            if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !\Auth::user()->isAdmin() || !isset($fields_data[$field->field]))
                        continue;
                $data['hidden_fields'][] = $fields_data[$field->field];
            
        }

        return response()->json($data);
    }

    public function store($slug, Request $request)
    {
        $settings = get_settings();
        $request->validate([
        ]);
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();

        $data = $request->all();

        $item = new $entity_class;
        $item->saveQuietly();
        $item->name = $entity->display_name_singular.' #'.$item->id;
        $item->user_id = \Auth::user()->id;
        if($slug == 'routes' && $request->date) {
            $item->date = $request->date;
            $item->employee = $request->employee_id;
            $item->car_id = $request->car_id;
        }
        $item->saveQuietly();

        // $history_text = 'Создана запись: '.$item->id;
        // if(isset(\Auth::user()->id))
        //     $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
        // else
        //     $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => 0, 'text' => $history_text]);
        // $history->save();

        // $history_text = 'Название: '.$item->name;
        // if(isset(\Auth::user()->id))
        //     $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
        // else
        //     $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => 0, 'text' => $history_text]);
        // $history->save();
        // info('4');
        $data = $item->getData();
        \App\Events\ObjectUpdated::dispatch('ObjectCreated', $data);
        \App\Models\History::createObject($slug, $item);
        // $data = $history->getData();
        // \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);

        return response()->json($data);
    }

    // public function create($slug, Request $request)
    // {
    //     $entity = $settings['models'][$slug];
    //     $entity_class = $entity->model_name;
    //     $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();

    //     $hidden_fields = \App\Models\Field::getHiddenFields($slug);
    //     $sections = \App\Models\FieldSection::get($slug);

    //     $users = \App\Models\User::get();
    //     foreach($users as $user) {
    //         $users_arr[$user->id] = $user;
    //     }
    //     $users = $users_arr;

    //     return view('objects.create', compact('entity', 'sections', 'hidden_fields', 'users', 'slug'));
    // }

    public function batch($slug, Request $request)
    {
        $settings = get_settings();
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();
        $rows = $request->rows;
        $objects = \App\Models\History::saveForObject($slug, $rows);
        
        \App\Models\Settings::clear_cache();
        $objects_collection_by_key = $objects['by_key'];
        $changed_fields = $objects['changed_fields'];
        foreach($rows as $k => $row) {
            $id = $row['id'];
            //$ids[] = $id;
            unset($row['id']);
            if(isset($row['password'])) {
                $row['password'] = Hash::make($row['password']);
            }

            $ob = $objects_collection_by_key[$id];
            foreach($row as $field => $value) {
                $ob->{$field} = $value;

                if($slug == 'logistic_tasks' && $field == 'client_id' && isset($model_fields[$field]) && $model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural) {
                    $ob->clients()->sync($value);
                }
                if($slug == 'clients' && $field == 'task_id' && isset($model_fields[$field]) && $model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural) {
                    $ob->logistic_tasks()->sync($value);
                }
                if($slug == 'cars' && $field == 'employee_id' && isset($model_fields[$field]) && $model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural) {
                    $ob->employees()->sync($value);
                }
                if($slug == 'employees' && $field == 'car_id' && isset($model_fields[$field]) && $model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural) {
                    $ob->cars()->sync($value);
                }
                if($slug == 'products' && $field == 'category_id' && isset($model_fields[$field]) && $model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural) {
                    $ob->categories()->sync($value);
                }
                if($slug == 'categories' && $field == 'product_id' && isset($model_fields[$field]) && $model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural) {
                    $ob->product()->sync($value);
                }
            }
            $ob->save();
            $data = $ob->getData($changed_fields);
            info('ObjectUpdated');
            \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);
        }
        foreach($request->rows as $row) {
            if(isset($row['role_id'])) {
                $objects_collection_by_key[$row['id']]->roles()->sync([$row['role_id']]);
                //$objects_collection_by_key[$row['id']]->roles()->sync(array_values($row['role_id']));
            }
        }
        
        

        return response()->json(['success' => true]);
    }

    public function delete($slug, Request $request)
    {
        $settings = get_settings();
        //\DB::table($slug)->whereIntegerInRaw('id', $request->ids)->delete();
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $items = $entity_class::whereIntegerInRaw('id', $request->ids);
        
        foreach($request->ids as $id) {
            $history_text = 'Удалена запись: '.$id;
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
            $history->save();
            // \App\Events\ObjectUpdated::dispatch('ObjectDeleted', [
            //     'id' => $id,
            //     'user_id' => \Auth::user()->id,
            //     'slug' => $slug,
            // ]);
            foreach($items->get() as $current) {
                $current->delete();
                \App\Events\ObjectUpdated::dispatch('ObjectDeleted', $current->getData());
            }
        }
        //$items->delete();

        return response()->json(['success' => true]);
    }

    public function restore($slug, Request $request)
    {
        $settings = get_settings();
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $items = $entity_class::withTrashed()->whereIntegerInRaw('id', $request->ids)->get();
        foreach($items as $k => $item) {
            $history_text = 'Восстановлена запись: '.$item->id;
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
            $history->save();
            $item->restore();
            \App\Events\ObjectUpdated::dispatch('ObjectRestored', $item->getData());
        }
        
        return response()->json(['data' => $items, 'success' => true]);
    }

    public function update($slug, $id, Request $request)
    {
        $settings = get_settings();
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $current = $entity_class::findOrFail($id);
        $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();

        
        if($request->relation_id) {
            $field = \DB::table('data_rows')->where('id', $request->field_id)->firstOrFail();
            if($field->is_plural) {
                $values = json_decode($current->{$field->field}, true);
                if(is_iterable($values)) {
                    if(array_search($request->relation_id, $values)) {
                        $history_text = 'Удалена связь с '. $field->display_name.': '.$request->relation_id;
                        if($history_text) {
                            if(isset(\Auth::user()->id))
                                $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
                            else
                                $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => 0, 'text' => $history_text]);
                            $history->save();
                        }
                        unset($values[array_search($request->relation_id, $values)]);
                        $current->{$field->field} = json_encode($values);
                    }
                   
                }
                
            } else {
                
                $history_text = 'Удалена связь с '. $field->display_name.': '.$current->{$field->field};
                if($history_text) {
                    if(isset(\Auth::user()->id))
                        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
                    else
                        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => 0, 'text' => $history_text]);
                    $history->save();
                }
                $current->{$field->field} = null;
            }
            $current->save();
        } else {
            $data = array();
            $history_items = array();
            foreach($request->all() as $field_name => $value) {
                if(!$value && $field_name == 'name')
                    continue;
                if(!$value) {
                    $data[$field_name] = null;
                } else {
                    foreach($model_fields as $field) {
                        if($field->field == $field_name && $field->type == 'date')
                            $data[$field_name] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
                        elseif($field->field == $field_name) {
                            if(is_array($value))
                                $data[$field_name] = json_encode($value, JSON_UNESCAPED_UNICODE);
                            else
                                $data[$field_name] = $value;
                        }
                    }
                }
            }
            $changed_fields = array();
            foreach($model_fields as $field) {
                if($request->{$field->field} && $field->field != 'id') {
                    $changed_fields[] = $field->id;
                    if(is_array($request->{$field->field}))
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    else
                        $value = $request->{$field->field};
                    $history_text = $field->display_name.': '.$current->{$field->field}.' -> '.$value;
                    if($history_text) {
                        if(isset(\Auth::user()->id))
                            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
                        else
                            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => 0, 'text' => $history_text]);
                        $history->save();
                        $history_items[] = $history;
                    }
                }
            }
            $current->update($data);
            $data = $current->getData();

            \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);
            \App\Events\ObjectUpdated::dispatch('ObjectCreated', $data);
            if(count($history_items)) {
                $data = \App\Models\History::getDataList($history_items);
                \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $data);
            }

            return response()->json($current);
        }
        
    }

    public function destroy($slug, $id, Request $request)
    {
        if($id == 1 && $slug == 'users')
            return response()->json([
                'message' => 'Администратор не может быть удален'
            ], 403);
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $current = $entity_class::findOrFail($id);
        

        \App\Events\ObjectUpdated::dispatch('ObjectDeleted', [
            'id' => $id,
            'user_id' => \Auth::user()->id,
            'slug' => $slug
        ]);

        $current->delete();
    }

    public function edit_section($slug, $id, $section_id) {
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $current = $entity_class::findOrFail($id);

        $fields = \App\Models\Field::getVisibleFields($slug);
        $hidden_fields = \App\Models\Field::getHiddenFields($slug);
        // $sections_1 = \App\Models\FieldSection::get($slug, 1);
        // $sections_2 = \App\Models\FieldSection::get($slug, 2);
        
        $section = \App\Models\FieldSection::findOrFail($section_id);

        return view('objects.edit_section', compact('current', 'fields', 'hidden_fields', 'section', 'slug'));
    }

    public function search($model, Request $request)
    {
        $data = array();
        $field_name = 'name';
        if($request->q && mb_strlen($request->q) >= 0) {

            $q = str_replace(' ', '%', $request->q);
            $q = '%'.$q.'%';
            if (\Schema::hasColumn($model, 'last_name')) {
                $items = \DB::table($model)->where([
                    [$field_name, 'LIKE', $q],
                    ['deleted_at', null]
                ])->orWhere([
                    ['last_name', 'LIKE', $q],
                    ['deleted_at', null]
                ])->orWhere([
                    ['id', (int)$request->q],
                    ['deleted_at', null]
                ])->limit(20)->get();
            } else {
                $items = \DB::table($model)->where([
                    [$field_name, 'LIKE', $q],
                    ['deleted_at', null]
                ])->orWhere([
                    ['id', (int)$request->q],
                    ['deleted_at', null]
                ])->limit(20)->get();
            }
            
            if(!$items) {
                $items = \DB::table($model)->whereNull('deleted_at')->limit(20)->get();
            }
        } else {
            $items = \DB::table($model)->whereNull('deleted_at')->limit(20)->get();
        }
        foreach($items as $item) {
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
            $data[] = array(
                'label' => [
                    'id' => $item->id,
                    'text' => $name.(isset($item->last_name) ? ' '.$item->last_name: ''),
                    'color' => isset($item->color) ? $item->color : '',
                    'file' => $photo
                ],
                'value' => $item->id
            );

            //$data[$item->id] = $item->{$field_name};
            /*
            $data[] = array(
                'label' => $item->{$field_name},//isset($item->last_name) ? $item->{$field_name}.' '.$item->last_name." ($item->id)" : $item->{$field_name}."($item->id)",
                'value' => (string)$item->id
            );*/
        }
        return response()->json($data);
    }

    public function export($slug, Request $request) 
    {
        $settings = get_settings();

        $params = $request->all();

        $user = \Auth::user();        
        $tables = \Auth::user()->tables;
        if($tables)
            $tables = json_decode($tables, true);
        else
            $tables = array();
        if(!isset($tables[$slug])) {
            $role = \App\Models\Role::findOrFail(\Auth::user()->role_id);
            if($role && $role->tables) {
                $role_tables = json_decode($role->tables, true);
                if(!is_array($role_tables))
                    $role_tables = array();
                if(isset($role_tables[$slug])) {
                    $tables[$slug] = $role_tables[$slug];
                    $user->tables = json_encode($tables);
                    $user->saveQuietly();
                }
            }

            if(!isset($tables[$slug])) {
                $settings = \DB::table('settings')->where('key', 'tables')->firstOrFail();
                if($settings->value) {
                    $tables_all = json_decode($settings->value, true);
                    if(isset($tables_all[$slug])) {
                        $tables[$slug] = $tables_all[$slug];
                        $user->tables = json_encode($tables);
                        $user->saveQuietly();
                    }
                }
            }
        }
        info('EXPORT');
        info($request->fields);
        if(isset($tables[$slug]) && !$request->fields) {
            if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
                return response()->json([
                    'message' => 'Entity not found'
                ], 404);
            }
            $entity = $settings['models'][$slug];
            $entity_class = $entity->model_name;
            $model_fields = collect($settings[$slug]['fields']);//$entity_class::getFields();
            $table_columns = collect($tables[$slug]);
            $table_columns = $table_columns->keyBy('key')->toArray();
            foreach($table_columns as $key => $column) {
                if(!$model_fields->contains('field', $key) && $key != 'isChoose' && $key != 'actions' && $key != 'iconDrag' && $key != 'iconDelete') {
                    
                    unset($table_columns[$key]);
                }
                    
            }
            //info($table_columns);
            foreach ($model_fields as $field) {
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->type != 'file' && $field->type != 'password') {
                    //$table_columns[$field->field] = array('title' => $field->display_name);
                    // $params['headings']['names'][] = $field->display_name;
                    // $params['headings']['fields'][] = $field->field;
                } elseif($field->type != 'text_group' && $field->type != 'password' && $field->type != 'file') {
                    //$table_columns[$field->field] = array('title' => $field->display_name);
                    
                    //$params['fields'][$field->field] = $field->field;
                }
            }
        } else {
            info('STEP2');
            $entity = $settings['models'][$slug];
            $entity_class = $entity->model_name;
            $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();
            $table_columns = array();
            $i = 0;
            foreach ($model_fields as $field) {
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->type != 'file' && $field->type != 'password') {
                    if($request->fields && in_array($field->field, $request->fields)) {
                        $table_columns[$field->field] = array('title' => $field->display_parent_name ? $field->display_parent_name.', '.$field->display_name : $field->display_name, 'key' => $field->field, 'sort' => array_search($field->field, $request->fields));
                    } elseif(!$request->fields) {
                        $table_columns[$field->field] = array('title' => $field->display_parent_name ? $field->display_parent_name.', '.$field->display_name : $field->display_name, 'key' => $field->field, 'sort' => $i);
                        $i++;
                    }
                    
                    
                    //$params['headings']['names'][] = $field->display_name;
                    //$params['fields'][$field->field] = $field->field;
                }
            }
            array_sort_by_column($table_columns, 'sort', SORT_ASC, SORT_NATURAL);
        }

        info($table_columns);
        $params['headings']['fields'] = array();
        foreach($table_columns as $column) {
            if(isset($column['title']) && $column['key'] != 'isChoose' && $column['key'] != 'actions' && $column['key'] != 'iconDrag' && $column['key'] != 'iconDelete') {
                $params['headings']['names'][] = $column['title'];
                $params['headings']['fields'][] = $column['key'];
            }
        }
        $now = strtotime(now());
        Excel::store(new ObjectExport($slug, $params), 'export'.$now.'.xlsx');
        
        return response()->json(['link' => 'https://'.tenant('id').'.compas.pro/storage/tenant'.tenant('id').'/app/public/export'.$now.'.xlsx']);;//Excel::raw(new ObjectExport($slug, $params), 'export.xlsx');
    }

}