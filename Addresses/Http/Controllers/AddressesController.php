<?php

namespace Modules\Addresses\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use Modules\Addresses\Entities\Address;

class AddressesController extends \App\Http\Controllers\Controller
{
    public function store(Request $request)
    {
        $item = Address::create($request->all());

        return response()->json($item);
    }
    public function update(Request $request, Address $address)
    {
        $address->update($request->all());

        return response()->json($request->all());
    }

    public function copy(Request $request, Address $address)
    {
        $item = $address->replicate();
        $item->saveQuietly();

        return response()->json($item);   
    }

    public function destroy(Address $address)
    {
        $address->delete();
    }

    public function index($type = '')
    {
        $point_types = \App\Models\Field::getFieldValuesModel($point_field, 39);

        if($type) {
            $address = Address::where($point_field, $type)->orderBy('sort')->orderBy('id')->first();
            if($address)
                return redirect()->route('addresses.edit', ['id' => $address->id]);
        }   

        $addresses = \App\Models\Address::get();
        $title = 'Адреса'; 

        return view('addresses.index', compact('addresses', 'title', 'addresses', 'type'));
    }

    

    public function show($id, Request $request)
    {
        $point_types = \App\Models\Field::getFieldValuesModel($point_field, 39);
        $fields = \App\Models\Field::getByAccountVisibleFields($account_id, 'addresses');
        $hidden_fields = \App\Models\Field::getByAccountHiddenFields($account_id, 'addresses');
        $sections = \App\Models\FieldSection::getByAccount($account_id, 'addresses');

        $addresses = Address::orderBy('sort')->get();
        $title = 'Адреса';
        $current = Address::find($id);
        $type = $current->{$point_field};
        $addresses_empty_count = Address::whereRaw('address <> ""')->whereNull($point_field)->count();


        if(!$current)
            abort(404);

        if($request->all)
            $type = '';
        if($type) {
            $addresses = Address::where($point_field, $type)->orderBy('sort')->get();
            $title = $point_types[$type];
        } else {
            $addresses = Address::whereNull($point_field)->orderBy('sort')->get();
            if(!$addresses) {
                $type = $current->{$point_field};
                $addresses = Address::where($point_field, $type)->orderBy('sort')->get();
            }
            $title = 'Адреса';
        }
        
        return view('addresses.index', compact('addresses', 'current', 'title', 'type', 'fields', 'hidden_fields', 'sections', 'addresses_empty_count', 'point_types'));
    }

    public function get(Request $request)
    {
        $users = \App\Models\User::get(['id', 'name', 'last_name'])->keyBy('id')->toArray();
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );
        $model_fields = Address::getFields();
        foreach($model_fields as $field) {
            if($field->type == 'status')
                $fields_values[$field->field] = \App\Models\Field::getStatuses($field->id);
            $field_colors[$field->field] = $field->label_color ?? ''; 
            $perms['read'][$field->field] = (!optional($request->user())->canRead($field->field, 'orders') ? 'disabled':'');
            $perms['write'][$field->field] = (!optional($request->user())->canWrite($field->field, 'orders') ? 'disabled':'');
        }
        
        $perms['read']['logistic'] = Auth::user()->getPermission('read_logistic') != 'O' ? 'disabled' : '';
        $perms['write']['logistic'] = Auth::user()->getPermission('write_logistic') != 'O' ? 'disabled' : '';
        $perms['delete']['logistic'] = Auth::user()->getPermission('delete_logistic') != 'O' ? 'disabled' : '';
        if($request->type)
            $items = Address::where('point_type', $request->type)->orderBy('sort','asc')->get();
        else
            $items = Address::orderBy('sort','asc')->get();
        $orders['data'] = array();
        $point_type_field = Address::getFieldByCode('addresses', 'point_type');
      
