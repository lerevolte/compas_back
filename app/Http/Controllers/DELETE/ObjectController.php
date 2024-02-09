<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use App\Models\SidebarItem;

class ObjectController extends Controller
{
    public function list($slug, Request $request)
    {
        // if($request->n) {
            
        //     echo Auth::user()->id;
        //     die();
        // }
        $limit = $request->per_page ? $request->per_page : 25;
        $page = $request->page ? $request->page : 1;
        $sort_field = $request->sort_field ? $request->sort_field : 'id';
        $sort_order = $request->sort_order ? $request->sort_order : 'desc';
        //cache()->flush();
        $settings = get_settings();
        $tenant = tenant('id');
        $user = \Auth::user();

        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();
        $visible_fields = \App\Models\Field::getVisibleFields($slug);

        $users = \App\Models\User::get(['id', 'name', 'last_name'])->keyBy('id')->toArray();
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $field) {
            if($field->type == 'status')
                $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
            $perms['read'][$field->field] = (!optional($request->user())->canRead($field->field, $slug) ? 'disabled':'');
            $perms['write'][$field->field] = (!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'');
        }
        //if($request->paginate) {
        $paginator = $entity_class::orderBy($sort_field, $sort_order);
        if($request->filter && is_array($request->filter)){
            // $filter = collect($request->filter)->filter()->all();
            // $paginator = $paginator->where($filter);
            //$f = array('created_at' => '2023-05-29');
            foreach($request->filter as $field => $val) {
                if($field == 'created_at' || $field == 'updated_at')
                    $paginator = $paginator->whereDate($field, $val);
                else
                    $paginator = $paginator->where($field, $val);
            }
        }
        if($request->order_id && $slug == 'products') {
            $order = \App\Models\Order::find($request->order_id);
            if($order) {
                $products = json_decode($order->products, true);
                $product_ids = array();
                $fix_order = false;
                if(is_array($products)) {
                    foreach($products as $product_k => $product) {
                        if(!isset($product['id'])) {
                            $prod = \Modules\Products\Entities\Product::where('name', $product['name'])->first();
                            if($prod) {
                                $fix_order = true;
                                $product_ids[] = $prod->id;
                                $products[$product_k]['id'] = $prod->id;
                            }
                            
                        } else {
                            $product_ids[] = $product['id'];
                        }
                    }
                    if($fix_order) {
                        $order->products = json_encode($products, JSON_UNESCAPED_UNICODE);
                        $order->saveQuietly();
                    }
                    $paginator = $paginator->whereIn('id', $product_ids);
                }
            }
        };
        // // if($request->nt) {
        // //     $f = array('created_at' => '2023-05-29');
        // //     foreach($f as $field => $val) {
        // //         if($field == 'created_at' || $field == 'updated_at')
        // //             $paginator = $paginator->whereDate($field, $val);
        // //         else
        // //             $paginator = $paginator->where($field, $val);
        // //     }
        // // }
        // if($request->q) {
        //     $search_columns = $model_fields->filter(function ($field) {
        //                         return ($field->type != 'relation' && $field->type != 'status');
        //                     })->pluck('field')->toArray();
        //     $q = $request->q;
        //     $paginator = $paginator->where(function ($query) use ($search_columns, $q) {
        //         foreach ($search_columns as $column) {
        //             $query->orWhere($column, 'like', "%{$q}%");
        //         }
        //     });
        // };
        // $paginator = $paginator->paginate($limit);
        
        // $objects = array();
        // $field_values = array();
        
        // foreach ($paginator->items() as $item) {
        //     $data = array(
        //         'id' => $item->id
        //     );
        //     foreach($model_fields as $field) {
        //         if(!array_key_exists($field->field, $data)) {
        //             $value = $item->{$field->field};
        //             $data[$field->field] = array(
        //                 'value' => $value,
        //                 'type' => $field->type,
        //                 'read_only' => $field->only_read,
        //                 'can_edit' => !$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0,
        //                 'color' => $field_colors[$field->field]
        //             );
        //             if(isset($settings[$slug]['list_values'][$field->field])) {
        //                 $data[$field->field]['options'] = $settings[$slug]['list_values'][$field->field];
        //             };
        //             if($field->type == 'relation') {
        //                 $data[$field->field]['related_table'] = json_decode($field->details, true)['table'];
        //             }
        //         }
        //     }
        //     if(isset($order) && $order && $slug == 'products') {
        //         $products = json_decode($order->products, true);
        //         if(is_array($products)) {
        //             foreach($products as $product) {
        //                 $data['product_price'] = array(
        //                     'value' => $product['price'],
        //                     'type' => 'text',
        //                     'read_only' => 0,
        //                     'can_edit' => 1,
        //                     'color' => ''
        //                 );
        //                 $data['product_count'] = array(
        //                     'value' => $product['count'],
        //                     'type' => 'text',
        //                     'read_only' => 0,
        //                     'can_edit' => 1,
        //                     'color' => ''
        //                 );
        //                 $data['product_weight'] = array(
        //                     'value' => $product['weight'],
        //                     'type' => 'text',
        //                     'read_only' => 0,
        //                     'can_edit' => 1,
        //                     'color' => ''
        //                 );
        //                 $data['product_sum'] = array(
        //                     'value' => $product['sum'],
        //                     'type' => 'text',
        //                     'read_only' => 1,
        //                     'can_edit' => 0,
        //                     'color' => ''
        //                 );
        //             }
        //         }
                
