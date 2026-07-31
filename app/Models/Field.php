<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Helpers\ValueHelper;


class Field extends Model
{
    protected $table = 'data_rows';

    public static function list($slug)
    {
        $settings = app('settings');

        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();

        $users = \App\Models\User::get(['id', 'name', 'last_name'])->keyBy('id')->toArray();
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        $data = array();
        $field_values = array();
        foreach($model_fields as $field) {
            if($field->type == 'status')
                $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
            if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && (!isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'])) {
                $data[$field->field] = array(
                    'id' => $field->id,
                    'title' => $field->title,//.' '.($field->required ? '*' : ''),
                    'type' => $field->type,
                    'read_only' => $field->only_read,
                    'can_read' => 1,
                    'can_edit' => $field->only_read || !$settings[$slug]['perms'][$field->field]['write'] && !\Auth::user()->is_admin ? 0 : 1,//!$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0,
                    'color' => $field_colors[$field->field],
                    'is_plural' => $field->is_plural,
                    'required' => $field->required
                );
                if(isset($settings['list_values'][$field->id])) {
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true);
                    } else {
                        if(isset($settings[$slug]['options'][$field->field]))
                            $field_values = $settings[$slug]['options'][$field->field];
                        else
                            $field_values = $settings['list_values'][$field->id];
                    }
                    $data[$field->field]['options'] = array_values($field_values);
                };
                if($field->type == 'relation') {
                    $data[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                }
            }
        }