        foreach ($items as $key => $item) {
            $latitude = $item->latitude;
            $longitude = $item->longitude;
            $order_data = array(    
                'dropping' => '<div class="not-selectable"><img src="/img/dropping.svg"></div>',
            );
            foreach($model_fields as $field) {
                if(!array_key_exists($field->field, $order_data)) {
                    $value = $item->{$field->field};
                    if($field->field == 'name') {
                        $order_data[$field->field] = '<div class="disabled is_store show-empty '.$perms['write'][$field->field].'" data-id="'.$item->id.'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="orders" title="'.strip_tags($value).'" style="'.($field->label_color ? 'color:'.$field->label_color : '').'"><div rel="ajaxpanel" data-loadtype="iframe" href="/addresses/edit/'.$item->id.'?ajax=y" style="'.($field_colors['name'] ? 'color:'.$field_colors['name'] : '').'">'.$value.'</div></div>';
                    } elseif($field->field == 'point_type') {
                        $order_data[$field->field] = '<div class="js-editable '.$perms['write'][$field->field].'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="" title="'.strip_tags($value).'">'.$item->getValue($point_type_field).'</div>';
                    } elseif($field->field == 'user_id') {
                        $order_data[$field->field] = '<div class="'.$perms['write']['user_id'].' js-editable" data-value="'.$item->user_id.'" data-field="user_id" data-type="select_dropdown" title="'.($item->user_id && isset($users[$item->user_id])? $users[$item->user_id]['name'].' '.$users[$item->user_id]['last_name'] : '').'" style="'.($field_colors['user_id'] ? 'color:'.$field_colors['user_id'] : '').'">'.($item->user_id && isset($users[$item->user_id]) ? $users[$item->user_id]['name'].' '.$users[$item->user_id]['last_name'] : '').'</div>';
                    } elseif(!isset($fields_values[$field->field]))
                        $order_data[$field->field] = '<div class="js-editable '.$perms['write'][$field->field].'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="orders" title="'.strip_tags($value).'" style="'.($field->label_color ? 'color:'.$field->label_color : '').'">'.$value.'</div>';
                    else {
                        $status_select = '<div class="form-group status-group '.($field->field == 'point_status' ? 'comment-cancel-form':'').' '.$perms['write'][$field->field].'" data-id="'.$item->id.'">';
                        foreach($fields_values[$field->field] as $list_item) {
                            if($item->{$field->field} && $item->{$field->field} == $list_item->id) {
                                if($list_item->file)
                                    $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: url('.\Storage::disk()->url($list_item->file).') '.$list_item->color.'"><div class="d-none">'.$item->{$field->field}.'</div></div>';
                                else
                                    $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"><div class="d-none">'.$item->{$field->field}.'</div></div>';
                            } elseif(!$item->{$field->field}) {
                                if($list_item->file)
                                    $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: url('.\Storage::disk()->url($list_item->file).') '.$list_item->color.'"><div class="d-none">'.$item->{$field->field}.'</div></div>';
                                else
                                    $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"><div class="d-none">'.$item->{$field->field}.'</div></div>';
                                break;
                            }
                        };
                        $status_select.= '<select name="'.$field->field.'" class="form-control form-control-status form-control-status-select field-status-select2 js-field-status '.($field->field == 'point_status' ? 'form-control-status-delivery':'').'">';
                        foreach($fields_values[$field->field] as $key => $list_item) {
                            $status_select.= '<option data-file="'.\Storage::disk()->url($list_item->file).'" data-color="'.$list_item->color.'" value="'.$list_item->id.'"'.($item->{$field->field} == $list_item->id || (!$item->{$field->field} && !$key) ? ' selected="selected"' : '').'>'.$list_item->name.'</option>';
                        }
                        $status_select.= '</select></div>';
                        $order_data[$field->field] = '<div data-field="'.$field->field.'">'.$status_select.'</div>';
                    }
                }
            }
            foreach($order_data as $field => $content) {
                if(isset($perms['read'][$field]) && $perms['read'][$field] == 'disabled')
                    unset($order_data[$field]);
            }

            $orders['data'][] = $order_data;
        }
        return response()->json($orders);
    }

    public function list(Request $request)
    {
        if($request->type) {
            $point_field = \App\Models\Account::find(request()->user()->account_id)->getPointField();
            $point_types = \App\Models\Field::getFieldValues($point_field);
            foreach($point_types as $val => $type) {
                if($val == $request->type)
                    $items = Address::where($point_field, $val);
            }
            $items = $items->get();
        } else {
            $items = Address::get();
        }
        

        return response()->json($items);
    }

    public function changeSort(Request $request)
    {
        $items = Address::whereIn('id', $request->items)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->items).")"))->get();
        foreach ($items as $key => $item) {
            $item->sort = $key;
            $item->save();
        }
    }

    public function edit_section($model, $id, $section_id) {
        $account_id = Auth::user()->account_id;
        $current = \App\Models\Address::find($id);
        $fields = \App\Models\Field::getByAccountVisibleFields($account_id, 'addresses');
        $hidden_fields = \App\Models\Field::getByAccountHiddenFields($account_id, 'addresses');
        
        $sections = \App\Models\FieldSection::getByAccount($account_id, 'addresses');
        
        $section = \App\Models\FieldSection::find($section_id);

        return view('addresses.edit_section', compact('current', 'fields', 'hidden_fields', 'section', 'model'));
    }
    
}