        //     }
            
        //     $objects[] = $data;
        // }
        // } else {
        //     $items = $entity_class::orderBy('id', 'desc')->take($limit)->get();
        // }
        
        cache()->flush();
        $settings = get_settings();
        $tenant = tenant('id');
        $parent_menu = null;
        $active_menu = SidebarItem::where('link', '/objects/'.$slug)->first();
        
        if($active_menu && $active_menu->parent_id) {
            $parent_menu = SidebarItem::find($active_menu->parent_id);
        }
        $user = \Auth::user();
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        //$entity
        $model_fields = $entity_class::getFields();
        $table_settings_name = 'table_page_orders';
        $visible_fields = \App\Models\Field::getVisibleFields($slug);
        //$hidden_fields = \App\Models\Field::getHiddenFields($slug);
        $filters = \App\Models\Filter::where(['user_id' => $user->id, 'data_type' => $entity->id])->orderBy('sort', 'asc')->get();
        if(!$filters->count()) {
            $filter = \App\Models\Filter::create([
                'name' => 'Фильтр',
                'user_id' => $user->id,
                'data_type' => $entity->id,
                'is_active' => 1,
                'config' => json_encode(array('fields' => array('id' => '')))
            ]);
            $filters->push($filter);
        } 
        foreach($filters as $filter) {
            if($filter->is_active)
                $active_filter = $filter;
        }
        