        return $data;
    }

    public static function getAllFields(string $model)
    {
        $row_type = \DB::table('data_types')->where('name', $model)->first();
        $fields = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'is_remove' => 0]);

        $fields = $fields->get();

        return $fields;
    }

    public static function getVisibleFields(string $model)
    {
        $row_type = \DB::table('data_types')->where('name', $model)->first();
        $fields = \DB::table('data_rows')->where(['hide' => 0, 'visible_always' => 1, 'data_type_id' => $row_type->id, 'is_remove' => 0]);

        $fields = $fields->get();

        return $fields;
    }

    public static function getVisibleAlwaysFields(string $model)
    {
        $row_type = \DB::table('data_types')->where('name', $model)->first();
    	$fields = \DB::table('data_rows')->where(['hide' => 0, 'data_type_id' => $row_type->id, 'is_remove' => 0]);

        $fields = $fields->get();

    	return $fields;
    }

    public static function getHiddenFields(string $model)
    {
        $row_type = \DB::table('data_types')->where('name', $model)->first();
        $fields = \DB::table('data_rows')->where(['hide' => 1, 'data_type_id' => $row_type->id, 'is_remove' => 0])->whereNull('group_id');

        $fields = $fields->get();

        return $fields;
    }

    public static function getByGroup(int $id)
    {
        $fields = \DB::table('data_rows')->where(['group_id' => $id])->orderBy('sort')->get();

        return $fields;
    }



    public static function getFieldValues(string $field)
    {
        $field_data = \DB::table('data_rows')->where('field', $field)->first();

        $options = array();
        if($details = json_decode($field_data->details, true))
            $options = $details['options'] ?? array();

        return $options;
    }

    public static function getFieldValuesModel(string $field, int $model_id) 
    {
        $field_data = \DB::table('data_rows')->where('field', $field)->where('data_type_id', $model_id)->first();

        $options = array();
        if($details = json_decode($field_data->details, true))
            $options = $details['options'] ?? array();

        return $options;
    }

    public static function getStatuses(int $field_id) 
    {
        $field_values = \DB::table('field_values')->orderBy('is_hidden', 'asc')->orderBy('sort')->where('field_id', $field_id)->get()->toArray();


        return $field_values;
    }

    public static function getStatusesVisible(int $field_id) 
    {
        $cache_name = tenant('id').':field-'.$field_id.'-statuses';
        $field_values = cache()->getMemcached()->get($cache_name);
        if(!$field_values) {
            $field_values = \DB::table('field_values')->orderBy('is_hidden', 'asc')->orderBy('sort')->where('field_id', $field_id)->where('is_hidden', '!=', 1)->get();
            cache()->getMemcached()->add($cache_name, $field_values);
        }

        return $field_values;
    }

    public static function getDefaultStatusValue(int $field_id)
    {
        $first = collect(self::getStatusesVisible($field_id))->first();

        return $first->id ?? null;
    }

    public static function getStatusesWithHidden(int $field_id)
    {
        $field_values = \DB::table('field_values')->orderBy('is_hidden', 'asc')->orderBy('sort')->where('field_id', $field_id)->get();

        return $field_values;
    }

    public static function checkReadPermission(string $field_name, string $model) 
    {
        $result = 1;
        $user = \Auth::user();
        if(!$user) {
            $user = User::find(1);
        }
        $user_roles = $user->roles_all()->pluck('id')->toArray();
        $row_type = \DB::table('data_types')->where('name', $model)->first();
        $read_roles = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'field' => $field_name])->pluck('roles_read')->first();
        $read_roles = json_decode($read_roles);
        if($read_roles) {
            $intersect = array_intersect($read_roles, $user_roles);
            if(!count($intersect))
                $result = 0;
        }

        return $result;
    }

    public static function checkWritePermission(string $field_name, string $model) 
    {
        $result = 1;

        $user = \Auth::user();
        if(!$user) {
            $user = User::find(1);
        }
        $user_roles = $user->roles_all()->pluck('id')->toArray();
        $row_type = \DB::table('data_types')->where('name', $model)->first();
        $write_roles = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'field' => $field_name])->pluck('roles_write')->first();
        $write_roles = json_decode($write_roles);
        if($write_roles) {
            $intersect = array_intersect($write_roles, $user_roles);
            if(!count($intersect))
                $result = 0;
        }
        

        return $result;
    }

    public static function getValues(int $field_id, $limit = null) 
    {
        $field_values = array();

        $field = \DB::table('data_rows')->where('id', $field_id)->first();

        if($details = json_decode($field->details, true)) {
            if(isset($details['table'])) {
                $type = \DB::table('data_types')->where('slug', $details['table'])->first();
                if($limit) {
                    if($type)
                        $table_objects = $type->model_name::whereNull('deleted_at')->limit($limit)->get();
                    else
                        $table_objects = \DB::table($details['table'])->whereNull('deleted_at')->limit($limit)->get();
                } else {
                    if($type)
                        $table_objects = $type->model_name::whereNull('deleted_at')->get();
                    else
                        $table_objects = \DB::table($details['table'])->whereNull('deleted_at')->get();
                }
                foreach ($table_objects as $object) {
                    if(isset($object->title)) {
                        $field_values[$object->id] = array(
                            'value' => $object->id,
                            'label' => $object->title,
                            'color' => isset($object->color) ? $object->color : '',
                            'file' => isset($object->avatar) ? $object->avatar : '',
                        );
                    } elseif(isset($object->first_name)) {
                        $field_values[$object->id] = array(
                            'value' => $object->id,
                            'label' => $object->first_name.' '.$object->last_name,
                            'color' => isset($object->color) ? $object->color : '',
                            'file' => isset($object->avatar) ? $object->avatar : '',
                        );
                    } elseif(isset($object->name)) {
                        $field_values[$object->id] = array(
                            'value' => $object->id,
                            'label' => $object->name.(isset($object->last_name) ? ' '.$object->last_name:''),
                            'color' => isset($object->color) ? $object->getColor() : '',
                            'file' => isset($object->avatar) ? $object->avatar : '',
                        );
                    } else {
                        $field_values[$object->id] = array(
                            'value' => $object->id,
                            'label' => $object->name,
                            'color' => isset($object->color) ? $object->getColor() : '',
                            'file' => isset($object->avatar) ? $object->avatar : '',
                        );
                    }
                    // if(isset($object->last_name))
                    //     $field_values[$object->id] = $field_values[$object->id].' '.$object->last_name;//." ($object->id)";
                    // else
                    //     $field_values[$object->id] = $field_values[$object->id];//." ($object->id)";
                }
            } elseif(isset($details['options'])) {
                foreach($details['options'] as $k => $option) {
                    if(is_array($option) && isset($option['value']))
                        $field_values[$option['value']] = $option;
                    else
                        $field_values[$k] = $option;
                }
            }
        }

        return $field_values;
    }

    public static function getDataByObjectForList($field, $slug, $current)
    {
        $settings = app('settings');

        $value = $current->{$field->field};
        $data = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;


        $list_values = array();
        if($field->type == 'relation') {
            $list_values = isset($settings['list_values'][$field->id]) ? $settings['list_values'][$field->id] : array();
            if(\App\Models\Settings::lazy_table($settings, $field->id))
                $list_values += \App\Models\Settings::resolve_list_values($settings, $field->id, $data);
        }
        if($field->type == 'relation' && $field->is_plural) {
            $values = $data;
            $data = array();
            if(is_array($values)) {
                foreach($values as $val) {
                    if(isset($list_values[$val]))
                        $data[] = $list_values[$val]['value'];
                }
            }
        } elseif($field->type == 'relation' && isset($list_values[$value])) {
            $data = array($list_values[$value]['value']);
        } elseif($field->type == 'relation') {
            $data = null;
        };

        return $data;
    }

    public static function getDataByObject($field, $slug, $current)
    {
        $settings = app('settings');
        $entity = $settings['models'][$slug];

        $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $entity->id)->first()->toArray();

        if($field->type == 'status')
            $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
        
        $val = (string)$current->{$field->field};
        $field_value = ValueHelper::isJson($val) && $field->field != 'products' && is_array(json_decode($val, true)) ? json_decode($val, true) : $val;
        if($field->type == 'relation' && $field->is_plural && $field->relation_table) {
            $relation_table = $field->relation_table;

            $field_value = $current->{$relation_table}->pluck('id')->toArray();
        }

        $fields_data[$field->field] = $settings[$slug]['field_data'][$field->field];//Field::getData($field);
        $fields_data[$field->field]['can_read'] = $settings[$slug]['perms'][$field->field]['read'] || \Auth::user()->is_admin ? 1 : 0;
        $fields_data[$field->field]['can_edit'] = isset($data['deleted_at']) || $field->only_read || !$settings[$slug]['perms'][$field->field]['write'] && !\Auth::user()->is_admin ? 0 : 1;
        if($permissions['create_p'] == 'Y' && $field->field == 'user_id' && !\Auth::user()->is_admin) {
            $fields_data[$field->field]['can_edit'] = 0;
        }
        if($permissions['update_p'] == 'Y' && $current->user_id != \Auth::user()->id && !\Auth::user()->is_admin && $slug != 'users' || 
            $permissions['update_p'] == 'N' && !\Auth::user()->is_admin || $field->field == 'payment') {
                $fields_data[$field->field]['can_edit'] = 0;
        }

        //$fields_data[$field->field]['can_edit'] = $field->only_read ? 0 : 1;//!$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0;
        $fields_data[$field->field]['value'] = $field->field == 'password' ? '' : $field_value;
        $fields_data[$field->field]['used_in_modules'] = $settings[$slug]['used_in_modules'][$field->field];
        //$fields_data[$field->field]['comparison'] = $comparisons[$field->id] ?? null;
        //$fields_data[$field->field]['editableFields']['model'] = $slug;

        if($field->type == 'file' && !isset($field_value[0]) && $field_value) {
            $fields_data[$field->field]['value'] = array($field_value);
        } elseif($field->type == 'file' && !isset($field_value[0]) && !$field_value) {
            $fields_data[$field->field]['value'] = array();
        } elseif($field->type == 'file') {
            $fields_data[$field->field]['value'] = array();
            foreach($field_value as $fval) {
                if($fval)
                    $fields_data[$field->field]['value'][] = $fval;
            }
        }
        $list_values = array();
        if(isset($settings['list_values'][$field->id]))
            $list_values = $settings['list_values'][$field->id];
        if($field->type == 'relation' && \App\Models\Settings::lazy_table($settings, $field->id)) {
            $list_values += \App\Models\Settings::resolve_list_values($settings, $field->id, $field_value);
        }
        if($field->type == 'relation' && $field->is_plural) {
            $values = $field_value;
            $fields_data[$field->field]['value'] = array(
                'value' => array(),
                'localOptions' => array()
            );
            if(is_array($values)) {
                foreach($values as $val) {
                    if(isset($list_values[$val])) {
                        $fields_data[$field->field]['value']['value'][] = $list_values[$val]['value'];
                        $fields_data[$field->field]['value']['localOptions'][] = $list_values[$val];
                    }
                }
            }
        } elseif($field->type == 'relation' && isset($list_values[$field_value])) {
            $fields_data[$field->field]['value'] = array(
                'value' => array(),
                'localOptions' => array()
            );
            $fields_data[$field->field]['value']['localOptions'] = array($list_values[$field_value]);
            $fields_data[$field->field]['value']['value'] = array($list_values[$field_value]['value']);
            
        } elseif($field->type == 'relation') {
            $fields_data[$field->field]['value'] = array(
                'value' => array(),
                'localOptions' => array()
            );
        }

        // if($field->type == 'relation') {
        //     if($field->field == 'category_id' || $field->field == 'role_id')
        //         $fields_data[$field->field]['can_create'] = 0;
        //     else
        //         $fields_data[$field->field]['can_create'] = 1;
        // }


        if($field->type == 'relation' && $field->field != 'role_id') {

            $fields_data[$field->field]['can_create'] = 1;

            if($field->relation_table && isset($settings['models'][$field->relation_table]) && isset($permissions_all[$settings['models'][$field->relation_table]->id]['create_p']) && !\Auth::user()->is_admin)
                
                $fields_data[$field->field]['can_create'] = $permissions_all[$settings['models'][$field->relation_table]->id]['create_p'] == 'N' ? 0 : 1;
            if($field->relation_table && isset($settings['models'][$field->relation_table]) && isset($permissions_all[$settings['models'][$field->relation_table]->id]['update_p']) && !\Auth::user()->is_admin)
                $fields_data[$field->field]['can_edit'] = $permissions_all[$settings['models'][$field->relation_table]->id]['update_p'] == 'N' ? 0 : 1;

        }

        if($field->type == 'relation' && $field->relation_table && isset($restrictions_tariff['objects'][$field->relation_table]['count'])) {
            if($restrictions_tariff['objects'][$field->relation_table]['count'] <= \DB::table($field->relation_table)->whereNull('deleted_at')->count()) {
                $fields_data[$field->field]['can_create'] = 0;
            };
        }
        if(isset($settings['list_values'][$field->id])) {
            $values = array();
            if($field->type == 'relation') {
                $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true);
                if($field->is_plural && isset($fields_data[$field->field]['value']['value'])) {
                    foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                        if(isset($list_values[$field_val]))
                            $field_values[$field_val] = $list_values[$field_val];
                    }
                } elseif($current->{$field->field} && isset($list_values[$current->{$field->field}])) {
                    $field_values[$current->{$field->field}] = $list_values[$current->{$field->field}];
                } elseif($current->{$field->field}) {
                    $field_values[$current->{$field->field}] = null;
                }
            } else {
                if(isset($settings[$slug]['options'][$field->field]))
                    $field_values = $settings[$slug]['options'][$field->field];
                else
                    $field_values = $settings['list_values'][$field->id];
            }
            $fields_data[$field->field]['options'] = array_values($field_values);
            $fields_data[$field->field]['choosed'] = [];
            if(isset($settings[$slug]['fields'][$field->field]->choosed))
                $fields_data[$field->field]['choosed'] = $settings[$slug]['fields'][$field->field]->choosed;
            if($field->type == 'status') {
                $simple_options = array();
                foreach($field_values as $option) {
                    if(isset($settings[$slug]['options'][$field->field])) {
                        $simple_options[$option['value']] = $option['label']['text'];
                        $values[] = array(
                            'value' => $option['value'],
                            'label' => $option['label']['text'],
                            'sort' => $option['label']['sort']
                        );
                    } else {
                        $simple_options[$option->id] = $option->value;
                        $values[] = array(
                            'value' => $option->id,
                            'label' => $option->value,
                            'sort' => $option->sort
                        );
                    }
                    
                }
            } else {
                if($field->type == 'relation') {
                    if($field->is_plural && is_array($fields_data[$field->field]['value'])) {
                        foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                            if(isset($list_values[$field_val]))
                                $field_values[$field_val] = $list_values[$field_val]['value'];
                        }
                    } elseif($current->{$field->field} && isset($list_values[$current->{$field->field}])) {
                        $field_values[$current->{$field->field}] = $list_values[$current->{$field->field}];
                    } elseif($current->{$field->field}) {
                        $field_values[$current->{$field->field}] = null;
                    }
                } else {
                    $field_values = $settings['list_values'][$field->id];
                }
                foreach($field_values as $k => $option) {
                    $simple_options[$k] = $option;
                    $values[] = $option;
                    // $values[] = array(
                    //     'label' => [    
                    //         'id' => $k,
                    //         'sort' => is_array($option) && isset($option['sort']) ? $option['sort'] : $k,
                    //         'file' => $avatar,
                    //         'is_hidden' => 0,
                    //         'field_id' => $field->id,
                    //         'color' => isset($current->color) && !$current->color ? $current->getColor() : ($current->color ?? ''),
                    //         'text' => is_array($option) ? $option['label'] : $option
                    //     ],
                    //     'value' => $k
                    // );
                    // $values[] = array(
                    //     'value' => $k,
                    //     'label' => is_array($option) ? $option['label'] : $option,
                    //     'sort' => is_array($option) && isset($option['sort']) ? $option['sort'] : $k
                    // );
                }
            }
            // $fields_data[$field->field]['editableFields']['values'] = $values;
            // if($field->type == 'status')
            //     $fields_data[$field->field]['editableFields']['statuses'] = $settings['list_values'][$field->id];
        };
        if($field->type == 'relation' && $t = json_decode($field->details, true)) {
            if(isset($t['table']))
                $fields_data[$field->field]['related_table'] = $t['table'];
        }
        if($field->type == 'text_group') {
            $subfields = \App\Models\Field::getByGroup($field->id);
            $values = array();
            $fields_data[$field->field]['options'] = array();
            foreach($subfields as $subfield) {
                //$fields_data[$field->field]['options'][$subfield->id] = $subfield->title;
                $values[] = array(
                    'value' => $subfield->id,
                    'label' => $subfield->title,
                    'sort' => $subfield->sort
                );
            };
            $fields_data[$field->field]['options'] = $values;
            $fields_data[$field->field]['subfields'] = array();
            foreach($subfields as $subfield) {
                $fields_data[$field->field]['subfields'][] = array(
                    'id' => $subfield->id,
                    'title' => $subfield->title,
                    'key' => $subfield->field,
                    'type' => $subfield->type,
                    'visible_always' => $field->visible_always,
                    'is_hidden' => $field->hide,
                    'is_plural' => $field->is_plural,
                    'required' => $field->required,
                    'read_only' => $field->only_read,
                    'can_read' => $settings[$slug]['perms'][$field->field]['read'] || \Auth::user()->is_admin ? 1 : 0,
                    'can_edit' => $field->only_read || !$settings[$slug]['perms'][$field->field]['write'] && !\Auth::user()->is_admin ? 0 : 1,
                    //'can_edit' => $field->only_read ? 0 : 1,//!$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0,
                    'color' => $field->label_color ? $field->label_color : null,
                    'group_id' => $subfield->group_id,
                    'value' => $current->{$subfield->field},
                    'sort' => $subfield->sort
                );
            }
        }

        return $fields_data[$field->field];
    }

    public function getData()
    {
        $data = array(
            'id' => $this->id,
            'has_roles_read' => ($this->roles_read ? 1 : 0),
            'has_roles_write' => ($this->roles_write ? 1 : 0),
            'roles_read' => ($this->roles_read ? json_decode($this->roles_read, true) : array()),
            'roles_write' => ($this->roles_write ? json_decode($this->roles_write, true) : array()),
            'subfields' => ($this->subfields ? json_decode($this->subfields, true) : array()),
            'set_color' => $this->set_color,
            'title' => $this->title,
            'key' => $this->field,
            'type' => $this->type,
            'visible_always' => $this->visible_always,
            'default_value' => $this->default_value,
            'set_default' => $this->set_default,
            'is_hidden' => $this->hide,
            'is_plural' => $this->is_plural,
            'is_external_link' => $this->is_external_link,
            'required' => $this->required,
            'read_only' => $this->only_read,
            'permanent_required' => $this->permanent_required,
            'permanent_name' => $this->permanent_name,
            'is_permanent' => $this->is_permanent,
            'color' => $this->label_color ? $this->label_color : null,
            'value' => '',
            'unit' => $this->unit,
            'module' => $this->module,
            'mask' => $this->mask,
            'show_file_name' => $this->show_file_name,
            'button_name' => $this->button_name,
            'is_program' => $this->is_program,
            'dependency_fields' => isset($this->dependency_fields) ? json_decode($this->dependency_fields, true) : null

        );

        if ($this->details) {
            $details = json_decode($this->details, true);
            if (isset($details['can_create'])) {
                $data['can_create'] = $details['can_create'] ? 1 : 0;
            }
        }
        
        return $data;
    }

    public static function getHumanValue($field, $value)
    {
        $settings = app('settings');
        $res = '';
        $arr_res = array();
        $values_res = array();
        if($field->type == 'status' && isset($settings['list_values'][$field->id])) {
            $statuses = collect($settings['list_values'][$field->id]);
            $visible_statuses = $statuses->filter(function ($status) {
                    return !$status['label']['is_hidden'];
                })->pluck('value')->toArray();
            $status = $statuses->firstWhere('value', $value);


            if($status && $status['label']['is_hidden']) {
                $res = $status['label']['color'];
            } elseif($status) {
                $res = $status['label']['text'] ? $status['label']['text'] : ($status['label']['color'] ? $status['label']['color'] : '');
            }
            if(!$status) {
                $status = \DB::table('field_values')->where('id', $value)->first();
                if($status)
                    $res = $status->color;
            }
        } elseif(isset($settings['list_values'][$field->id])) {

            $list_values = $settings['list_values'][$field->id];
            foreach($list_values as $k => $item) {
                if(isset($item['label']))
                    $list_values[$item['value']] = $item['label'];
            }
            if($field->is_plural) {
                $res = array();
                $value = is_array($value) ? $value : json_decode($value, true);
                if($value && is_int($value))
                    $value = array($value);
                if($value) {
                    foreach($value as $k => $val) {
                        if(isset($list_values[$val])) {
                            $v = is_array($list_values[$val]) && array_key_exists('text', $list_values[$val]) ? $list_values[$val]['text'] : $list_values[$val];
                            $v = $v ? $v : $val;
                            if($field->type == 'relation' && $field->relation_table && $val) 
                                $v = "<span data-slug='$field->relation_table' data-id='$val'>$v</span>";
                            $res[$k] = $v;
                            $values_res[] = $val;
                        } elseif($field->relation_table) {
                            $new_ob = \DB::table($field->relation_table)->where('id', $val)->first();
                            if(!$new_ob) {
                                unset($res[$k]);
                            } else {
                                $v = "<span data-slug='$field->relation_table' data-id='$val'>$new_ob->name</span>";
                                $res[$k] = $v;
                                $values_res[] = $val;
                            }
                        }
                    }
                    $arr_res = $res;
                    

                    $res = implode(', ', $res);
                } else {
                    $res = '';
                }
            } else {
                if(is_array($value)) {
                    $value = array_pop($value);
                }

                $v = isset($list_values[$value]['text']) ? $list_values[$value]['text'] : '';
                $v = isset($list_values[$value]) && !is_array($list_values[$value]) ? $list_values[$value] : $v;
                $v = $v ? $v : $value;
                if($field->type == 'relation' && $field->relation_table && $value) {
                    $v = "<span data-slug='$field->relation_table' data-id='$value'>$v</span>";
                    $arr_res = array($v);
                    $values_res[] = $value;
                };
                $res = $v;
            }
        } else {
            if($value && !is_int($value) && !is_array($value) && is_array($list = json_decode($value, true)) || is_array($value) && $list = $value) {
                if($field->type == 'address' && is_array($list)) {
                    $res = $list['text'];
                } elseif($field->field== 'products' && is_array($list)) {
                    $res = array();
                    foreach($list as $product) {
                        $res[] = $product['name'].' <b>'.$product['count'].'шт.</b>';
                    }
                    $res = implode(', ', $res);
                } elseif($field->type == 'file' && is_array($list)) {
                    $file_values = array();
                    foreach($list as $v) {
                        if(isset($v['name']))
                            $file_values[] = $v['name'];
                    }
                    $res = implode(', ', $file_values);
                } elseif(is_array($list)) {
                    if(isset($list['value']) && isset($list['external_link'])) {
                        $res = implode(', ', array_values($list));
                    } elseif(isset($list['value'])) {
                        $res = $list['value'];
                    }
                }
            } elseif($field->type == 'date') {
                $res = $value ? date('d.m.Y', strtotime($value)) : '';
            } else {
                $res = $value;
                if(is_array($res))
                    $res = implode(',',$res);
            }
        }
        $object[$field->field] = $res;

        return array('res' => $res, 'arr_res' => $arr_res, 'values' => $values_res);
    }
}
