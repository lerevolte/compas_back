<?
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;

class FieldController extends Controller
{
    public function field(Request $request) {

        $name = $request->name;
        $value = $request->value;
        $options = array();
        $names = array();
        $labels = array();
        $model = $request->model;
        $id = $request->entity_id;
        $db_value = '';
        $entity_id = $request->entity_id;
        
        if($request->model == 'supplies')
            $model = 'orders';
        $row_type = \DB::table('data_types')->where('name', $model)->first();

        $field_data = null;
        if(!strstr($name, ',')) {
            $field_data = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'field' => $name])->first();

            if(isset($_GET['n'])) {
                echo $name;
                die();
            }
            if($field_data) {
                $type = $field_data->type;
                $field_details = json_decode($field_data->details, true);
            } else {
                $type = 'text';
            }
            
        } else {
            $type = 'text';
        };
        
        switch ($type) {
            case 'multiple_checkbox':
                $options = array();
                if(isset($field_details['table'])) {
                    $options_o = \DB::table($field_details['table'])->get();
                    foreach($options_o as $option) {
                        $options[$option->id] = $option->display_name ? $option->display_name : $option->name;
                    }
                } else
                    $options = $field_details['options'];
                $view = 'fields.show.multiple_checkbox';
                break;
            case 'select_dropdown':
                $options = array();
                if(isset($field_details['table'])) {
                    $options_o = array();
                    if($field_details['table'] == 'car_marks') {
                        if(isset($request->category) && $request->category) {
                            $koefs = \App\Models\CarKoef::where('category_id', $request->category)->whereNotNull('mark_id')->whereNull('model_id')->pluck('mark_id')->toArray();
                            $options_o = \DB::table($field_details['table'])->whereIn('id', $koefs)->get();
                        }
                    } elseif($field_details['table'] == 'car_models') {
                        if(isset($request->category) && $request->category && isset($request->mark) && $request->mark) {
                            $koefs = \App\Models\CarKoef::where('category_id', $request->category)->where('mark_id',$request->mark)->whereNotNull('model_id')->pluck('model_id')->toArray();
                            $options_o = \DB::table($field_details['table'])->whereIn('id', $koefs)->get();
                        }
                        
                    } else {
                        $options_o = \DB::table($field_details['table'])->whereNull('deleted_at')->get();
                    }
                    if(count($options_o))
                        foreach($options_o as $option) {
                            $options[$option->id] = (isset($option->display_name) ? $option->display_name : $option->name).(isset($option->last_name) ? ' '.$option->last_name : '');
                        }
                } else
                    $options = $field_details['options'];
                $view = 'fields.show.select_dropdown';
                break;
            case 'user':
                $options = array();
                $options_o = \DB::table('users')->get();
                foreach($options_o as $option) {
                    $options[$option->id] = $option->name;
                }
                $view = 'fields.show.select_dropdown';
                break;
            case 'radio_btn':
                $options = $field_details['options'];
                $view = 'fields.show.radio_btn';
                break;
            case 'color':
                $view = 'fields.show.color';
                break;
            case 'file':
                // if(is_string($value))
                //     $value = '['.$value.']';
                
                
                $value = json_decode(json_encode($value),true);
                $db_value = json_encode($value);
                //dd($value);
                if(is_array($value))
                    $value = \App\Models\File::whereIn('id', $value)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$value).")"))->get();

                $view = 'fields.show.file';
                break;
            case 'image':
                $value = json_encode($value);
                //$value = json_decode(json_encode($value),true);
                //if(is_array($value))
                   // $value = \App\Models\File::whereIn('id', $value)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$value).")"))->get();
                $view = 'fields.show.image';
                break;
            case 'status':
                $status_select = '<div class="form-group status-group">';
                $fields_values = \App\Models\Field::getStatuses($field_data->id);
                $exist = false;
                $first = null;
                $i = 0;
                $color = '#000';
                foreach($fields_values as $list_item) {
                    if(!$list_item->is_hidden)
                        $i++;
                    if($i == 1) {
                        $first = $list_item;
                    }
                    if(!$list_item->is_hidden) {
                        if($list_item->file)
                            $status_select.= '<div class="point_status_rect" style="background: url('.Storage::disk()->url($list_item->file).') '.$list_item->color.'"></div>';
                        else
                            $status_select.= '<div class="point_status_rect" style="background: '.$list_item->color.'"></div>';
                        $exist = true;
                        $text_value = $list_item->name;
                        break;
                    }

                }
                if(!$exist) {
                    $text_value = $first->name;
                    if($first->file)
                        $status_select.= '<div class="point_status_rect" style="background: url('.Storage::disk()->url($first->file).') '.$list_item->color.'"></div>';
                    else
                        $status_select.= '<div class="point_status_rect" style="background: '.$first->color.'"></div>';
                }
                $status_select.= '<select name="'.$field_data->field.'" class="form-control form-control-status form-control-status-select field-status-select2 js-field-status" data-field="'.$field_data->id.'" data-color="'.$color.'">';
                if($color != '')
                    $status_select.= '<option disabled selected value></option>';
                foreach($fields_values as $list_item) {
                    
                    if(!$list_item->is_hidden)
                        $status_select.= '<option data-file="'.Storage::disk()->url($list_item->file).'" data-color="'.$list_item->color.'" value="'.$list_item->id.'">'.$list_item->name.'</option>';
                }
                $status_select.= '</select></div>';
                return $status_select;
            default:
                $view = 'fields.show.text';
                if(strstr($name, ',')) {
                    $names = explode(',', $name);
                    $value = explode(',', $value);
                    foreach ($names as $key => $name) {
                        $field_data = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'field' => trim($name)])->first();
                        $field_details = json_decode($field_data->details, true);
                        if(isset($field_details['label']))
                            $labels[$name] = $field_details['label'];
                        else
                            $labels[$name] = $field_data->display_name;
                        
                    }
                    $view = 'fields.show.multipletext';
                }
                break;
        }
        return view($view, compact('id', 'name', 'value', 'options', 'names', 'labels', 'field_data', 'db_value', 'model', 'entity_id'));
    }

    public function store(Request $request) {
        if(!Auth::user()->isAdmin())
            return abort(403);
        $row_type = \DB::table('data_types')->where('name', $request->model)->first();
        $field = \Str::slug($request->name, '_');
        if (\DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'field' => $field])->exists())
            return 'error';
        
        $details = array();
        $type = $request->type;
        if($request->values) {
            foreach($request->values as $value) {
                $details['options'][$value] = $value;
            }
        };

        if($request->type == 'select_dropdown' && $request->is_plural) {
            $type = 'multiple_checkbox';
        }

        if (\DB::table('data_rows')->where('field', $field)->where('data_type_id', $row_type->id)->doesntExist()) {
            $max_sort = \DB::table('data_rows')->where('data_type_id', $row_type->id)->max('sort');
            $max_sort++;

            if($type == 'text_group') {
                
                \DB::table('data_rows')->insert([
                    'data_type_id' => $row_type->id,
                    'field' => $field,
                    'type' => $type,
                    'display_name' => $request->name,
                    'visible_always' => $request->visible_always,
                    'section_id' => $request->section_id ? $request->section_id : 0,
                    'submodel' => $request->submodel ? $request->submodel : 0,
                    'roles_read' => $request->has_roles_read ? $request->roles_read : '',
                    'roles_write' => $request->has_roles_write ? $request->roles_write : '',
                    'mobile_pages' => $request->show_in_mobile ? $request->mobile_pages : '',
                    'sort' => $max_sort
                ]);
                $field_group = \DB::table('data_rows')->where('field', $field)->first();

                foreach($request->values as $value) {

                    $field_slug = \Str::slug($value, '_').'_'.$field_group->id;
                    if (\DB::table('data_rows')->where('field', $field_slug)->doesntExist()) {
                        \DB::table('data_rows')->insert([
                            'data_type_id' => $row_type->id,
                            'field' => $field_slug,
                            'type' => 'text',
                            'display_name' => $value,
                            'display_parent_name' => $field_group->display_name,
                            'group_id' => $field_group->id,
                            'submodel' => $request->submodel ? $request->submodel : 0,
                        ]);
                        \Schema::table($request->model, function($table) use ($field_slug) {
                            $table->string($field_slug)->nullable();
                        });
                    }
                }
            } elseif($type == 'status') {
                
                $files = $request->file('files');
                $field_id = \DB::table('data_rows')->insertGetId([
                    'data_type_id' => $row_type->id,
                    'field' => $field,
                    'type' => $type,
                    'display_name' => $request->name,
                    'visible_always' => $request->visible_always ?? 0,
                    'details' => '',
                    'label_color' => $request->set_color ? $request->label_color : '',
                    'section_id' => $request->section_id ? $request->section_id : 0,
                    'measure' => $request->measure ? $request->measure : 0,
                    'button_name' => $request->button_name ? $request->button_name : 0,
                    'show_file_image' => $request->show_file_image ? $request->show_file_image : 0,
                    'submodel' => $request->submodel ? $request->submodel : 0,
                    'is_plural' => $request->is_plural ? $request->is_plural : 0,
                    'roles_read' => $request->has_roles_read ? $request->roles_read : '',
                    'roles_write' => $request->has_roles_write ? $request->roles_write : '',
                    'mobile_pages' => $request->show_in_mobile ? $request->mobile_pages : '',
                    'sort' => $max_sort
                ]);
                foreach($request->values as $key => $value) {
                    $path = '';
                    if($request->hasFile('files') && isset($files[$key]))
                    {
                        $path = $files[$key]->store('/public/field_icons');
                    }
                    \DB::table('field_values')->insert([
                        'field_id' => $field_id,
                        'color' => $request->colors[$key],
                        'file' => $path,
                        'name' => $value
                    ]);
                }
                

                \Schema::table($request->model, function($table) use ($field) {
                    $table->string($field)->nullable();
                });
            } else {
                \DB::table('data_rows')->insert([
                    'data_type_id' => $row_type->id,
                    'field' => $field,
                    'type' => $type,
                    'display_name' => $request->name,
                    'visible_always' => $request->visible_always ?? 0,
                    'details' => $request->values ? json_encode($details, true) : '',
                    'label_color' => $request->set_color ? $request->label_color : '',
                    'section_id' => $request->section_id ? $request->section_id : 0,
                    'measure' => $request->measure ? $request->measure : 0,
                    'button_name' => $request->button_name ? $request->button_name : 0,
                    'show_file_image' => $request->show_file_image ? $request->show_file_image : 0,
                    'submodel' => $request->submodel ? $request->submodel : 0,
                    'is_plural' => $request->is_plural ? $request->is_plural : 0,
                    'roles_read' => $request->has_roles_read ? $request->roles_read : '',
                    'roles_write' => $request->has_roles_write ? $request->roles_write : '',
                    'mobile_pages' => $request->show_in_mobile ? $request->mobile_pages : '',
                    'sort' => $max_sort
                ]);


                \Schema::table($request->model, function($table) use ($field) {
                    $table->string($field)->nullable();
                });
            }
            cache()->flush();
            
        } else {
            return false;
        }
        
        
    }

    public function add_color(Request $request) {
        $field_value_id = \DB::table('field_values')->insertGetId([
            'field_id' => $request->field_id,
            'color' => $request->color,
            'is_hidden' => 1
        ]);
        $field = \DB::table('data_rows')->where(['id' => $request->field_id])->first();

        \DB::table($request->model)->where(['id' => $request->id])->update([
            $field->field => $field_value_id
        ]);

        cache()->flush();
    }
    public function renderProperties($type, Request $request) {
        $is_address = $request->is_address;
        
        return view('fields.create.'.$type, compact('is_address'));
    }

    public function edit(Request $request) {
        $field = \DB::table('data_rows')->where(['id' => $request->field])->first();
        $field_details = array();
        
        $options = array();
        if($field->details)
            $field_details = json_decode($field->details, true);
        if(isset($field_details['options']))
            $options = $field_details['options'];
        if($field->type == 'status') {
            $options = \App\Models\Field::getStatuses($field->id);
        }
        if($field->type == 'text_group') {
            $options = \DB::table('data_rows')->where(['group_id' => $field->id])->orderBy('sort')->pluck('display_name', 'field')->toArray();
        }
        // if(isset($field_details['table']))
        //     $options = \DB::table($field_details['table'])->pluck('name')->toArray();
        $type = $field->type;
        if($type == 'multiple_checkbox')
            $type = 'select_dropdown';

        $roles = \App\Models\Role::get();

        return view('fields.edit.'.$type, compact('field', 'options', 'roles'));
    }

    public function update(Request $request) {
        info('STATUS');
        info($request->all());
        $files = $request->file('files');
        info($files);
        $field = \DB::table('data_rows')->where(['id' => $request->id])->first();

        $data = array();
        $data['display_name'] = $request->name;
        $data['visible_always'] = $request->visible_always;
        $data['show_file_image'] = $request->show_file_image ?? 0;
        if($request->rules)
            $data['rules'] = json_encode($request->rules, true);
        if($field->type == 'multiple_checkbox' && !$request->is_plural) {
            $data['type'] = 'select_dropdown';
        } elseif($field->type == 'select_dropdown' && $request->is_plural) {
            $data['type'] = 'multiple_checkbox';
        }

        if($request->set_color == 1)
            $data['label_color'] = $request->label_color;
        else
            $data['label_color'] = '';

        $details = array();

        if($request->values) {
            $options = array();
            if($field->details) {
                $field_details = json_decode($field->details, true);
                $options = $field_details['options'];
            }
            foreach($request->values as $k => $value) {
                if(in_array($value, $options))
                    $details['options'][array_search($value, $options)] = $value;
                else
                    $details['options'][$value] = $value;
            }
            
            $data['details'] = json_encode($details, true);
        };
        $data['is_plural'] = $request->is_plural ? $request->is_plural : 0;
        $data['roles_read'] = $request->has_roles_read ? $request->roles_read : '';
        $data['roles_write'] = $request->has_roles_write ? $request->roles_write : '';
        $data['mobile_pages'] = $request->show_in_mobile ? $request->mobile_pages : '';

        if($field->type == 'text_group') {
            $field_group = \DB::table('data_rows')->where('field', $field->field)->first();
            $fields_by_group = \DB::table('data_rows')->where('group_id', $field_group->id)->get();
            $fields_save = array();
            foreach($request->values as $k => $value) {
                $field_slug = $k;//\Str::slug($value, '_').'_'.$field_group->id;
                $fields_save[] = $field_slug;
                //echo $k.'<br>';
                if (\DB::table('data_rows')->where('field', $field_slug)->doesntExist()) {
                    \DB::table('data_rows')->insert([
                        'data_type_id' => $field_group->data_type_id,
                        'field' => $field_slug,
                        'type' => 'text',
                        'display_name' => $value,
                        'display_parent_name' => $field_group->display_name,
                        'group_id' => $field_group->id,
                        'submodel' => $field_group->submodel,
                        'sort' => $k
                    ]);
                    $row_type = \DB::table('data_types')->where('id', $field_group->data_type_id)->first();
                    \Schema::table($row_type->name, function($table) use ($field_slug) {
                        $table->string($field_slug)->nullable();
                    });
                } else {
                    \DB::table('data_rows')->where('field', $field_slug)->update([
                        'display_name' => $value,
                        'display_parent_name' => $data['display_name'],
                        'sort' => $k
                    ]);
                }
            }
            foreach($fields_by_group as $field_r) {
                if(!in_array($field_r->field, $fields_save))
                    \DB::table('data_rows')->where('field', $field_r->field)->delete();
            }
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
        } elseif($field->type == 'status') {
            $files = $request->file('files');
            
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
            $field_values = \DB::table('field_values')->where('field_id', $field->id)->pluck('color', 'id')->toArray();
            foreach($request->values as $key => $value) {
                if($field_values && array_key_exists($request->value_ids[$key], $field_values)) {
                    unset($field_values[$request->value_ids[$key]]);
                }

                $path = '';
                if($request->hasFile('files') && isset($files[$key]))
                {
                    $path = $files[$key]->store('/public/field_icons');
                }
                if(isset($request->value_ids[$key])) {
                    \DB::table('field_values')->where('id', $request->value_ids[$key])->update([
                        'field_id' => $field->id,
                        'color' => $request->colors[$key] ? $request->colors[$key] : '#000',
                        'name' => $value ? $value : '',
                        'sort' => $key
                    ]);
                    if($request->file_remove_values[$key] == 1)
                        \DB::table('field_values')->where('id', $request->value_ids[$key])->update([
                            'file' => ''
                        ]);
                    if($path)
                        \DB::table('field_values')->where('id', $request->value_ids[$key])->update([
                            'file' => $path
                        ]);
                } else {
                    \DB::table('field_values')->insert([
                        'field_id' => $field->id,
                        'color' => $request->colors[$key] ? $request->colors[$key] : '#000',
                        'file' => $path,
                        'name' => $value ? $value : '',
                        'sort' => $key
                    ]);
                }
            }
            info('FIELD VALUES');
            info($field_values);
            if(count($field_values)) {
                \DB::table('field_values')->whereIn('id', array_keys($field_values))->where('is_hidden', 0)->delete();
            }
        } else {
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
        }
        cache()->flush();

        return 1;
    }

    public function changeSort(Request $request)
    {
        \DB::table('data_rows')->where(['id' => $request->id])->update(['section_id' => $request->section]);
        $items = \DB::table('data_rows')->whereIn('id', $request->items)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->items).")"))->get();
        foreach ($items as $key => $item) {
            \DB::table('data_rows')->where(['id' => $item->id])->update([
                'sort' => $key
            ]);
        }

        cache()->flush();

        return json_encode(array('id' => $request->id, 'section_id' => $request->section));
    }

    public function hide(Request $request) {
        \DB::table('data_rows')->where(['id' => $id])->update([
            'hide' => 1
        ]);

        cache()->flush();
    }

    public function destroy(Request $request, int $id) {
        \DB::table('data_rows')->where(['id' => $request->id])->update([
            'is_remove' => 1
        ]);
        \DB::table('data_rows')->where(['group_id' => $request->id])->update([
            'is_remove' => 1
        ]);

        cache()->flush();
    }

    public function show(Request $request) {
        if(!Auth::user()->isAdmin())
            return abort(403);
        if($request->section)
            $section = \DB::table('field_sections')->where('id', $request->section)->first();
        $props = array(
            'hide' => 0
        );
        if(isset($request->visible_always))
            $props['visible_always'] = $request->visible_always;
        if ($section)
            $props['section_id'] = $section->id;
        $model = $request->model;
        if($request->model == 'supplies')
            $model = 'orders';
        $row_type = \DB::table('data_types')->where('name', $model)->first();
        $max_sort = \DB::table('data_rows')->where('data_type_id', $row_type->id)->max('sort');
        $max_sort++;
        $props['sort'] = $max_sort;
        \DB::table('data_rows')->where(['field' => $request->field, 'data_type_id' => $row_type->id])->update($props);

        cache()->flush();

        return response()->json($props);
    }

    public function set_compare($entity, $module, Request $request) {
        $comparison = \DB::table('comparison_fields')->where([
            'module' => $module,
            'entity' => $entity,
            'module_field' => $request->module_field,
        ])->first();

        if(!$comparison) {
            \DB::table('comparison_fields')->insert([
                'module' => $module,
                'entity' => $entity,
                'module_field' => $request->module_field,
                'entity_field' => $request->entity_field,
                'values_list' => $request->values_list
            ]);
        } else {
            \DB::table('comparison_fields')->where([
                'module' => $module,
                'entity' => $entity,
                'module_field' => $request->module_field
            ])->update([
                'entity_field' => $request->entity_field,
                'values_list' => $request->values_list
            ]);
        }
        
        return response()->json([
            'module' => $module,
            'entity' => $entity,
            'module_field' => $request->module_field,
            'entity_field' => $request->entity_field,
            'values_list' => $request->values_list
        ]);

    }

    public function get_compare($entity, $module, Request $request) {
        $comparisons = \DB::table('comparison_fields')->where([
            'module' => $module,
            'entity' => $entity,
        ])->get();
        
        return response()->json($comparisons);
    }
}