        //dd($settings);
        $table_settings = $user->{$table_settings_name} ? json_decode($user->{$table_settings_name}, true) : '';
        $users = \App\Models\User::get(['id', 'name', 'last_name'])->keyBy('id')->toArray();
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $field) {
            if($field->type == 'status')
                $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            $field_colors[$field->field] = $field->label_color ?? '';
            $perms['read'][$field->field] = (!optional($request->user())->canRead($field->field, $slug) ? 'disabled':'');
            $perms['write'][$field->field] = (!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'');
        }

        if (isset(request()->date))
            $date = request()->date;
        elseif ($user->carrier_date)
            $date = $user->carrier_date;
        else
            $date = date('d.m.Y');
        if (!strstr($date, '-')) {
            $date_end = $date;
        } else {
            $d = explode('-', $date);
            $date = $d[0];
            $date_end = $d[1];
        }
        if ($date == $date_end)
            $full_date = $date;
        else
            $full_date = $date.'-'.$date_end;

        $items = $entity_class::orderBy('id', 'desc')/*->take(50)*/->get();

        $objects = array();
        $field_values = array();
        foreach ($items as $key => $item) {
            $delete_btn = '<a class="dropdown-item js-delete-model" data-model="'.$slug.'" href="javascript:;"  data-id="'.$item->id.'"><span class="red">Удалить</span></a>';
            $history_btn = '<a class="dropdown-item js-history" href="javascript:;" data-entity="'.$slug.'" data-id="'.$item->id.'">История</a>';
            $last_col = '<div class="dropdown">
                  <button class="btn btn-transparent px-0 btn-actions" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                  </button>
                  <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions" aria-labelledby="dropdownMenuButton">
                    '.$history_btn.'
                    '.$delete_btn.'
                  </div>
                </div>';
            $data = array(
                'id' => $item->id,
                'checkbox' => '<div class="not-selectable disabled justify-content-center"><label class="checkbox-container m-auto"><input type="checkbox" class="js-checkbox-row"><span class="checkmark"></span></label></div>',
                'actions' => '<div class="not-selectable disabled" data-field="actions">'.$last_col.'</div>'
            );
            foreach($model_fields as $field) {
                if(!array_key_exists($field->field, $data)) {
                    $value = $item->{$field->field};
                    if($field->field == 'products') {
                        $value = ValueHelper::prepareProducts($item->{$field->field});
                        $data[$field->field] = '<div class="js-editable '.(!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'').'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="orders" title="'.strip_tags($value).'" style="'.($field->label_color ? 'color:'.$field->label_color : '').'">'.$value.'</div>';
                    } elseif(($field->type == 'file' || $field->type =='image') && $item->{$field->field} && $item->{$field->field} != 'null') {
                        $photos = $item->getValue($field);
                        $html = '<div class="table-gallery">
                            <div class="table-gallery__inner">';
                        foreach($photos as $photo_key => $photo) {
                            if(strstr($photo->name, '.pdf'))
                                $html.='<a href="/storage/tenant'.$tenant.'/app/'.$photo->path.'" data-fancybox="gallery-'.$field->id.'-'.$item->id.'" target="_blank" '.($photo_key ? 'class="d-none"':'').'>
                                            <picture class="pdf"><source srcset="/img/pdf.svg" type="image/webp"><img  src="/img/pdf.svg" ></picture>
                                        </a>';

                            elseif(!strstr($photo->name, '.jpeg') && !strstr($photo->name, '.jpg') && !strstr($photo->name, '.png'))
                                $html.='<a href="storage/tenant'.$tenant.'/app/'.$photo->path.'" data-fancybox="gallery-'.$field->id.'-'.$item->id.'" target="_blank" '.($photo_key ? 'class="d-none"':'').'>
                                            <picture class="pdf"><source srcset="/img/pdf.svg" type="image/webp"><img  src="/img/pdf.svg" ></picture>
                                        </a>';
                            else
                                $html.='<a href="/storage/tenant'.$tenant.'/app/'.$photo->path.'" data-fancybox="gallery-'.$field->id.'-'.$item->id.'" target="_blank" '.($photo_key ? 'class="d-none"':'').'>
                                            <picture><source srcset="'.\Thumbnail::src('https://opt6.compas.pro'.\Storage::disk()->url('tenant'.$tenant.'/app/'.$photo->path))->heighten(200)->url().'" type="image/webp"><img class="table-gallery__preview" src="'.\Thumbnail::src('https://opt6.compas.pro'.\Storage::disk()->url('tenant'.$tenant.'/app/'.$photo->path))->heighten(200)->url().'" ></picture>
                                        </a>';
                        }
                        $html.= '
                            </div>
                            <span class="table-gallery__count">'.count($photos).'</span>
                         </div>';
                         $data[$field->field] = $html;
                    } elseif(!isset($settings[$slug]['list_values'][$field->field])) {

                        $data[$field->field] = '<div class="js-editable '.$settings[$slug]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'" data-value="'.$item->{$field->field}.'" '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').'>'.$value.'</div>';
                    } elseif(isset($settings[$slug]['list_values'][$field->field]) && $field->type != 'status') {
                        if($field->type == 'select_dropdown' && $field->is_plural && is_array(json_decode($value, true)))
                            $value = implode(', ', json_decode($value, true));
                        $data[$field->field] = '<div class="js-editable position-relative" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'" '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').'>'.$value.'</div>';;
                        foreach($settings[$slug]['list_values'][$field->field] as $list_value => $list_item) {
                            if($list_value == $value) {
                                $data[$field->field] = '<div class="js-editable '.$settings[$slug]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'"  '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').' data-value="'.$item->{$field->field}.'">'.$list_item.'</div>';
                            }
                        };
                    } else {
                        $status_select = '<div data-field="'.$field->field.'" class="form-group status-group disabled" data-id="'.$item->id.'" data-type="'.$field->type.'">';
                        $text_value = '';
                        $exist = false;
                        $i = 0;
                        $first_item = null;
                        foreach($settings[$slug]['list_values'][$field->field] as $list_item) {
                            if(!$i) {
                                $first_item = $list_item;
                            }
                            if($field->field == 'point_status' && $item->{$field->field} && $item->{$field->field} == $list_item->id) {
                                $text_value = $list_item->value;
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect '.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?'comment-cancel-tooltip':'').'"  data-title="'.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?($item->comment_cancel ?? ' '):' ').'" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                                $exist = true;
                            } elseif($item->{$field->field} && $item->{$field->field} == $list_item->id) {
                                $text_value = $list_item->value;
                                if($list_item->file)
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$list_item->file.') '.$list_item->color.'"></div>';
                                else
                                    $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"><div class="d-none">'.$item->{$field->field}.'</div></div>';
                                $exist = true;
                            } elseif(!$item->{$field->field}) {
                                $text_value = $list_item->value;
                                if($list_item->file)
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$list_item->file.') '.$list_item->color.'"></div>';
                                else
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                                $exist = true;
                                break;
                            }
                            $i++;
                        };
                        if(!$exist && isset($settings[$slug]['list_values'][$field->field]) && $first_item) {
                            if($first_item->file)
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$first_item->file.') '.$first_item->color.'"></div>';
                            else
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$first_item->color.'"></div>';
                        }
                        
                        $status_select.= '<span class="js-select-text">'.$text_value.'</span></div>';
                        $data[$field->field] = $status_select;
                    }
                }
            }
            
            $objects[] = $data;
        }
        $model = $slug;
        if(!$request->n) {
            $table_settings = ($user->tables ? json_decode($user->tables, true) : array());
            //dd($table_settings);
            return view('objects.list_new', compact('user', 'objects','filters', 'model_fields', 'visible_fields', 'date', 'table_settings', 'entity', 'settings', 'model', 'table_settings', 'parent_menu', 'active_menu'));
        }
        else
            return view('objects.list', compact('user', 'objects', 'filters', 'model_fields', 'visible_fields', 'date', 'table_settings', 'entity', 'settings', 'model'));
    }

    public function get($model, Request $request)
    {
        $class = '\\App\\Models\\' . \Str::title($model);
        $objects = $class::where('account_id', Auth::user()->account_id)->orderBy('sort');
        $table_name = app($class)->getTable();
        $model_fields = $class::getFields();
        $settings = get_settings();

        if($model == 'carrier') {
            $count_wo_type = $class::withTrashed()->whereNull('type')->get()->count();
            $types = $class::types();
            $type = array_key_first($types);
            if($request->type)
                $type = $request->type;
            if($request->type || !$count_wo_type) {
                $objects = $objects->where('type', 'like', ['name' => "%$type%"]);
            } else {
                $objects = $objects->whereNull('type');
            }
        }
        

        $objects = $objects->get();

        $items = array();
        foreach ($objects as $key => $item) {
            foreach($model_fields as $field) {
                $value = $item->{$field->field};
                $value = $item->getValue($field);
                if($field->field == 'products')
                    $value = ValueHelper::prepareProducts($item->{$field->field});
                if($field->field == 'name') {
                    $data[$field->field] = '<div class="js-editable '.$settings[$table_name]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-id="'.$item->id.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="'.$table_name.'" title="'.strip_tags($value).'" style="'.($field->label_color ? 'color:'.$field->label_color : '').'">'.$value.'</div>';
                } elseif($field->field == 'point_type') {
                    $data[$field->field] = '<div class="js-editable '.$settings[$table_name]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="'.$table_name.'" title="'.strip_tags($value).'">'.$item->getValue($settings['settings']['point_type']).'</div>';
                } elseif(!isset($settings[$table_name]['list_values'][$field->field]))
                    $data[$field->field] = '<div class="js-editable '.$settings[$table_name]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="'.$table_name.'" title="'.strip_tags($value).'" style="'.($field->label_color ? 'color:'.$field->label_color : '').'">'.$value.'</div>';
                else {
                    $status_select = '<div class="form-group status-group m-auto '.($field->field == 'point_status' ? 'comment-cancel-form':'').' disabled" data-id="'.$item->id.'">';
                    foreach($settings[$table_name]['list_values'][$field->field] as $list_item) {
                        if($field->field == 'point_status' && $item->{$field->field} && $item->{$field->field} == $list_item->id) {
                            $status_select.= '<div class="point_status_rect '.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?'comment-cancel-tooltip':'').'"  data-title="'.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?($item->comment_cancel ?? ' '):' ').'" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                        } elseif($item->{$field->field} && $item->{$field->field} == $list_item->id) {
                            if($list_item->file)
                                $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: url('.\Storage::disk()->url($list_item->file).') '.$list_item->color.'"></div>';
                            else
                                $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                        } elseif(!$item->{$field->field}) {
                            if($list_item->file)
                                $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: url('.\Storage::disk()->url($list_item->file).') '.$list_item->color.'"></div>';
                            else
                                $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                            break;
                        }
                    };
                    $status_select.= '<select name="'.$field->field.'" class="form-control form-control-status form-control-status-select field-status-select2 js-field-status '.($field->field == 'point_status' ? 'form-control-status-delivery':'').'">';
                    foreach($settings[$table_name]['list_values'][$field->field] as $key => $list_item) {
                        $status_select.= '<option data-file="'.\Storage::disk()->url($list_item->file).'" data-color="'.$list_item->color.'" value="'.$list_item->id.'"'.($item->{$field->field} == $list_item->id || (!$item->{$field->field} && !$key) ? ' selected="selected"' : '').'>'.$list_item->name.'</option>';
                    }
                    $status_select.= '</select></div>';
                    $data[$field->field] = '<div data-field="'.$field->field.'">'.$status_select.'</div>';
                }
            }
            foreach($data as $field => $content) {
                if(isset($settings[$table_name]['perms'][$field]['read']) && $settings[$table_name]['perms'][$field]['read'] == 'disabled')
                    unset($data[$field]);
            }
            $items['data'][] = $data;
        };

        return response()->json($items);
    }

    public function show($slug, $id, Request $request)
    {
        
        // $parent = \Modules\Journal\Entities\EntityRelation::find(2);
        //     $node = \Modules\Journal\Entities\EntityRelation::find(3);
        //     \Modules\Journal\Entities\EntityRelation::fixTree();
        //     $parent->appendNode($node);
        //     dd($parent);

        $status_field = \DB::table('data_rows')->where('field', 'point_status')->first();
        $order_statuses = \DB::table('field_values')->where('field_id', $status_field->id)->get();

        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();

        $record_fields = array();
        if($slug == 'cars') { ///И МОДУЛЬ ЖУРНАЛА ВКЛЮЧЕН
            $record_entity = \DB::table('data_types')->where('slug', 'journal_records')->first();

            
            $record_entity_class = $record_entity->model_name;
            $record_fields = $record_entity_class::getFields();
        };

        $remnant_fields = array();
        if($slug == 'products' || $slug == 'cars') {
            $remnant_entity = \DB::table('data_types')->where('slug', 'remnants')->first();
            $remnant_entity_class = $remnant_entity->model_name;
            $remnant_fields = $remnant_entity_class::getFields();
        };
        
        $mileage_fields = array();
        if($slug == 'cars') {
            $mileage_entity = \DB::table('data_types')->where('slug', 'mileages')->first();
            $mileage_entity_class = $mileage_entity->model_name;
            $mileage_fields = $mileage_entity_class::getFields();
        }

        $current = $entity_class::where(['id' => $id])->first();

        $salary_fields = array();
        $fund_fields = array();
        if($slug == 'drivers') {
            $salary_entity = \DB::table('data_types')->where('slug', 'salaries')->first();
            $salary_entity_class = $salary_entity->model_name;
            $salary_fields = $salary_entity_class::getFields();

            $fund_entity = \DB::table('data_types')->where('slug', 'emergency_fund_records')->first();
            $fund_entity_class = $fund_entity->model_name;
            $fund_fields = $fund_entity_class::getFields();
            //$current->calcStat();
        }

        $hidden_fields = \App\Models\Field::getHiddenFields($slug);
        $sections_1 = \App\Models\FieldSection::get($slug, 1);
        $sections_2 = \App\Models\FieldSection::get($slug, 2);
        
        $products = array();
        if(isset($current->products) && $current->products)
            $products = json_decode($current->products, true);
        $expenses = array();
        if(isset($current->expenses) && $current->expenses)
            $expenses = json_decode($current->expenses, true);

        $sum = $count = $weight = 0;
        foreach($products as $product) {
            $sum+=(int)$product['price']*(int)$product['count'];
            $count+=(int)$product['count'];
            if(isset($product['weight']))
                $weight+=(int)$product['weight']*(int)$product['count'];
        }
        $sum_expenses = $count_expenses = 0;
        foreach($expenses as $expense) {
            $sum_expenses+=(int)$expense['price']*(int)$expense['count'];
            $count_expenses+=(int)$expense['count'];
        }

        $remnants = array();
        $mileages = array();
        if($slug == 'cars') {
            $product_ids = array();
            foreach($current->journal_records as $record) {
                if($record['products']) {
                    $objects = json_decode($record['products'], true);
                    foreach($objects as $obj) {
                        if(isset($obj['id']))
                            $product_ids[] = $obj['id'];
                    }
                }
            }
            if(count($product_ids)) {
                $remnants = \Modules\Products\Entities\Remnant::whereIntegerInRaw('id', $product_ids)->get()->toArray();
            }

            $mileages = $current->mileages;
        }

        $history_days = \App\Models\History::where(['entity' => $slug, 'entity_id' => $current->id])->orderBy('created_at', 'DESC')->get()->groupBy(function ($val) {
                return \Carbon\Carbon::parse($val->created_at)->format('d.m.Y');
            });
        $users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;


        return view('objects.detail', compact('current', 'sections_1', 'sections_2', 'hidden_fields', 'products', 'expenses', 'sum', 'count', 'weight', 'sum_expenses', 'count_expenses', 'users', 'history_days', 'slug', 'order_statuses', 'record_fields', 'remnant_fields', 'remnants', 'mileage_fields', 'mileages', 'salary_fields', 'fund_fields'));
        
    }

    public function store($slug, Request $request)
    {
        $request->validate([
        ]);

        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();

        $data = $request->all();
        $item = $entity_class::create($data);
        if($slug == 'orders')
            $item->store_name = $entity->display_name_singular.' #'.$item->id;
        else
            $item->name = $entity->display_name_singular.' #'.$item->id;
        $item->save();

        $history_text = 'Создана запись: '.$item->id;
        if(isset(\Auth::user()->id))
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
        else
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => 0, 'text' => $history_text]);
        $history->save();

        $history_text = 'Название: '.(isset($item->store_name) ? $item->store_name : $item->name);
        if(isset(\Auth::user()->id))
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
        else
            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => 0, 'text' => $history_text]);
        $history->save();

        return response()->json($item);
    }

    public function create($slug, Request $request)
    {
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();

        $hidden_fields = \App\Models\Field::getHiddenFields($slug);
        $sections = \App\Models\FieldSection::get($slug);

        $users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;

        return view('objects.create', compact('entity', 'sections', 'hidden_fields', 'users', 'slug'));
    }

    public function edit_list($slug, Request $request)
    {
        $settings = get_settings();
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();
        //dd($settings);
        $visible_fields = \App\Models\Field::getVisibleFields($slug);
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $field) {
            if($field->type == 'status')
                $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            $field_colors[$field->field] = $field->label_color ?? '';
            $perms['read'][$field->field] = (!optional($request->user())->canRead($field->field, $slug) ? 'disabled':'');
            $perms['write'][$field->field] = (!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'');
        }

        $items = \DB::table($slug)->whereIntegerInRaw('id', $request->ids)->orderBy('id', 'desc')->get();
        
        $rows = array();
        foreach ($items as $key => $item) {
            $rows[$item->id] = array();
            foreach($model_fields as $field_num => $field) {
                if(!$field->only_read && !array_key_exists($field->field, $rows[$item->id])) {
                    $value = $item->{$field->field};
                    //$html = view('users.edit', compact('user'))->render();
                    if($field->type == 'relation' && !$field->is_plural) {
                        $html = '<div class="position-relative">
                            <ul class="row g-2 ps-0 flex-nowrap">
                                <li class="col-lg-12">
                                    <div class="position-relative">
                                        <select name="select" class="js-select" '.($field->is_plural ? 'multiple':'') .' >';
                        if(!$field->is_plural)
                            $html.= '<option value="" '.(!$item->{$field->field} ? 'selected':'').'>не выбрано</option>';
                        if(isset($settings[$slug]['list_values']) && count($settings[$slug]['list_values'][$field->field])) {
                            foreach($settings[$slug]['list_values'][$field->field] as $val => $text) {
                                $html.= '<option value="'.$val.'" '.($item->{$field->field} == $val || $field->is_plural && in_array($item->{$field->field}, array_keys($settings[$slug]['list_values'][$field->field])) ? 'selected':'').' data-value="'.$text.'">'.$text.'</option>';
                            }
                        }

                        $html.=         '</select>
                                    </div>
                                </li>
                            </ul>
                        </div>';
                        $rows[$item->id][$field->field] = $html;
                        /*$html = '<div class="card-relations js-editable" data-field="'.$field->field.'" data-type="relation" '.($field->is_plural ? 'data-multiple="1"':'').'>
                                    <div class="mt-2 card p-3 bg-light d-none">
                                        <div class="d-flex justify-content-start align-items-center" style="flex-grow: 1;">
                                            <div class="dropdown">
                                                <a class="dropdown-toggle me-2" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                                                    <svg class="icon icon-dots">
                                                        <use xlink:href="#icon-dots"></use>
                                                    </svg>
                                                </a>

                                                <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions dropdown-menu-left" href="javascript:;" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" >
                                                    <a href="#" class="dropdown-item">Посмотреть</a>
                                                    <a href="#changeDriver" data-fancybox data-touch="false" class="dropdown-item-fancy">Заменить</a>
                                                    <a href="#" class="dropdown-item js-delete-relation" data-id="" data-model="cars" data-field="driver_id">Удалить</a>
                                                </div>
                                            </div>
                                            <h5 class="h5 mb-0 w-100">
                                                <div class="position-relative">
                                                    <div class="row g-2 flex-nowrap">
                                                        <div class="col-lg-12">
                                                            <div class="position-relative">
                                                                <select name="select" >
                                                                    <option value="" selected>не выбрано</option>';
                                                            if(isset($settings[$slug]['list_values']) && count($settings[$slug]['list_values'][$field->field])) {
                                                                foreach($settings[$slug]['list_values'][$field->field] as $val => $text) {
                                                                    $html.= '<option value="'.$val.'" '.($item->{$field->field} == $val || $field->is_plural && in_array($item->{$field->field}, array_keys($settings[$slug]['list_values'][$field->field])) ? 'selected':'').' data-value="'.$text.'">'.$text.'</option>';
                                                                }
                                                            }
                        $html.=                                 '</select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                <a class="mt-2 d-inline-block link show me-2 fs-14 js-add-relation-object" href="javascript:;" >
                                    + Добавить
                                </a>';
                        $rows[$item->id][$field->field] = $html;*/
                    } elseif($field->type == 'select_dropdown') {
                        $html = '<div class="position-relative">
                            <ul class="row g-2 ps-0 flex-nowrap">
                                <li class="col-lg-12">
                                    <div class="position-relative">
                                        <select name="select" class="js-select" '.($field->is_plural ? 'multiple':'') .' >';
                        if(!$field->is_plural)
                            $html.= '<option value="" '.(!$item->{$field->field} ? 'selected':'').'>не выбрано</option>';
                        if(isset($settings[$slug]['list_values']) && count($settings[$slug]['list_values'][$field->field])) {
                            foreach($settings[$slug]['list_values'][$field->field] as $val => $text) {
                                $html.= '<option value="'.$val.'" '.($item->{$field->field} == $val || $field->is_plural && in_array($item->{$field->field}, array_keys($settings[$slug]['list_values'][$field->field])) ? 'selected':'').' data-value="'.$text.'">'.$text.'</option>';
                            }
                        }

                        $html.=         '</select>
                                    </div>
                                </li>
                            </ul>
                        </div>';
                        $rows[$item->id][$field->field] = $html;
                    } elseif($field->type == 'status') {
                        $status_select = '<div data-field="'.$field->field.'" class="form-group status-group position-relative '.$settings[$slug]['perms'][$field->field]['write'].'" data-id="'.$item->id.'">';
                        $text_value = '';
                        foreach($settings[$slug]['list_values'][$field->field] as $list_item) {
                            if($field->field == 'point_status' && $item->{$field->field} && $item->{$field->field} == $list_item->id) {
                                $text_value = $list_item->name;
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect '.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?'comment-cancel-tooltip':'').'"  data-title="'.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?($item->comment_cancel ?? ' '):' ').'" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                            } elseif($item->{$field->field} && $item->{$field->field} == $list_item->id) {
                                $text_value = $list_item->name;
                                if($list_item->file)
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url('.\Storage::disk()->url($list_item->file).') '.$list_item->color.'"></div>';
                                else
                                    $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"><div class="d-none">'.$item->{$field->field}.'</div></div>';
                            } elseif(!$item->{$field->field}) {
                                $text_value = $list_item->name;
                                if($list_item->file)
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url('.\Storage::disk()->url($list_item->file).') '.$list_item->color.'"></div>';
                                else
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                                break;
                            }
                        };
                        $status_select.= '<select name="'.$field->field.'" class="form-control form-control-status form-control-status-select field-status-select2 js-field-status '.($field->field == 'point_status' ? 'form-control-status-delivery':'').'">';
                        foreach($settings[$slug]['list_values'][$field->field] as $key => $list_item) {
                            if(!$list_item->is_hidden)
                                $status_select.= '<option data-file="'.\Storage::disk()->url($list_item->file).'" data-color="'.$list_item->color.'" value="'.$list_item->id.'"'.($item->{$field->field} == $list_item->id || (!$item->{$field->field} && !$key) ? ' selected="selected"' : '').'>'.$list_item->name.'</option>';
                        }
                        $status_select.= '</select><span class="js-select-text">'.$text_value.'</span></div>';
                        $rows[$item->id][$field->field] = '<div data-field="'.$field->field.'">'.$status_select.'</div>';
                    } else {
                        $rows[$item->id][$field->field] = '<textarea rows="1" class="form-control form-control_table" data-edit-field="'.$field_num.'">'.$item->{$field->field}.'</textarea>';
                    }
                }
            }
            $objects[$item->id] = $rows[$item->id];
        }

        return response()->json($objects);
    }

    public function show_list($slug, Request $request)
    {
        $tenant = tenant('id');
        $settings = get_settings();
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();
        //dd($settings);
        $visible_fields = \App\Models\Field::getVisibleFields($slug);
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $field) {
            if($field->type == 'status')
                $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            $field_colors[$field->field] = $field->label_color ?? '';
            $perms['read'][$field->field] = (!optional($request->user())->canRead($field->field, $slug) ? 'disabled':'');
            $perms['write'][$field->field] = (!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'');
        }

        $items = \DB::table($slug)->whereIntegerInRaw('id', $request->ids)->orderBy('id', 'desc')->get();
        $rows = array();
        foreach ($items as $key => $item) {
            $rows[$item->id] = array();
            foreach($model_fields as $field_num => $field) {
                if(!array_key_exists($field->field, $rows[$item->id])) {
                    $value = $item->{$field->field};
                    if($field->field == 'products') {
                        $value = ValueHelper::prepareProducts($item->{$field->field});
                        $rows[$item->id][$field->field] = '<div class="js-editable '.(!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'').'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="orders" title="'.strip_tags($value).'" style="'.($field->label_color ? 'color:'.$field->label_color : '').'">'.$value.'</div>';
                    } elseif(!isset($settings[$slug]['list_values'][$field->field])) {

                        $rows[$item->id][$field->field] = '<div class="js-editable '.$settings[$slug]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'" data-value="'.$item->{$field->field}.'" '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').'>'.$value.'</div>';
                    } elseif(isset($settings[$slug]['list_values'][$field->field]) && $field->type != 'status') {
                        if($field->type == 'select_dropdown' && $field->is_plural && is_array(json_decode($value, true)))
                            $value = implode(', ', json_decode($value, true));
                        $rows[$item->id][$field->field] = '<div class="js-editable position-relative" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'" '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').'>'.$value.'</div>';;
                        foreach($settings[$slug]['list_values'][$field->field] as $list_value => $list_item) {
                            if($list_value == $value) {
                                $rows[$item->id][$field->field] = '<div class="js-editable '.$settings[$slug]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'"  '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').' data-value="'.$item->{$field->field}.'">'.$list_item.'</div>';
                            }
                        };
                    } else {
                        $status_select = '<div data-field="'.$field->field.'" class="form-group status-group disabled" data-id="'.$item->id.'" data-type="'.$field->type.'">';
                        $text_value = '';
                        $exist = false;
                        $i = 0;
                        $first_item = null;
                        foreach($settings[$slug]['list_values'][$field->field] as $list_item) {
                            if(!$i) {
                                $first_item = $list_item;
                            }
                            if($field->field == 'point_status' && $item->{$field->field} && $item->{$field->field} == $list_item->id) {
                                $text_value = $list_item->name;
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect '.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?'comment-cancel-tooltip':'').'"  data-title="'.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?($item->comment_cancel ?? ' '):' ').'" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                                $exist = true;
                            } elseif($item->{$field->field} && $item->{$field->field} == $list_item->id) {
                                $text_value = $list_item->name;
                                if($list_item->file)
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$list_item->file.') '.$list_item->color.'"></div>';
                                else
                                    $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"><div class="d-none">'.$item->{$field->field}.'</div></div>';
                                $exist = true;
                            } elseif(!$item->{$field->field}) {
                                $text_value = $list_item->name;
                                if($list_item->file)
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$list_item->file.') '.$list_item->color.'"></div>';
                                else
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                                $exist = true;
                                break;
                            }
                            $i++;
                        };
                        if(!$exist && isset($settings[$slug]['list_values'][$field->field])) {
                            if($first_item && $first_item->file)
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$first_item->file.') '.$first_item->color.'"></div>';
                            else
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$first_item->color.'"></div>';
                        }
                        // $status_select.= '<select name="'.$field->field.'" class="form-control form-control-status form-control-status-select field-status-select2 js-field-status '.($field->field == 'point_status' ? 'form-control-status-delivery':'').'">';
                        // foreach($settings[$slug]['list_values'][$field->field] as $key => $list_item) {
                        //     if(!$list_item->is_hidden)
                        //         $status_select.= '<option data-file="/storage/tenant'.$tenant.'/app/'.$list_item->file.'" data-color="'.$list_item->color.'" value="'.$list_item->id.'"'.($item->{$field->field} == $list_item->id || (!$item->{$field->field} && !$key) ? ' selected="selected"' : '').'>'.$list_item->name.'</option>';
                        // }
                        $status_select.= '<span class="js-select-text">'.$text_value.'</span></div>';
                        $rows[$item->id][$field->field] = $status_select;
                    }
                }
            }
            $objects[$item->id] = $rows[$item->id];
        }

        return response()->json($objects);
    }

    public function batch($slug, Request $request)
    {
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $s = get_settings();
        $model_fields = $s[$slug]['fields'];//$entity_class::getFields();

        $rows = json_decode($request->rows, true);
        $keys = array();
        info('rows');
        info($rows);
        foreach($rows as $id => $row) {
            $keys = array_keys($row);
            foreach($rows[$id] as $field_name => $value) {
                if(!$value && $slug == 'orders' && $field_name == 'store_name' || !$value && $slug != 'orders' && $field_name == 'name')
                    continue;
                if(!$value) {
                    $rows[$id][$field_name] = null;
                } else {
                    foreach($model_fields as $field) {
                        if($field->field == $field_name && $field->type == 'date')
                            $rows[$id][$field_name] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');//$value = strtotime($value);

                    }
                }
            }
            

            foreach($model_fields as $field) {
                if($request->{$field->field}) {
                    $history_text = $field->display_name.': '.$current->{$field->field}.' -> '.$request->{$field->field};
                    if($history_text) {
                        if(isset(\Auth::user()->id))
                            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
                        else
                            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => 0, 'text' => $history_text]);
                        $history->save();
                    }
                }
            }
        }
        info('rows2');
        info($rows);
        info('keys');
        info($keys);
        //dd($rows);
        //if(!$request->date) {
            //$request->date = strtotime($request->date);
        //}
        //die();
        foreach($rows as $k => $row) {
            $id = $row['id'];
            unset($rows[$k]['id']);
            \DB::table($slug)->where($id)->update($row);
        }
        //\DB::table($slug)->upsert($rows, 'id', $keys);
        //die();
        //echo $k;
        

        
        
        //$current->update($request->all());
    }

    public function delete($slug, Request $request)
    {
        $ids = json_decode($request->ids, true);
        \DB::table($slug)->whereIn('id', $ids)->delete();

        
    }

    public function update($slug, $id, Request $request)
    {
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $current = $entity_class::find($id);
        $model_fields = $entity_class::getFields();

        info('update');
        info($request->all());
        if($request->relation_id) {
            $field = \DB::table('data_rows')->where('id', $request->field_id)->first();
            if($field->is_plural) {
                $values = json_decode($current->{$field->field}, true);
                info('is_iterable?');
                if(is_iterable($values)) {
                    info('yes');
                    info($request->relation_id);
                    info($values);
                    if(array_search($request->relation_id, $values)) {
                        info('found');
                        info(array_search($request->relation_id, $values));
                        $history_text = 'Удалена связь с '. $field->display_name.': '.$request->relation_id;
                        if($history_text) {
                            if(isset(\Auth::user()->id))
                                $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
                            else
                                $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => 0, 'text' => $history_text]);
                            $history->save();
                        }
                        unset($values[array_search($request->relation_id, $values)]);
                        info($values);
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
            foreach($request->all() as $field_name => $value) {
                info('field_name '.$field_name);
                if(!$value && $slug == 'orders' && $field_name == 'store_name' || !$value && $slug != 'orders' && $field_name == 'name')
                    continue;
                if(!$value) {
                    $data[$field_name] = null;
                } else {
                    foreach($model_fields as $field) {
                        if($field->field == $field_name && $field->type == 'date') {
                            info($field_name.' '.$value);
                            $data[$field_name] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
                            info($data[$field_name]);
                        } elseif($field->type != 'date') {
                            info($field_name.' '.$value);
                            $data[$field_name] = $value;
                        }
                    }
                }
            }
            foreach($model_fields as $field) {
                if($request->{$field->field}) {
                    $history_text = $field->display_name.': '.$current->{$field->field}.' -> '.$request->{$field->field};
                    if($history_text) {
                        if(isset(\Auth::user()->id))
                            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => \Auth::user()->id, 'text' => $history_text]);
                        else
                            $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $id, 'user_id' => 0, 'text' => $history_text]);
                        $history->save();
                    }
                }
            }
            info('data');
            info($data);
            $current->update($data);
        }
        
    }

    public function destroy($slug, $id, Request $request)
    {
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $current = $entity_class::find($id);

        $current->delete();
    }

    public function restore($slug, Request $request, $id)
    {
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $item = $entity_class::withTrashed()->find($id);
        $item->restore();
    }

    public function edit_section($slug, $id, $section_id) {
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $current = $entity_class::find($id);

        $fields = \App\Models\Field::getVisibleFields($slug);
        $hidden_fields = \App\Models\Field::getHiddenFields($slug);
        // $sections_1 = \App\Models\FieldSection::get($slug, 1);
        // $sections_2 = \App\Models\FieldSection::get($slug, 2);
        
        $section = \App\Models\FieldSection::find($section_id);

        return view('objects.edit_section', compact('current', 'fields', 'hidden_fields', 'section', 'slug'));
    }

    public function create_relation($parent_slug, $id, $slug, Request $request)
    {
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;

        $object = new $entity_class;
        $object->save();

        if($slug == 'orders')
            $object->store_name = $entity->display_name_singular.' #'.$object->id;
        else
            $object->name = $entity->display_name_singular.' #'.$object->id;
        $object->save();


        $relation_parent = \Modules\Journal\Entities\EntityRelation::where('entity_id', $id)->first();
        if(!$relation_parent) {
            $parent_entity = \DB::table('data_types')->where('slug', $parent_slug)->first();
            $parent_entity_class = $parent_entity->model_name;
            $parent = $parent_entity_class::find($id);
            $relation_parent = new \Modules\Journal\Entities\EntityRelation;
            $relation_parent->name = $parent_entity->display_name_singular;
            if($parent_slug == 'orders')
                $relation_parent->entity_name = $parent->store_name;
            else
                $relation_parent->entity_name = $parent->name;
            $relation_parent->entity = $parent_slug;
            $relation_parent->entity_id = $parent->id;
            $relation_parent->user_id = $parent->user_id;
            $relation_parent->save();
        }

        $relation = new \Modules\Journal\Entities\EntityRelation;
        $relation->name = $entity->display_name_singular;
        if($slug == 'orders')
            $relation->entity_name = $object->store_name;
        else
            $relation->entity_name = $object->name;
        $relation->entity = $slug;
        $relation->entity_id = $object->id;
        $relation->parent_id = $relation_parent->id;
        $relation->user_id = $object->user_id;
        $relation->save();

        \Modules\Journal\Entities\EntityRelation::fixTree();
    }

}