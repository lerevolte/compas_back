<?php

namespace Modules\Products\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Products\Entities\Category;
use Modules\Products\Entities\Product;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $categories = Category::where('category_id', 0)->get();

        $slug = 'products';
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
        $settings = get_settings();
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

        $items = $entity_class::orderBy('id', 'desc')->get();

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
                    if($field->field == 'name' && $slug != 'orders') {
                        $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['name'] ? 'color:'.$settings[$slug]['colors']['name'] : '').'">'.($item->name ? $item->name : '').'</div></div>';
                        $data[$field->field] = $name_td;
                    } elseif($field->field == 'store_name') {
                        if(isset($item->replic_num) && $item->replic_num && $item->replic_num_split) {
                            $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'" data-storename="'.$item->store_name.'" data-supply="'.$item->is_supply.'" data-store="'.$item->is_store.'" data-link="'.$item->link.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['store_name'] ? 'color:'.$settings[$slug]['colors']['store_name'] : '').'">'.'<span class="replic-num replic-status-4" >'.$item->replic_num.'</span>-<span>'.$item->replic_num_split.'</span>-'.($item->store_name ? $item->store_name : '').'</div></div>';
                            
                        } elseif(isset($item->replic_num) && $item->replic_num) {
                            $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'" data-storename="'.$item->store_name.'" data-supply="'.$item->is_supply.'" data-store="'.$item->is_store.'" data-link="'.$item->link.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['store_name'] ? 'color:'.$settings[$slug]['colors']['store_name'] : '').'">'.'<span class="replic-num replic-status-4">'.$item->replic_num.'</span>-'.($item->store_name ? $item->store_name : '').'</div></div>';
                        } elseif(isset($item->replic_num) && $item->replic_num_split) {
                            $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'" data-storename="'.$item->store_name.'" data-supply="'.$item->is_supply.'" data-store="'.$item->is_store.'" data-link="'.$item->link.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['store_name'] ? 'color:'.$settings[$slug]['colors']['store_name'] : '').'">'.'<span class="replic-num">'.$item->replic_num_split.'</span>-'.($item->store_name ? $item->store_name : '').'</div></div>';
                        } else {
                            $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'" data-storename="'.$item->store_name.'" data-supply="'.$item->is_supply.'" data-store="'.$item->is_store.'" data-link="'.$item->link.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['store_name'] ? 'color:'.$settings[$slug]['colors']['store_name'] : '').'">'.($item->store_name ? $item->store_name : '').'</div></div>';
                        }
                        $data[$field->field] = $name_td;
                    } elseif($field->field == 'products') {
                        $value = ValueHelper::prepareProducts($item->{$field->field});
                        $data[$field->field] = '<div class="js-editable '.(!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'').'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="orders" title="'.strip_tags($value).'" style="'.($field->label_color ? 'color:'.$field->label_color : '').'">'.$value.'</div>';
                    } elseif(!isset($settings[$slug]['list_values'][$field->field])) {
                        $data[$field->field] = '<div class="js-editable '.$settings[$slug]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'"  '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').'>'.$value.'</div>';
                    } elseif(isset($settings[$slug]['list_values'][$field->field]) && $field->type != 'status') {
                        $data[$field->field] = '';
                        foreach($settings[$slug]['list_values'][$field->field] as $list_value => $list_item) {
                            if($list_value == $value) {
                                $data[$field->field] = '<div class="js-editable '.$settings[$slug]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'"  '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').'>'.$list_item.'</div>';
                            }
                        };
                    } else {
                        $status_select = '<div class="form-group status-group '.$settings[$slug]['perms'][$field->field]['write'].'" data-id="'.$item->id.'">';
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
                            $status_select.= '<option data-file="'.\Storage::disk()->url($list_item->file).'" data-color="'.$list_item->color.'" value="'.$list_item->id.'"'.($item->{$field->field} == $list_item->id || (!$item->{$field->field} && !$key) ? ' selected="selected"' : '').'>'.$list_item->name.'</option>';
                        }
                        $status_select.= '</select><span class="js-select-text">'.$text_value.'</span></div>';
                        $data[$field->field] = '<div data-field="'.$field->field.'">'.$status_select.'</div>';
                    }
                }
            }
            $objects[] = $data;
        }

        $model = $slug;

        return view('products::categories.index', compact('user', 'objects', 'active_filter', 'filters', 'model_fields', 'visible_fields', 'date', 'table_settings', 'entity', 'settings', 'model', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('products::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $data = Category::create($request->all());

        return response()->json($data);
    }

    /**
     * Show the specified resource.
     * @return Renderable
     */
    public function show(Request $request, Category $category)
    {   
        $categories = Category::where('category_id', 0)->get();

        $parents = array();

        $parent = Category::find($category->category_id);

        while($parent) {
            $parents[] = $parent->id;
            $parent = Category::find($parent->category_id);
        }

        $slug = 'products';
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
        $settings = get_settings();
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

        $items = $entity_class::orderBy('id', 'desc')->where('category_id', $category->id)->get();

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
                    if($field->field == 'name' && $slug != 'orders') {
                        $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['name'] ? 'color:'.$settings[$slug]['colors']['name'] : '').'">'.($item->name ? $item->name : '').'</div></div>';
                        $data[$field->field] = $name_td;
                    } elseif($field->field == 'store_name') {
                        if(isset($item->replic_num) && $item->replic_num && $item->replic_num_split) {
                            $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'" data-storename="'.$item->store_name.'" data-supply="'.$item->is_supply.'" data-store="'.$item->is_store.'" data-link="'.$item->link.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['store_name'] ? 'color:'.$settings[$slug]['colors']['store_name'] : '').'">'.'<span class="replic-num replic-status-4" >'.$item->replic_num.'</span>-<span>'.$item->replic_num_split.'</span>-'.($item->store_name ? $item->store_name : '').'</div></div>';
                            
                        } elseif(isset($item->replic_num) && $item->replic_num) {
                            $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'" data-storename="'.$item->store_name.'" data-supply="'.$item->is_supply.'" data-store="'.$item->is_store.'" data-link="'.$item->link.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['store_name'] ? 'color:'.$settings[$slug]['colors']['store_name'] : '').'">'.'<span class="replic-num replic-status-4">'.$item->replic_num.'</span>-'.($item->store_name ? $item->store_name : '').'</div></div>';
                        } elseif(isset($item->replic_num) && $item->replic_num_split) {
                            $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'" data-storename="'.$item->store_name.'" data-supply="'.$item->is_supply.'" data-store="'.$item->is_store.'" data-link="'.$item->link.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['store_name'] ? 'color:'.$settings[$slug]['colors']['store_name'] : '').'">'.'<span class="replic-num">'.$item->replic_num_split.'</span>-'.($item->store_name ? $item->store_name : '').'</div></div>';
                        } else {
                            $name_td = '<div class="disabled is_store show-empty" data-field="store_name" data-id="'.$item->id.'" data-storename="'.$item->store_name.'" data-supply="'.$item->is_supply.'" data-store="'.$item->is_store.'" data-link="'.$item->link.'"><div rel="ajaxpanel" data-loadtype="iframe" href="/objects/'.$slug.'/show/'.$item->id.'?ajax=y" style="'.($settings[$slug]['colors']['store_name'] ? 'color:'.$settings[$slug]['colors']['store_name'] : '').'">'.($item->store_name ? $item->store_name : '').'</div></div>';
                        }
                        $data[$field->field] = $name_td;
                    } elseif($field->field == 'products') {
                        $value = ValueHelper::prepareProducts($item->{$field->field});
                        $data[$field->field] = '<div class="js-editable '.(!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'').'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="orders" title="'.strip_tags($value).'" style="'.($field->label_color ? 'color:'.$field->label_color : '').'">'.$value.'</div>';
                    } elseif(!isset($settings[$slug]['list_values'][$field->field])) {
                        $data[$field->field] = '<div class="js-editable '.$settings[$slug]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'"  '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').'>'.$value.'</div>';
                    } else {
                        $status_select = '<div class="form-group status-group '.$settings[$slug]['perms'][$field->field]['write'].'" data-id="'.$item->id.'">';
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
                            $status_select.= '<option data-file="'.\Storage::disk()->url($list_item->file).'" data-color="'.$list_item->color.'" value="'.$list_item->id.'"'.($item->{$field->field} == $list_item->id || (!$item->{$field->field} && !$key) ? ' selected="selected"' : '').'>'.$list_item->name.'</option>';
                        }
                        $status_select.= '</select><span class="js-select-text">'.$text_value.'</span></div>';
                        $data[$field->field] = '<div data-field="'.$field->field.'">'.$status_select.'</div>';
                    }
                }
            }
            $objects[] = $data;
        }

        $model = $slug;

        return view('products::categories.show', compact('user', 'objects', 'active_filter', 'filters', 'model_fields', 'visible_fields', 'date', 'table_settings', 'entity', 'settings', 'model', 'categories', 'category', 'parents'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('products::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
