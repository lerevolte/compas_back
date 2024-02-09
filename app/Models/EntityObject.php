<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use App\Helpers\ValueHelper;

class EntityObject
{
    public static function detail($slug, $id, Request $request)
    {
        $settings = get_settings();

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
        

        $entity = $settings['models'][$slug];
        if(!$entity || !$entity->enable) {
            return [
                'error' => array(
                    'message' => 'Entity not found',
                    'code' => 404
                )
            ];
        }
        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];
        $fields_data = array();
        $current = $entity_class::withTrashed()->where(['id' => $id])->first();
        if(!$current) {
            return [
                'error' => array(
                    'message' => 'Object not found',
                    'code' => 404
                )
            ];
        }

        $data['title'] = $current->name;
        if(ValueHelper::isJson($data['title'])) {
            $data['title'] = json_decode($data['title'], true);
            if(isset($data['title']['value'])) {
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
                if($field->type == 'status')
                    $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
                $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
                
                $val = (string)$current->{$field->field};
                $field_value = ValueHelper::isJson($val) && $field->field != 'products' && is_array(json_decode($val, true)) ? json_decode($val, true) : $val;
                //MONDAY
                // if($slug == 'logistic_tasks' && $field->field == 'client_id') {
                //     $field_value = $current->clients()->pluck('id')->toArray();
                // }
                // if($slug == 'clients' && $field->field == 'task_id') {
                //     $field_value = $current->logistic_tasks()->pluck('id')->toArray();
                // }
                // if($slug == 'cars' && $field->field == 'employee_id') {
                //     $field_value = $current->employees()->pluck('id')->toArray();
                // }
                // if($slug == 'employees' && $field->field == 'car_id') {
                //     $field_value = $current->cars()->pluck('id')->toArray();
                // }
                // if($slug == 'categories' && $field->field == 'product_id') {
                //     $field_value = $current->products()->pluck('id')->toArray();
                // }
                // if($slug == 'products' && $field->field == 'category_id') {
                //     $field_value = $current->categories()->pluck('id')->toArray();
                // }
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
                if($field->type == 'file' && !isset($field_value[0])) {
                    $fields_data[$field->field]['value'] = array($field_value);
                }
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
                        if(isset($settings[$slug]['options'][$field->field]))
                            $field_values = $settings[$slug]['options'][$field->field];
                        else
                            $field_values = $settings[$slug]['list_values'][$field->field];
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
                                    $field_values[$field_val] = $settings[$slug]['list_values'][$field->field][$field_val]['value'];
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
                // $fields_data[$field->field] = Field::getDataByObject($field, $slug, $current);
                
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
                    if(isset($fields_data[$field->field]))
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
                    if(isset($fields_data[$field->field]))
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

        return $data;
        
    }

    public static function detail_module($slug, $id, $module, Request $request)
    {
        $settings = get_settings();

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
        

        $entity = $settings['models'][$slug];
        if(!$entity || !$entity->enable) {
            return [
                'error' => array(
                    'message' => 'Entity not found',
                    'code' => 404
                )
            ];
        }
        $entity_class = $entity->model_name;
        $entity_fields = $settings[$slug]['fields'];
        $model_fields = $entity_class::getFieldsByModule($module);
        $fields_data = array();
        $current = $entity_class::withTrashed()->where(['id' => $id])->first();
        if(!$current) {
            return [
                'error' => array(
                    'message' => 'Object not found',
                    'code' => 404
                )
            ];
        }

        $data['title'] = $current->name;
        if(ValueHelper::isJson($data['title'])) {
            $data['title'] = json_decode($data['title'], true);
            if(isset($data['title']['value'])) {
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

        return $data;
        
    }

    public static function list($slug, Request $request)
    {
        $settings = get_settings();
        $tenant = tenant('id');

        if(!$request->filter && $request->is_slug) {
            return response()->json([]);
        }
        $limit = $request->per_page ? $request->per_page : 25;
        $page = $request->page ? $request->page : 1;
        $sort_field = $request->sort_field ? $request->sort_field : 'id';
        $sort_order = $request->sort_order ? $request->sort_order : 'desc';   

        $entity = $settings['models'][$slug];
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        $model_fields = collect($settings[$slug]['fields']);

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );


        if($sort_order == 'asc')
            $paginator = $entity_class::orderByRaw("$sort_field REGEXP '^-?[0-9\.]+$' AND LENGTH($sort_field) - LENGTH(REPLACE($sort_field, '.', '')) < 2 DESC, CAST($sort_field AS UNSIGNED), $sort_field");
        else
            $paginator = $entity_class::orderByRaw("$sort_field REGEXP '^-?[0-9\.]+$' AND LENGTH($sort_field) - LENGTH(REPLACE($sort_field, '.', '')) < 2 DESC, CAST($sort_field AS UNSIGNED), $sort_field desc");
        if($sort_field == 'id')
            $paginator = $entity_class::orderByRaw("CAST($sort_field AS DECIMAL) $sort_order");
        // foreach($model_fields as $field) {
        //     if($field->field == $sort_field && $field->type == 'file')
        //         $paginator = $entity_class::orderBy($sort_field, $sort_order);
        // }
        
        if($request->trashed) {
            $paginator = $paginator->onlyTrashed();
        }
        if($request->filter && is_array($request->filter)){

            foreach($request->filter as $field => $val) {
                if($field == 'created_at' || $field == 'updated_at')
                    $paginator = $paginator->whereDate($field, $val);
                elseif($settings[$slug]['fields'][$field]->type == 'date')
                    $paginator = $paginator->whereDate($field, date('Y-m-d', strtotime($val)));
                else {
                    if(isset($settings[$slug]['fields'][$field]) && $settings[$slug]['fields'][$field]->is_plural) {
                        if($settings[$slug]['fields'][$field]->type == 'relation') {
                            $paginator = $paginator->whereJsonContains($field, (int)$val);
                        }
                        else {
                            //->whereRaw("json_contains(`client_id`, ?)", [15])->whereRaw('json_contains(`tip_tk`, \'"'.$str.'"\')')
                            info('filter '.$field);
                            info('filter '.$val);
                            $paginator = $paginator->whereRaw('json_contains('.$field.', \''.$val.'\')');
                            //$paginator = $paginator->whereRaw('json_contains('.$field.', \'"'.$val.'"\')');
                        }
                    } else {
                        if($field == 'category_id' && !$request->exclude_childs) {
                            foreach($model_fields as $f) {
                                if($f->field == $field) {
                                    $related_table = json_decode($f->details, true)['table'];
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
                                // $paginator = $paginator->where(function ($query) use ($search_columns, $q) {
                                //     foreach ($search_columns as $column) {
                                //         $query->orWhere($column, 'like', "%{$q}%");
                                //     }
                                // });
                                
                                if($settings[$slug]['fields'][$field]->type == 'address')
                                    //$paginator = $paginator->whereJsonContains('address', ['text' => $val]);
                                    $paginator = $paginator->where("{$field}->text",'like', "%{$val}%");
                                    //$paginator = $paginator->whereJsonContains("address->text", $val);
                                    //$paginator = $paginator->where($field, '%"text": "'.$val.'"%');
                                    //$paginator = $paginator->whereRaw('json_contains('.$field.', \'"'.$val.'"\')');
                                    //$paginator = $paginator->whereJsonContains($field, $val);
                                elseif($settings[$slug]['fields'][$field]->type == 'text')
                                    $paginator = $paginator->where($field, 'like', "%{$val}%");
                                else
                                    $paginator = $paginator->where($field, $val);
                                
                            }
                        }
                        
                    }
                    
                }
            }
        }

        if($request->order_id && $slug == 'products') {
            $order = \App\Models\Task::find($request->order_id);
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
                    $paginator = $paginator->whereIntegerInRaw('id', $product_ids);
                } else {
                    return response()->json([]);
                }
            }
        };

        if($request->q) {
            $q = $request->q;

            $search_columns = $model_fields->filter(function ($field) {
                                return ($field->type != 'relation' && $field->type != 'status' && $field->type != 'text_group');
                            })->pluck('field')->toArray();
            

            $paginator = $paginator->where(function ($query) use ($slug, $settings, $model_fields, $search_columns, $q) {
                foreach ($search_columns as $column) {
                    $query->orWhere($column, 'like', "%{$q}%");
                }
                foreach($model_fields as $field) {
                    if($field->type == 'relation') {
                        $relations = collect($settings[$slug]['list_values'][$field->field])->filter(function ($item) use ($q) {
                            return mb_stristr($item['label']['text'], $q);
                        })->pluck('value')->toArray();
                        if(count($relations) && $field->is_plural) {
                            $query->orWhere(function ($subquery) use ($field, $relations) {
                               foreach ($relations as $id) {
                                   $subquery->orWhereJsonContains($field->field, $id);
                               }
                            });
                            //$paginator = $paginator->whereJsonContains($field->field, (int)$val);
                        } elseif(count($relations)) {
                            $query->orWhere(function ($subquery) use ($field, $relations) {
                               foreach ($relations as $id) {
                                   $subquery->orWhere($field->field, $id);
                               }
                            });
                        }
                    }
                }
            });
            
            
        };
        info('SQL');
        info($paginator->toSql());
        $paginator = $paginator->paginate($limit);
        
        $objects = array();
        $field_values = array();
        
        foreach ($paginator->items() as $item) {
            $data = array(
                'id' => $item->id
            );
            foreach($model_fields as $field) {
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
                    $value = $item->{$field->field};
                    $field_value = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;
                    $data[$field->field] = $field_value;
                    $list_values = array();
                    if(isset($settings[$slug]['list_values'][$field->field]))
                        $list_values = $settings[$slug]['list_values'][$field->field];
                    if($field->type == 'file' && !isset($field_value[0])) {
                        $data[$field->field] = array($field_value);
                    }
                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $field_value;
                        $data[$field->field] = array(
                            'key' => array(
                                'value' => array(),
                                'localOptions' => array()
                            )
                        );
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($list_values[$val])) {
                                    $data[$field->field]['key']['value'][] = $list_values[$val]['value'];
                                    $data[$field->field]['key']['localOptions'][] = $list_values[$val];
                                }
                            }
                        }
                    } elseif($field->type == 'relation' && isset($list_values[$field_value])) {
                        $data[$field->field] = array(
                            'key' => array(
                                'value' => array(),
                                'localOptions' => array()
                            )
                        );
                        $data[$field->field]['key']['localOptions'] = array($list_values[$field_value]);
                        $data[$field->field]['key']['value'] = array($list_values[$field_value]['value']);
                        
                    } elseif($field->type == 'relation') {
                        $data[$field->field] = array(
                            'key' => array(
                                'value' => null,
                                'localOptions' => null
                            )
                        );
                    }
                }
            }
            if(isset($order) && $order && $slug == 'products') {
                $products = json_decode($order->products, true);
                if(is_array($products)) {
                    foreach($products as $num => $product) {
                        if($product['id'] == $item->id) {
                            $data['product_name'] = isset($product['product_name']) ? $product['product_name'] : $product['name'];
                            $data['product_price'] = $product['price'];
                            $data['product_count'] = $product['count'];
                            $data['product_weight'] = $product['weight'];
                            $data['product_sum'] = $product['sum'];
                            $data['sort'] = $num;
                        }
                    }
                }
                
            }
            
            $objects[] = $data;
        }
        if($sort_order == 'asc' && $sort_field)
            array_sort_by_column($objects, $sort_field, SORT_ASC, SORT_NATURAL);
        elseif($sort_field)
            array_sort_by_column($objects, $sort_field, SORT_DESC, SORT_NATURAL);
        info('SORT');
        info($sort_order.' '.$sort_field);
        
        $res = array(
            'count' => $paginator->count(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'data' => $objects,
            'buttons' => $settings[$slug]['buttons']
        );
        return $res;

    }
    
}
