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
        $settings = get_settings();

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
            $perms['read'][$field->field] = '';//(!optional($request->user())->canRead($field->field, $slug) ? 'disabled':'');
            $perms['write'][$field->field] = '';//(!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'');
            if(!array_key_exists($field->field, $data) && $field->type != 'text_group') {
                $data[$field->field] = array(
                    'id' => $field->id,
                    'title' => $field->display_name,//.' '.($field->required ? '*' : ''),
                    'type' => $field->type,
                    'read_only' => $field->only_read,
                    'can_edit' => !$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0,
                    'color' => $field_colors[$field->field],
                    'is_plural' => $field->is_plural,
                    'required' => $field->required
                );
                if(isset($settings[$slug]['list_values'][$field->field])) {
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings[$slug]['list_values'][$field->field], 0, 19, true);
                    } else {
                        if(isset($settings[$slug]['options'][$field->field]))
                            $field_values = $settings[$slug]['options'][$field->field];
                        else
                            $field_values = $settings[$slug]['list_values'][$field->field];
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
                    if(isset($object->display_name)) {
                        $field_values[$object->id] = array(
                            'value' => $object->id,
                            'label' => $object->display_name,
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
                        info($details['table'].' '.$object->id);
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
        $settings = get_settings();

        $value = $current->{$field->field};
        $data = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;


        if($field->type == 'relation' && $field->is_plural) {
            $values = $data;
            $data = array();
            if(is_array($values)) {
                foreach($values as $val) {
                    if(isset($settings[$slug]['list_values'][$field->field][$val]))
                        $data[] = $settings[$slug]['list_values'][$field->field][$val]['value'];
                }
            }
        } elseif($field->type == 'relation' && isset($settings[$slug]['list_values'][$field->field][$value])) {
            $data = array($settings[$slug]['list_values'][$field->field][$value]['value']);
        } elseif($field->type == 'relation') {
            $data = null;
        };

        return $data;
    }

    public static function getDataByObject($field, $slug, $current)
    {
        $settings = get_settings();

        if($field->type == 'status')
            $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
        $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
        
        $val = (string)$current->{$field->field};
        $field_value = ValueHelper::isJson($val) && $field->field != 'products' && is_array(json_decode($val, true)) ? json_decode($val, true) : $val;
        $fields_data[$field->field] = array(
            'id' => $field->id,
            'title' => $field->display_name,
            'key' => $field->field,
            'type' => $field->type,
            'visible_always' => $field->visible_always,
            'is_hidden' => $field->hide,
            'is_plural' => $field->is_plural,
            'show_file_name' => $field->show_file_name,
            'is_external_link' => $field->is_external_link,
            'is_link' => $field->is_link,
            'external_link' => $field->external_link,
            'required' => $field->required,
            'read_only' => $field->only_read,
            'permanent_required' => $field->permanent_required,
            'permanent_name' => $field->permanent_name,
            'is_permanent' => $field->is_permanent,
            'can_edit' => !$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0,
            'color' => $field_colors[$field->field],
            'group_id' => $field->group_id,
            'button_name' => $field->button_name,
            'value' => $field_value,
            //'value' => $current->{$field->field},
            'sort' => $field->sort,
            'unit' => $field->unit,
            'module' => $field->module,
            'used_in_modules' => $settings[$slug]['used_in_modules'][$field->field],
            'mask' => $field->mask,
            'comparison' => $comparisons[$field->id] ?? null,
            'editableFields' => array(
                'id' => $field->id,
                'model' => $slug,
                'title' => $field->display_name,
                'type' => $field->type,
                'is_plural' => $field->is_plural,
                'show_file_name' => $field->show_file_name,
                'is_external_link' => $field->is_external_link,
                'is_link' => $field->is_link,
                'external_link' => $field->external_link,
                'required' => $field->required,
                'permanent_required' => $field->permanent_required,
                'permanent_name' => $field->permanent_name,
                'visible_always' => $field->visible_always,
                'section_id' => $field->section_id,
                'module_section_id' => $field->module_section_id,
                'values' => array(
                    array('value' => 0, 'label' => '')
                ),
                'set_color' => ($field->label_color ? $field->label_color : 0),
                'color' => ($field->label_color ? $field->label_color : '#000'),
                'button_name' => $field->button_name,
                'has_roles_read' => ($field->roles_read ? 1 : 0),
                'has_roles_write' => ($field->roles_write ? 1 : 0),
                'roles_read' => ($field->roles_read ? json_decode($field->roles_read, true) : array()),
                'roles_write' => ($field->roles_write ? json_decode($field->roles_write, true) : array()),
                'show_in_mobile' => ($field->mobile_pages ? 1 : 0),
                'blocked_changes' => $field->blocked_changes,
                'mobile_pages' => ($field->mobile_pages ? json_decode($field->mobile_pages, true) : array()),
                'unit' => $field->unit,
                'statuses' => array()
            )

        );
        $list_values = array();
        if(isset($settings[$slug]['list_values'][$field->field]))
            $list_values = $settings[$slug]['list_values'][$field->field];
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
        // if($field->type == 'relation' && $field->is_plural) {
        //     $values = $fields_data[$field->field]['value'];
        //     $fields_data[$field->field]['value'] = array();
        //     if(is_array($values)) {
        //         foreach($values as $val) {
        //             if(isset($settings[$slug]['list_values'][$field->field][$val]))
        //                 $fields_data[$field->field]['value'][] = $settings[$slug]['list_values'][$field->field][$val]['value'];
        //         }
        //     }
        // } elseif($field->type == 'relation' && isset($settings[$slug]['list_values'][$field->field][$fields_data[$field->field]['value']])) {
        //     $fields_data[$field->field]['value'] = array($settings[$slug]['list_values'][$field->field][$fields_data[$field->field]['value']]['value']);
        // } elseif($field->type == 'relation') {
        //     $fields_data[$field->field]['value'] = null;
        // }

        if($field->type == 'relation') {
            if($field->field == 'category_id' || $field->field == 'role_id')
                $fields_data[$field->field]['can_create'] = 0;
            else
                $fields_data[$field->field]['can_create'] = 1;
        }
        if(isset($settings[$slug]['list_values'][$field->field])) {
            $values = array();
            if($field->type == 'relation') {
                $field_values = array_slice($settings[$slug]['list_values'][$field->field], 0, 19, true);
                if($field->is_plural && is_array($fields_data[$field->field]['value'])) {
                    if(isset($fields_data[$field->field]['value']['value']))
                        foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                            $field_values[$field_val] = $settings[$slug]['list_values'][$field->field][$field_val];
                        }
                } elseif($current->{$field->field} && isset($settings[$slug]['list_values'][$field->field][$current->{$field->field}])) {
                    $field_values[$current->{$field->field}] = $settings[$slug]['list_values'][$field->field][$current->{$field->field}];
                } elseif($current->{$field->field}) {
                    $field_values[$current->{$field->field}] = null;
                }
            } else {
                $field_values = $settings[$slug]['list_values'][$field->field];
            }
            $fields_data[$field->field]['options'] = array_values($field_values);
            $fields_data[$field->field]['choosed'] = [];
            if(isset($settings[$slug]['fields'][$field->field]->choosed))
                $fields_data[$field->field]['choosed'] = $settings[$slug]['fields'][$field->field]->choosed;
            if($field->type == 'status') {
                $simple_options = array();
                foreach($field_values as $option) {
                    $simple_options[$option['value']] = $option['label']['text'];
                    $values[] = array(
                        'value' => $option['value'],
                        'label' => $option['label']['text'],
                        'sort' => $option['label']['sort']
                    );
                }
            } else {
                if($field->type == 'relation') {
                    if($field->is_plural && isset($fields_data[$field->field]['value']['value'])) {
                        foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                            $field_values[$field_val] = $settings[$slug]['list_values'][$field->field][$field_val];
                        }
                    } elseif($current->{$field->field} && isset($settings[$slug]['list_values'][$field->field][$current->{$field->field}])) {
                        $field_values[$current->{$field->field}] = $settings[$slug]['list_values'][$field->field][$current->{$field->field}];
                    } elseif($current->{$field->field}) {
                        $field_values[$current->{$field->field}] = null;
                    }
                } else {
                    $field_values = $settings[$slug]['list_values'][$field->field];
                }
                foreach($field_values as $k => $option) {
                    $simple_options[$k] = $option;
                    $values[] = array(
                        'value' => $k,
                        'label' => is_array($option) ? $option['label'] : $option,
                        'sort' => is_array($option) && isset($option['sort']) ? $option['sort'] : $k
                    );
                }
            }
            $fields_data[$field->field]['editableFields']['values'] = $values;
            if($field->type == 'status')
                $fields_data[$field->field]['editableFields']['statuses'] = $settings[$slug]['list_values'][$field->field];
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
                $fields_data[$field->field]['options'][$subfield->id] = $subfield->display_name;
                $values[] = array(
                    'value' => $subfield->id,
                    'label' => $subfield->display_name,
                    'sort' => $subfield->sort
                );
            };
            $fields_data[$field->field]['editableFields']['values'] = $values;
            $fields_data[$field->field]['subfields'] = array();
            foreach($subfields as $subfield) {
                $fields_data[$field->field]['subfields'][] = array(
                    'id' => $subfield->id,
                    'title' => $subfield->display_name,
                    'key' => $subfield->field,
                    'type' => $subfield->type,
                    'visible_always' => $field->visible_always,
                    'is_hidden' => $field->hide,
                    'is_plural' => $field->is_plural,
                    'required' => $field->required,
                    'read_only' => $field->only_read,
                    'can_edit' => !$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0,
                    'color' => $field_colors[$field->field],
                    'group_id' => $subfield->group_id,
                    'value' => $current->{$subfield->field},
                    'sort' => $subfield->sort
                );
            }
        }

        return $fields_data[$field->field];
    }
}
