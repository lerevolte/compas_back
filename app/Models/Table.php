<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\ValueHelper;

class Table
{
    public static function get($slug)
    {
        $start_time = microtime(true);  // Время окончания
        if($slug == 'documents' && tenant('id')) {
            $table_columns = array(
                [
                    'id' => 105,
                    'title' => 'Дата создания',
                    'key' => 'created_at',
                    'width' => '368px',
                    'enabled' => true,
                    'sort_order' => 'desc',
                    'type' => 'date',
                    'is_plural' => 0,
                    'external_link' => null,
                    'is_external_link' => 1,
                    'is_link' => 0,
                    'required' => 1,
                    'fixed' => null,
                    'index' => 5,
                    'fixTarget' => '0px',
                    'read_only' => 0,
                    'unit' => '',
                    'mask' => '',
                    'can_edit' => 0,
                    'color' => '',
                    'is_hidden' => 0,
                    'visible_always' => 1,
                    'options' => []
                ],                
                [
                    'id' => 798,
                    'title' => 'Итоговая сумма',
                    'key' => 'sum',
                    'width' => '88px',
                    'enabled' => true,
                    'sort_order' => null,
                    'type' => 'number',
                    'is_plural' => 0,
                    'external_link' => '',
                    'is_external_link' => 0,
                    'is_link' => 0,
                    'required' => 1,
                    'fixed' => null,
                    'index' => 2,
                    'fixTarget' => '0px',
                    'read_only' => 1,
                    'unit' => 'руб.',
                    'mask' => '',
                    'can_edit' => 0,
                    'color' => '',
                    'is_hidden' => 0,
                    'visible_always' => 1,
                    'options' => []
                ],
                [
                    'id' => 2249,
                    'title' => 'Документ',
                    'key' => 'photo',
                    'width' => '325px',
                    'enabled' => true,
                    'sort_order' => null,
                    'type' => 'text',
                    'is_plural' => 0,
                    'external_link' => '',
                    'is_external_link' => 1,
                    'is_link' => 0,
                    'required' => 0,
                    'fixed' => false,
                    'index' => 7,
                    'fixTarget' => '0px',
                    'read_only' => 0,
                    'unit' => '',
                    'mask' => '',
                    'can_edit' => 0,
                    'color' => '#000',
                    'is_hidden' => 0,
                    'visible_always' => 1,
                    'options' => []
                ],
                [
                    'id' => 1,
                    'title' => 'Акт',
                    'key' => 'act',
                    'width' => '325px',
                    'enabled' => true,
                    'sort_order' => null,
                    'type' => 'text',
                    'is_plural' => 0,
                    'external_link' => '',
                    'is_external_link' => 1,
                    'is_link' => 0,
                    'required' => 0,
                    'fixed' => false,
                    'index' => 7,
                    'fixTarget' => '0px',
                    'read_only' => 0,
                    'unit' => '',
                    'mask' => '',
                    'can_edit' => 0,
                    'color' => '#000',
                    'is_hidden' => 0,
                    'visible_always' => 1,
                    'options' => []
                ],
                [
                    'id' => 842,
                    'title' => 'Наименование компании',
                    'key' => 'requisite_id',
                    'width' => '378px',
                    'enabled' => true,
                    'sort_order' => null,
                    'type' => 'relation',
                    'is_plural' => 1,
                    'external_link' => '',
                    'is_external_link' => 0,
                    'is_link' => 1,
                    'required' => 0,
                    'fixed' => null,
                    'index' => 4,
                    'fixTarget' => '0px',
                    'read_only' => 1,
                    'unit' => '',
                    'mask' => null,
                    'can_edit' => 0,
                    'color' => '',
                    'is_hidden' => 0,
                    'visible_always' => 1,
                    'options' => [],
                    'related_table' => 'requisites',
                    'can_create' => 1
                ]
            );

            return $table_columns;
        }
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        $user = \Auth::user(); 
        if(!$user)
            $user = \App\Models\User::find(1);      
        $tables = $user->tables;
        if($tables)
            $tables = json_decode($tables, true);
        else
            $tables = array();
        $end_time = microtime(true);  // Время окончания

        $execution_time = $end_time - $start_time;  // Время выполнения

        info("table1: " . $execution_time . " секунд.");
        if(!isset($tables[$slug])) {
            $role = \App\Models\Role::find($user->role_id);
            if($role && $role->tables) {
                $role_tables = json_decode($role->tables, true);
                if(!is_array($role_tables))
                    $role_tables = array();
                if(isset($role_tables[$slug])) {
                    $tables[$slug] = $role_tables[$slug];
                    $user->tables = json_encode($tables);
                    $user->timestamps = false;
                    $user->saveQuietly();
                }
            }

            if(!isset($tables[$slug])) {
                $settings = \DB::table('settings')->where('key', 'tables')->first();
                if($settings && $settings->value) {
                    $tables_all = json_decode($settings->value, true);
                    if(isset($tables_all[$slug])) {
                        $tables[$slug] = $tables_all[$slug];
                        $user->tables = json_encode($tables);
                        $user->timestamps = false;
                        $user->saveQuietly();
                    }
                }
            }
        }
        $start_time = microtime(true);  // Время окончания
        $settings = app('settings');
        $end_time = microtime(true);  // Время окончания

        $execution_time = $end_time - $start_time;  // Время выполнения

        info("table2: " . $execution_time . " секунд.");
        $start_time = microtime(true);  // Время окончания
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        if(!$entity || !$entity->enable) {
            return [
                'error' => array(
                    'message' => 'Entity not found',
                    'code' => 404
                )
            ];
        }
        $entity_class = $entity->model_name;
        $permissions = \Auth::user()->role->permissions()->select([
            'read_p',
            'create_p',
            'update_p',
            'delete_p',
            'export_p',
            'import_p'
        ])->where('entity_id', $entity->id)->first();

        if($permissions) {
            $permissions = $permissions->toArray();
        }
        if(!$permissions) {
            $data_types = \DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
            $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', \Auth::user()->role_id)->get()->keyBy('entity_id')->toArray();
            $isAdminRole = (bool) (\Auth::user()->role->is_admin ?? false);
            foreach($data_types as $entity_id => $data_type) {
                if(!array_key_exists($entity_id, $res)) {
                    \DB::table('permissions')->insert([\App\Models\Permission::newEntityRow((int) $entity_id, (int) \Auth::user()->role_id, $isAdminRole)]);
                }
            }
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $entity->id)->first();
            if($permissions) {
                $permissions = $permissions->toArray();
            }
        }
        $permissions_all = \Auth::user()->role->permissions->keyBy('entity_id')->toArray();
        $end_time = microtime(true);  // Время окончания

        $execution_time = $end_time - $start_time;  // Время выполнения

        info("table3: " . $execution_time . " секунд.");
        //\App\Models\Settings::clear_cache();
        $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();
        $tariff = Tariff::current();
        $restrictions = [];
        if($tariff->restrictions) {
            $restrictions_tariff = json_decode($tariff->restrictions,true);

        }
        if(isset($tables[$slug]['fields'])) {
            $model_fields = collect($model_fields);
            $table_columns = collect($tables[$slug]['fields']);
            $table_columns = $table_columns->keyBy('key')->toArray();
            foreach($table_columns as $key => $column) {
                if(strpos((string)$key, 'rel__') === 0) {
                    $table_columns[$key]['type'] = 'text';
                    $table_columns[$key]['read_only'] = 1;
                    $table_columns[$key]['can_edit'] = 0;
                    $rel_parts = explode('__', (string)$key);
                    if(count($rel_parts) >= 3) {
                        $rel_slug = $rel_parts[1];
                        $rel_field_key = implode('__', array_slice($rel_parts, 2));
                        $rel_name = \DB::table('data_types')->where('slug', $rel_slug)->value('name') ?: $rel_slug;
                        if(isset($settings[$rel_name]['fields'])) {
                            $rel_field = collect($settings[$rel_name]['fields'])->first(function($f) use ($rel_field_key) {
                                return $f->field == $rel_field_key;
                            });
                            if($rel_field) {
                                $table_columns[$key]['type'] = $rel_field->type;
                                $table_columns[$key]['is_plural'] = $rel_field->is_plural;
                                $table_columns[$key]['unit'] = $rel_field->unit;
                                $table_columns[$key]['mask'] = $rel_field->mask;
                                $table_columns[$key]['is_external_link'] = $rel_field->is_external_link;
                                $table_columns[$key]['set_color'] = $rel_field->set_color;
                                $table_columns[$key]['color'] = $rel_field->label_color;

                                $rel_options = array();
                                if($rel_field->type == 'relation' && isset($settings['list_values'][$rel_field->id])) {
                                    $rel_options = array_slice($settings['list_values'][$rel_field->id], 0, 19, true);
                                } elseif(isset($settings[$rel_name]['options'][$rel_field_key])) {
                                    $rel_options = $settings[$rel_name]['options'][$rel_field_key];
                                } elseif(isset($settings['list_values'][$rel_field->id])) {
                                    $rel_options = $settings['list_values'][$rel_field->id];
                                }
                                $table_columns[$key]['options'] = array_values(is_array($rel_options) ? $rel_options : []);

                                if($rel_field->type == 'relation' && $rel_field->details) {
                                    $rel_details = json_decode($rel_field->details, true);
                                    if(isset($rel_details['table'])) {
                                        $table_columns[$key]['related_table'] = $rel_details['table'];
                                    }
                                }
                            }
                        }
                    }
                    continue;
                }
                if(!$model_fields->contains('field', $key) && $key != 'isChoose' && $key != 'actions' && $key != 'iconDrag' && $key != 'iconDelete' || isset($settings[$slug]['perms'][$key]['read']) && !$settings[$slug]['perms'][$key]['read'])
                    unset($table_columns[$key]);
                elseif(isset($model_fields[$key]) && $column['type'] == 'relation' && $model_fields[$key]->relation_table && (!isset($settings['models'][$model_fields[$key]->relation_table]) || !$settings['models'][$model_fields[$key]->relation_table]->enable))
                    unset($table_columns[$key]);
            }
            if(!isset($table_columns['isChoose']) && $slug != 'balance_operations') {
                $table_columns['isChoose'] = array(
                    "id" => 0,
                    "title" => "Выделение",
                    "key" => "isChoose",
                    "width" => "40.00px",
                    "enabled" => true,
                    "hover" => false,
                    "sort_order" => null,
                    "type" => "checkbox",
                    "fixed" => true,
                    "fixTarget" => "0px",
                    "index" => 0,
                    "mask" => ""
                );
            }
            if(!isset($table_columns['actions']) && $slug != 'balance_operations') {
                $table_columns['actions'] = array(
                    "id" => 2,
                    "title" => "Действие",
                    "key" => "actions",
                    "width" => "40.00px",
                    "enabled" => true,
                    "hover" => false,
                    "sort_order" => null,
                    "type" => "actions",
                    "fixed" => true,
                    "index" => 1,
                    "fixTarget" => "40px",
                    "mask" => ""
                );
            }
            
            $start_time = microtime(true);  // Время окончания
            foreach ($model_fields as $field) {
                $field_values = array();
                $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
                if(isset($settings['list_values'][$field->id])) {
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true); 
                        // if($item->{$field->field})
                        //     $field_values[$item->{$field->field}] = $settings['list_values'][$field->id][$item->{$field->field}];
                    } else {
                        if(isset($settings[$slug]['options'][$field->field]))
                            $field_values = $settings[$slug]['options'][$field->field];
                        else
                            $field_values = $settings['list_values'][$field->id];
                    }
                };
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->type != 'password' && (!isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'])) {
                    if($field->type == 'relation' && $field->relation_table && (!isset($settings['models'][$field->relation_table]) || !$settings['models'][$field->relation_table]->enable))
                        continue;
                    if($field->type == 'waybills' && !\App\Services\Saby\SabyOrderService::ready())
                        continue;
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_parent_name ? $field->display_parent_name.', '.$field->title : $field->title,
                        'key' => $field->field,
                        'width' => '200px',
                        'enabled' => ($field->is_default ? true : false),
                        'sort_order' => '',
                        'type' => $field->type,
                        'is_plural' => ($field->type == 'text' ? 1 : $field->is_plural),
                        'external_link' => $field->external_link,
                        'is_external_link' => $field->is_external_link,
                        'is_link' => $field->is_link,
                        'required' => $field->required,
                        'fixed' => '',
                        'index' => count($table_columns) + 1,
                        'fixTarget' => '0px',
                        'unit' => $field->unit,
                        'mask' => $field->mask
                    );
                    $table_columns[$field->field]['type'] = $field->type;
                    $table_columns[$field->field]['read_only'] = $field->only_read || (isset($settings[$slug]['perms'][$field->field]['write']) && !$settings[$slug]['perms'][$field->field]['write']) || (isset($permissions['update_p']) && $permissions['update_p'] == 'N') ? 1 : 0;
                    if(\Auth::user()->is_admin && !$field->only_read)
                        $table_columns[$field->field]['read_only'] = 0;
                    $table_columns[$field->field]['can_read'] = !isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'] || \Auth::user()->is_admin ? 1 : 0;
                    $table_columns[$field->field]['can_edit'] = $field->only_read || (isset($settings[$slug]['perms'][$field->field]['write']) && !$settings[$slug]['perms'][$field->field]['write']) && !\Auth::user()->is_admin ? 0 : 1;
                    $table_columns[$field->field]['set_color'] = $field->set_color;
                    $table_columns[$field->field]['color'] = $field->label_color;
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = array_values($field_values);
                    if($field->type == 'status' && $field->details) {
                        $details = json_decode($field->details, true);
                        if(isset($details['can_create'])) {
                            $table_columns[$field->field]['can_create'] = $details['can_create'] ? 1 : 0;
                        }
                    }

                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        // if($field->field == 'category_id')
                        //     $table_columns[$field->field]['can_create'] = 0;
                        // else
                        $table_columns[$field->field]['can_create'] = 1;
                        if($field->relation_table && isset($settings['models'][$field->relation_table]) && isset($permissions_all[$settings['models'][$field->relation_table]->id]['create_p']) && !\Auth::user()->is_admin)
                            $table_columns[$field->field]['can_create'] = $permissions_all[$settings['models'][$field->relation_table]->id]['create_p'] == 'N' ? 0 : 1;

                    }
                } elseif($field->type != 'text_group' && $field->type != 'password' && (!isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'])) {
                    if($field->type == 'relation' && $field->relation_table && (!isset($settings['models'][$field->relation_table]) || !$settings['models'][$field->relation_table]->enable))
                        continue;
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_parent_name ? $field->display_parent_name.', '.$field->title : $field->title,
                        'key' => $field->field,
                        'width' => $table_columns[$field->field]['width'],
                        'enabled' => $table_columns[$field->field]['enabled'],
                        'sort_order' => $table_columns[$field->field]['sort_order'],
                        'type' => $field->type,
                        'is_plural' => ($field->type == 'text' ? 1 : $field->is_plural),
                        'external_link' => $field->external_link,
                        'is_external_link' => $field->is_external_link,
                        'is_link' => $field->is_link,
                        'required' => $field->required,
                        'fixed' => $table_columns[$field->field]['fixed'],
                        'index' => $table_columns[$field->field]['index'],
                        'fixTarget' => $table_columns[$field->field]['fixTarget'],
                        'unit' => $field->unit,
                        'mask' => $field->mask
                    );
                    $table_columns[$field->field]['type'] = $field->type;

                    $table_columns[$field->field]['read_only'] = $field->only_read || (isset($settings[$slug]['perms'][$field->field]['write']) && !$settings[$slug]['perms'][$field->field]['write']) || (isset($permissions['update_p']) && $permissions['update_p'] == 'N')? 1 : 0;


                    if(\Auth::user()->is_admin && !$field->only_read)
                        $table_columns[$field->field]['read_only'] = 0;
                    $table_columns[$field->field]['can_read'] = !isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'] || \Auth::user()->is_admin ? 1 : 0;
                    $table_columns[$field->field]['can_edit'] = $field->only_read || (isset($settings[$slug]['perms'][$field->field]['write']) && !$settings[$slug]['perms'][$field->field]['write']) && !\Auth::user()->is_admin ? 0 : 1;
                    $table_columns[$field->field]['set_color'] = $field->set_color;
                    $table_columns[$field->field]['color'] = $field->label_color;
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = array_values($field_values);
                    if($field->type == 'status' && $field->details) {
                        $details = json_decode($field->details, true);
                        if(isset($details['can_create'])) {
                            $table_columns[$field->field]['can_create'] = $details['can_create'] ? 1 : 0;
                        }
                    }
                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        $table_columns[$field->field]['can_create'] = 1;
                        if($field->relation_table && isset($settings['models'][$field->relation_table]) && isset($permissions_all[$settings['models'][$field->relation_table]->id]['create_p']) && !\Auth::user()->is_admin)
                            $table_columns[$field->field]['can_create'] = $permissions_all[$settings['models'][$field->relation_table]->id]['create_p'] == 'N' ? 0 : 1;

                        if($field->relation_table && isset($restrictions_tariff['objects'][$field->relation_table]['count'])) {
                            if($restrictions_tariff['objects'][$field->relation_table]['count'] <= \DB::table($field->relation_table)->whereNull('deleted_at')->count()) {
                                $table_columns[$field->field]['can_create'] = 0;
                            };
                        }

                    }
                }
                
                
            }
            $end_time = microtime(true);  // Время окончания

            $execution_time = $end_time - $start_time;  // Время выполнения

            info("table4: " . $execution_time . " секунд.");
        } else {
            
            $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();
            $table_columns = array();
            if(!isset($table_columns['isChoose'])) {
                $table_columns['isChoose'] = array(
                    "id" => 0,
                    "title" => "Выделение",
                    "key" => "isChoose",
                    "width" => "40.00px",
                    "enabled" => true,
                    "hover" => false,
                    "sort_order" => null,
                    "type" => "checkbox",
                    "fixed" => true,
                    "fixTarget" => "0px",
                    "index" => 0,
                    "mask" => ""
                );
            }
            if(!isset($table_columns['actions'])) {
                $table_columns['actions'] = array(
                    "id" => 2,
                    "title" => "Действие",
                    "key" => "actions",
                    "width" => "40.00px",
                    "enabled" => true,
                    "hover" => false,
                    "sort_order" => null,
                    "type" => "actions",
                    "fixed" => true,
                    "index" => 1,
                    "fixTarget" => "40px",
                    "mask" => ""
                );
            }
            foreach ($model_fields as $field) {
                $field_values = array();
                $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
                if(isset($settings['list_values'][$field->id])) {
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true); 
                        // if($item->{$field->field})
                        //     $field_values[$item->{$field->field}] = $settings['list_values'][$field->id][$item->{$field->field}];
                    } else {
                        $field_values = $settings['list_values'][$field->id];
                    }
                };
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->type != 'password' && (!isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'])) {
                    if($field->type == 'relation' && $field->relation_table && (!isset($settings['models'][$field->relation_table]) || !$settings['models'][$field->relation_table]->enable))
                        continue;
                    if($field->type == 'waybills' && !\App\Services\Saby\SabyOrderService::ready())
                        continue;
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_parent_name ? $field->display_parent_name.', '.$field->title : $field->title,
                        'key' => $field->field,
                        'width' => '200px',
                        'enabled' => ($field->is_default ? true : false),
                        'sort_order' => ($field->field == 'id' ? 'desc':''),
                        'type' => $field->type,
                        'is_plural' => ($field->type == 'text' ? 1 : $field->is_plural),
                        'external_link' => $field->external_link,
                        'is_external_link' => $field->is_external_link,
                        'is_link' => $field->is_link,
                        'required' => $field->required,
                        'fixed' => '',
                        'index' => count($table_columns) + 1,
                        'fixTarget' => '0px',
                        'unit' => $field->unit,
                        'mask' => $field->mask
                    );
                    $table_columns[$field->field]['type'] = $field->type;

                    $table_columns[$field->field]['read_only'] = $field->only_read || (isset($settings[$slug]['perms'][$field->field]['write']) && !$settings[$slug]['perms'][$field->field]['write']) || (isset($permissions['update_p']) && $permissions['update_p'] == 'N') ? 1 : 0;
                    if(\Auth::user()->is_admin && !$field->only_read)
                        $table_columns[$field->field]['read_only'] = 0;

                    $table_columns[$field->field]['can_read'] = !isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'] || \Auth::user()->is_admin ? 1 : 0;
                    $table_columns[$field->field]['can_edit'] = $field->only_read || (isset($settings[$slug]['perms'][$field->field]['write']) && !$settings[$slug]['perms'][$field->field]['write']) && !\Auth::user()->is_admin ? 0 : 1;
                    $table_columns[$field->field]['set_color'] = $field->set_color;
                    $table_columns[$field->field]['color'] = $field->label_color;
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = array_values($field_values);
                    if($field->type == 'status' && $field->details) {
                        $details = json_decode($field->details, true);
                        if(isset($details['can_create'])) {
                            $table_columns[$field->field]['can_create'] = $details['can_create'] ? 1 : 0;
                        }
                    }
                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        $table_columns[$field->field]['can_create'] = 1;
                        if($field->relation_table && isset($settings['models'][$field->relation_table]) && isset($permissions_all[$settings['models'][$field->relation_table]->id]['create_p']) && !\Auth::user()->is_admin)
                            $table_columns[$field->field]['can_create'] = $permissions_all[$settings['models'][$field->relation_table]->id]['create_p'] == 'N' ? 0 : 1;


                        if($field->relation_table && isset($restrictions_tariff['objects'][$field->relation_table]['count'])) {
                            if($restrictions_tariff['objects'][$field->relation_table]['count'] <= \DB::table($field->relation_table)->whereNull('deleted_at')->count()) {
                                $table_columns[$field->field]['can_create'] = 0;
                            };
                        }


                    }
                }
                
            }
            
        }

        if($slug == 'balance_operations') {
            unset($table_columns['isChoose']);
            unset($table_columns['actions']);
        }
        
        
        $table_columns = array_values($table_columns);

        return $table_columns;
    }

    private static function ndsColumn(string $key, string $title, string $type, int $index): array
    {
        $column = array(
            'id' => null,
            'title' => $title,
            'key' => $key,
            'width' => $key == 'product_nds' ? '150px' : '130px',
            'enabled' => 1,
            'sort_order' => '',
            'type' => $type,
            'fixed' => '',
            'index' => $index,
            'fixTarget' => '0px',
            'read_only' => 0,
            "mask" => "",
            'is_another_title' => 0
        );
        if ($type == 'select_dropdown') {
            $column['options'] = array_map(
                fn ($option) => array('label' => $option['label'], 'value' => $option['value']),
                \App\Console\Commands\InstallProductNdsFields::NDS_OPTIONS
            );
            $column['is_plural'] = 0;
        }

        return $column;
    }

    private static function shippedColumn(int $index): array
    {
        return array(
            'id' => null,
            'title' => 'Отгружено',
            'key' => 'product_shipped',
            'width' => '200px',
            'enabled' => 1,
            'sort_order' => '',
            'type' => 'number',
            'fixed' => '',
            'index' => $index,
            'fixTarget' => '0px',
            'read_only' => 1,
            "mask" => "",
            'is_another_title' => 0
        );
    }

    public static function get_order_products($parentSlug = null)
    {
        $user = \Auth::user();
        if(!$user)
            $user = \App\Models\User::find(1); 
        $tables = $user->tables;
        if($tables)
            $tables = json_decode($tables, true);
        $settings = app('settings');//get_settings();
        $options = array();
        $items = Product::orderBy('choosed_at', 'DESC')->orderBy('name', 'ASC')->whereNull('deleted_at')->limit(10)->get();
        foreach($items as $item) {
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
            $option = array(
                'label' => [
                    'id' => $item->id,
                    'text' => $name,
                    'color' => isset($item->color) ? $item->color : '',
                    'file' => $photo,
                    'count' => $item->quantity,
                    'weight' => $item->weight,
                    'volume' => $item->volume ?? null,
                    'price' => $item->price
                ],
                'value' => $item->id
            );
            foreach($settings['products']['fields'] as $field) {
                if($field->field == 'name')
                    continue;
                if($field->type == 'relation' && $field->relation_table) {
                    if($field->is_plural)
                        $option['label'][$field->field]['value'] = $item->{$field->relation_table} ? $item->{$field->relation_table}->pluck('id')->toArray() : array();
                    else
                        $option['label'][$field->field]['value'] = $item->{$field->field} ? array($item->{$field->field}) : array();
                    $option['label'][$field->field]['localOptions'] =array_values($settings['list_values'][$field->id]);
                } elseif($field->type == 'date') {
                    $option['label'][$field->field] = \Carbon\Carbon::parse($item->{$field->field})->format('Y-m-d H:i:s');
                } else {
                    $option['label'][$field->field] = $item->{$field->field};
                }
            }
            $options[] = $option;
        }
        if(isset($tables['order_products'])) {
            //$entity = \DB::table('data_types')->where('slug', 'remnants')->first();
            $entity = \DB::table('data_types')->where('slug', 'products')->first();
            $entity_class = $entity->model_name;
            $model_fields = collect($settings['products']['fields']);
            $table_columns = collect($tables['order_products']['fields']);
            $table_columns = $table_columns->keyBy('key')->toArray();
            foreach($table_columns as $key => $column) {
                if(!$model_fields->contains('field', $key) && $key != 'isChoose' && $key != 'actions' && $key != 'remnant_name' && $key != 'product_name' && $key != 'product_id' && $key != 'product_price' && $key != 'product_count' && $key != 'product_weight' && $key != 'product_volume' && $key != 'product_sum' && $key != 'product_shipped' && $key != 'product_nds' && $key != 'product_nds_included' && $key != 'iconDrag' || $key == 'price' || $key == 'name' || $key == 'weight' || $key == 'volume' || $key == 'price')
                    unset($table_columns[$key]);
            }
            unset($table_columns['iconDelete']);
            foreach ($model_fields as $field) {
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->field != 'price' && $field->field != 'name' && $field->field != 'weight' && $field->field != 'volume' && $field->field != 'price') {
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_parent_name ? $field->display_parent_name.', '.$field->title : $field->title,
                        'key' => $field->field,
                        'width' => '200px',
                        'enabled' => 0,
                        'sort_order' => '',
                        'type' => $field->type,
                        'fixed' => '',
                        'index' => count($table_columns) + 1,
                        'fixTarget' => '0px',
                        'read_only' => 1,//$field->only_read,
                        'unit' => $field->unit,
                        "mask" => "",
                        'is_another_title' => 0
                    );
                    if(in_array($field->type, ['select_dropdown', 'status']) && isset($settings['list_values'][$field->id]))
                        $table_columns[$field->field]['options'] = array_values($settings['list_values'][$field->id]);
                } elseif(array_key_exists($field->field, $table_columns)) {
                    $table_columns[$field->field]['read_only'] = 1;
                    if(in_array($field->type, ['select_dropdown', 'status']) && !isset($table_columns[$field->field]['options']) && isset($settings['list_values'][$field->id]))
                        $table_columns[$field->field]['options'] = array_values($settings['list_values'][$field->id]);
                }
            }
            if(!isset($table_columns['product_id'])) {
                $table_columns['product_id'] = array(
                    'id' => null,
                    'title' => 'Наименование товара',
                    'key' => 'product_id',
                    'width' => '200px',
                    'enabled' => 1,
                    'sort_order' => '',
                    'type' => 'relation',
                    'fixed' => '',
                    'index' => 0,
                    'fixTarget' => '0px',
                    'read_only' => 0,
                    'related_table' => 'products',
                    'options' => $options,
                    "mask" => "",
                    'is_another_title' => 1
                );
            } else {
                $table_columns['product_id']['type'] = 'relation';
                $table_columns['product_id']['related_table'] = 'products';
                $table_columns['product_id']['options'] = $options;
            }
            // if(!isset($table_columns['product_name']))
            //     $table_columns['product_name'] = array(
            //         'id' => null,
            //         'title' => 'Наименование товара',
            //         'key' => 'product_name',
            //         'width' => '200px',
            //         'enabled' => 1,
            //         'sort_order' => '',
            //         'type' => 'relation',
            //         'fixed' => '',
            //         'index' => 0,
            //         'fixTarget' => '0px',
            //         'read_only' => 0,
            //         'related_table' => 'products',
            //         'options' => [],
            //         "mask" => ""
            //     );
            if(!isset($table_columns['product_price']))
                $table_columns['product_price'] = array(
                    'id' => null,
                    'title' => 'Цена',
                    'key' => 'product_price',
                    'width' => '200px',
                    'enabled' => 1,
                    'sort_order' => '',
                    'type' => 'number',
                    'fixed' => '',
                    'index' => 1,
                    'fixTarget' => '0px',
                    'read_only' => 0,
                    "mask" => "",
                    'is_another_title' => 0
                );
            if(!isset($table_columns['product_count']))
                $table_columns['product_count'] = array(
                    'id' => null,
                    'title' => 'Кол-во',
                    'key' => 'product_count',
                    'width' => '200px',
                    'enabled' => 1,
                    'sort_order' => '',
                    'type' => 'number',
                    'fixed' => '',
                    'index' => 2,
                    'fixTarget' => '0px',
                    'read_only' => 0,
                    "mask" => "",
                    'is_another_title' => 0
                );
            if(!isset($table_columns['product_weight']))
                $table_columns['product_weight'] = array(
                    'id' => null,
                    'title' => 'Вес, кг',
                    'key' => 'product_weight',
                    'width' => '200px',
                    'enabled' => 1,
                    'sort_order' => '',
                    'type' => 'number',
                    'fixed' => '',
                    'index' => 3,
                    'fixTarget' => '0px',
                    'read_only' => 1,
                    "mask" => "",
                    'is_another_title' => 0
                );
            if(!isset($table_columns['product_volume']))
                $table_columns['product_volume'] = array(
                    'id' => null,
                    'title' => 'Объем, л',
                    'key' => 'product_volume',
                    'width' => '200px',
                    'enabled' => 1,
                    'sort_order' => '',
                    'type' => 'number',
                    'fixed' => '',
                    'index' => 4,
                    'fixTarget' => '0px',
                    'read_only' => 1,
                    "mask" => "",
                    'is_another_title' => 0
                );
            if(!isset($table_columns['product_sum']))
                $table_columns['product_sum'] = array(
                    'id' => null,
                    'title' => 'Сумма',
                    'key' => 'product_sum',
                    'width' => '200px',
                    'enabled' => 1,
                    'sort_order' => '',
                    'type' => 'number',
                    'fixed' => '',
                    'index' => 5,
                    'fixTarget' => '0px',
                    'read_only' => 1,
                    "mask" => "",
                    'is_another_title' => 0
                );
            if(\App\Services\ShipmentService::hasShippedColumn((string) $parentSlug) && !isset($table_columns['product_shipped']))
                $table_columns['product_shipped'] = self::shippedColumn(count($table_columns));
            if(!\App\Services\ShipmentService::hasShippedColumn((string) $parentSlug))
                unset($table_columns['product_shipped']);
            if(!isset($table_columns['product_nds']))
                $table_columns['product_nds'] = self::ndsColumn('product_nds', 'НДС', 'select_dropdown', count($table_columns));
            if(!isset($table_columns['product_nds_included']))
                $table_columns['product_nds_included'] = self::ndsColumn('product_nds_included', 'Включать НДС', 'checkbox', count($table_columns));
            // if(!isset($table_columns['isChoose'])) {
            //     $table_columns['isChoose'] = array(
            //         "id" => 0,
            //         "title" => "Выделение",
            //         "key" => "isChoose",
            //         "width" => "40.00px",
            //         "enabled" => true,
            //         "hover" => false,
            //         "sort_order" => null,
            //         "type" => "checkbox",
            //         "fixed" => true,
            //         "fixTarget" => "0px",
            //         "index" => 0
            //     );
            // }
            // if(!isset($table_columns['actions'])) {
            //     $table_columns['actions'] = array(
            //         "id" => 2,
            //         "title" => "Действие",
            //         "key" => "actions",
            //         "width" => "40.00px",
            //         "enabled" => true,
            //         "hover" => false,
            //         "sort_order" => null,
            //         "type" => "actions",
            //         "fixed" => true,
            //         "index" => 1,
            //         "fixTarget" => "40px"
            //     );
            // }
            if(!isset($table_columns['iconDrag'])) {
                $table_columns['iconDrag'] = array(
                    "id" => null,
                    "title" => "Перетаскивание",
                    "key" => "iconDrag",
                    "width" => "40px",
                    "enabled" => 1,
                    "sort_order" => "",
                    "type" => "iconDrag",
                    "fixed" => "",
                    "index" => 1,
                    "fixTarget" => "0px",
                    "read_only" => 1,
                    "mask" => "",
                    'is_another_title' => 0
                );
            }
            // Старая колонка iconDelete заменена на полноценную колонку «Действие» (actions)
            // с двумя пунктами — Посмотреть и Удалить, как в остальных таблицах.
            unset($table_columns['iconDelete']);
            $savedActions = isset($table_columns['actions']) && is_array($table_columns['actions']) ? $table_columns['actions'] : null;
            $table_columns['actions'] = array(
                "id" => 2,
                "title" => "Действие",
                "key" => "actions",
                "width" => "57px",
                "enabled" => $savedActions !== null && array_key_exists('enabled', $savedActions) ? ($savedActions['enabled'] ? 1 : 0) : 1,
                "hover" => false,
                "sort_order" => null,
                "type" => "actions",
                "fixed" => true,
                "index" => 1,
                "fixTarget" => "40px",
                "read_only" => 1,
                "mask" => "",
                'is_another_title' => 0
            );
            $table_columns = array_values($table_columns);

            
        } else {
            $entity = \DB::table('data_types')->where('slug', 'products')->first();
            $entity_class = $entity->model_name;
            $model_fields = collect($settings['products']['fields']);
            $table_columns = array();
            // $table_columns['remnant_name'] = array(
            //     'id' => null,
            //     'title' => 'Наименование единицы',
            //     'key' => 'remnant_name',
            //     'width' => '200px',
            //     'enabled' => 1,
            //     'sort_order' => '',
            //     'type' => 'text',
            //     'fixed' => '',
            //     'index' => 0,
            //     'fixTarget' => '0px',
            //     'read_only' => 0
            // );
            $table_columns['product_id'] = array(
                'id' => null,
                'title' => 'Наименование товара',
                'key' => 'product_id',
                'width' => '200px',
                'enabled' => 1,
                'sort_order' => '',
                'type' => 'relation',
                'fixed' => '',
                'index' => 0,
                'fixTarget' => '0px',
                'read_only' => 0,
                'related_table' => 'products',
                'options' => $options,
                "mask" => "",
                'is_another_title' => 1
            );
            $table_columns['product_price'] = array(
                'id' => null,
                'title' => 'Цена',
                'key' => 'product_price',
                'width' => '200px',
                'enabled' => 1,
                'sort_order' => '',
                'type' => 'number',
                'fixed' => '',
                'index' => 1,
                'fixTarget' => '0px',
                'read_only' => 0,
                "mask" => "",
                'is_another_title' => 0
            );
            $table_columns['product_count'] = array(
                'id' => null,
                'title' => 'Кол-во',
                'key' => 'product_count',
                'width' => '200px',
                'enabled' => 1,
                'sort_order' => '',
                'type' => 'number',
                'fixed' => '',
                'index' => 2,
                'fixTarget' => '0px',
                'read_only' => 0,
                "mask" => "",
                'is_another_title' => 0
            );
            $table_columns['product_weight'] = array(
                'id' => null,
                'title' => 'Вес, кг',
                'key' => 'product_weight',
                'width' => '200px',
                'enabled' => 1,
                'sort_order' => '',
                'type' => 'number',
                'fixed' => '',
                'index' => 3,
                'fixTarget' => '0px',
                'read_only' => 1,
                "mask" => "",
                'is_another_title' => 0
            );
            $table_columns['product_volume'] = array(
                'id' => null,
                'title' => 'Объем, л',
                'key' => 'product_volume',
                'width' => '200px',
                'enabled' => 1,
                'sort_order' => '',
                'type' => 'number',
                'fixed' => '',
                'index' => 4,
                'fixTarget' => '0px',
                'read_only' => 1,
                "mask" => "",
                'is_another_title' => 0
            );
            $table_columns['product_sum'] = array(
                'id' => null,
                'title' => 'Сумма',
                'key' => 'product_sum',
                'width' => '200px',
                'enabled' => 1,
                'sort_order' => '',
                'type' => 'number',
                'fixed' => '',
                'index' => 5,
                'fixTarget' => '0px',
                'read_only' => 1,
                "mask" => "",
                'is_another_title' => 0
            );
            if(\App\Services\ShipmentService::hasShippedColumn((string) $parentSlug))
                $table_columns['product_shipped'] = self::shippedColumn(6);
            $table_columns['product_nds'] = self::ndsColumn('product_nds', 'НДС', 'select_dropdown', 7);
            $table_columns['product_nds_included'] = self::ndsColumn('product_nds_included', 'Включать НДС', 'checkbox', 8);

            if(!isset($table_columns['iconDrag'])) {
                $table_columns['isChoose'] = array(
                    "id" => null,
                    "title" => "Перетаскивание",
                    "key" => "iconDrag",
                    "width" => "40px",
                    "enabled" => 1,
                    "sort_order" => "",
                    "type" => "iconDrag",
                    "fixed" => "",
                    "index" => 1,
                    "fixTarget" => "0px",
                    "read_only" => 1,
                    "mask" => "",
                    'is_another_title' => 0
                );
            }
            if(!isset($table_columns['iconDelete'])) {
                $table_columns['actions'] = array(
                    "id" => null,
                    "title" => "Удаление",
                    "key" => "iconDelete",
                    "width" => "40px",
                    "enabled" => 1,
                    "sort_order" => "",
                    "type" => "iconDelete",
                    "fixed" => "",
                    "index" => 1,
                    "fixTarget" => "0px",
                    "read_only" => 1,
                    "mask" => "",
                    'is_another_title' => 0
                );
            }
            foreach ($model_fields as $field) {
                $field_values = array();
                $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
                if(isset($settings['list_values'][$field->id])) {
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true); 
                    } else {
                        $field_values = $settings['list_values'][$field->id];
                    }
                };
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->type != 'password' && $field->field != 'name' &&  $field->field != 'weight' && $field->field != 'volume' && $field->field != 'price') {
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_parent_name ? $field->display_parent_name.', '.$field->title : $field->title,
                        'key' => $field->field,
                        'width' => '200px',
                        'enabled' => ($field->is_default ? true : false),
                        'sort_order' => ($field->field == 'id' ? 'desc':''),
                        'type' => $field->type,
                        'is_plural' => ($field->type == 'text' ? 1 : $field->is_plural),
                        'external_link' => $field->external_link,
                        'is_external_link' => $field->is_external_link,
                        'is_link' => $field->is_link,
                        'required' => $field->required,
                        'fixed' => '',
                        'index' => count($table_columns) + 1,
                        'fixTarget' => '0px',
                        'read_only' => 1,//$field->only_read || !$settings['products']['perms'][$field->field]['write'] ? 1 : 0,
                        'unit' => $field->unit,
                        'mask' => $field->mask,
                        'is_another_title' => 0
                    );
                    $table_columns[$field->field]['type'] = $field->type;
                    $table_columns[$field->field]['read_only'] = $field->only_read;
                    $table_columns[$field->field]['can_read'] = 1;
                    $table_columns[$field->field]['can_edit'] = $field->only_read ? 0 : 1;
                    $table_columns[$field->field]['set_color'] = $field->set_color;
                    $table_columns[$field->field]['color'] = $field->label_color;//$field_colors[$field->field];
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = array_values($field_values);
                    if($field->type == 'status' && $field->details) {
                        $details = json_decode($field->details, true);
                        if(isset($details['can_create'])) {
                            $table_columns[$field->field]['can_create'] = $details['can_create'] ? 1 : 0;
                        }
                    }
                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        // if($field->field == 'category_id')
                        //     $table_columns[$field->field]['can_create'] = 0;
                        // else
                        $table_columns[$field->field]['can_create'] = 1;
                        if($field->relation_table && isset($settings['models'][$field->relation_table]) && isset($permissions_all[$settings['models'][$field->relation_table]->id]['create_p']) && !\Auth::user()->is_admin)
                            $table_columns[$field->field]['can_create'] = $permissions_all[$settings['models'][$field->relation_table]->id]['create_p'] == 'N' ? 0 : 1;


                    }
                }
                
            }

            

            //return array_values($table_columns);
        }

        

        $table_columns = array_values($table_columns);

        info('product table_columns');
        info($table_columns);

        return $table_columns;
    }

    public static function roles()
    {
        $table_columns = array(
            [
                'id' => 527,
                'title' => 'Сущность',
                'key' => 'name',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'text',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => true,
                'index' => 2,
                'fixTarget' => '0px',
                'read_only' => 1,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 1,
                'options' => []
            ],
            [
                'id' => 2149,
                'title' => 'Чтение',
                'key' => 'read_p',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'select_dropdown',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 6,
                'fixTarget' => '0px',
                'read_only' => 0,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 0,
                'options' => [
                    [
                        'value' => 'N',
                        'label' => 'Нет доступа',
                        'color' => '#ad0b0a',
                        'sort' => 0
                    ],
                    [
                        'value' => 'E',
                        'label' => 'Сотрудник',
                        'color' => '#000',
                        'sort' => 1
                    ],
                    [
                        'value' => 'Y',
                        'label' => 'Ответственный',
                        'color' => '#000',
                        'sort' => 2
                    ],
                    [
                        'value' => 'A',
                        'label' => 'Полный доступ',
                        'color' => '#3C7C2B',
                        'sort' => 3
                    ]
                ]
            ],
            [
                'id' => 2149,
                'title' => 'Добавление',
                'key' => 'create_p',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'select_dropdown',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 6,
                'fixTarget' => '0px',
                'read_only' => 0,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 0,
                'options' => [
                    [
                        'value' => 'N',
                        'label' => 'Нет доступа',
                        'color' => '#ad0b0a',
                        'sort' => 0
                    ],
                    // [
                    //     'value' => 'Y',
                    //     'label' => 'Только свои',
                    //     'sort' => 1
                    // ],
                    [
                        'value' => 'A',
                        'label' => 'Полный доступ',
                        'color' => '#3C7C2B',
                        'sort' => 2
                    ]
                ]
            ],
            [
                'id' => 2149,
                'title' => 'Изменение',
                'key' => 'update_p',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'select_dropdown',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 6,
                'fixTarget' => '0px',
                'read_only' => 0,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 0,
                'options' => [
                    [
                        'value' => 'N',
                        'label' => 'Нет доступа',
                        'color' => '#ad0b0a',
                        'sort' => 0
                    ],
                    [
                        'value' => 'E',
                        'label' => 'Сотрудник',
                        'color' => '#000',
                        'sort' => 1
                    ],
                    [
                        'value' => 'Y',
                        'label' => 'Ответственный',
                        'color' => '#000',
                        'sort' => 2
                    ],
                    [
                        'value' => 'A',
                        'label' => 'Полный доступ',
                        'color' => '#3C7C2B',
                        'sort' => 3
                    ]
                ]
            ],
            [
                'id' => 2149,
                'title' => 'Удаление',
                'key' => 'delete_p',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'select_dropdown',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 6,
                'fixTarget' => '0px',
                'read_only' => 0,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 0,
                'options' => [
                    [
                        'value' => 'N',
                        'label' => 'Нет доступа',
                        'color' => '#ad0b0a',
                        'sort' => 0
                    ],
                    [
                        'value' => 'Y',
                        'label' => 'Ответственный',
                        'color' => '#000',
                        'sort' => 1
                    ],
                    [
                        'value' => 'A',
                        'label' => 'Полный доступ',
                        'color' => '#3C7C2B',
                        'sort' => 2
                    ]
                ]
            ],
            [
                'id' => 2149,
                'title' => 'Экспорт',
                'key' => 'export_p',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'select_dropdown',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 6,
                'fixTarget' => '0px',
                'read_only' => 0,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 0,
                'options' => [
                    [
                        'value' => 'N',
                        'label' => 'Нет доступа',
                        'color' => '#ad0b0a',
                        'sort' => 0
                    ],
                    // [
                    //     'value' => 'Y',
                    //     'label' => 'Только свои',
                    //     'sort' => 1
                    // ],
                    [
                        'value' => 'A',
                        'label' => 'Полный доступ',
                        'color' => '#3C7C2B',
                        'sort' => 2
                    ]
                ]
            ]/*,
            [
                'id' => 2149,
                'title' => 'Импорт',
                'key' => 'import_p',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'select_dropdown',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 6,
                'fixTarget' => '0px',
                'read_only' => 0,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 0,
                'options' => [
                    [
                        'value' => 'N',
                        'label' => 'Нет доступа',
                        'color' => '#ad0b0a',
                        'sort' => 0
                    ],
                    [
                        'value' => 'Y',
                        'label' => 'Только свои',
                        'color' => '#000',
                        'sort' => 1
                    ],
                    [
                        'value' => 'A',
                        'label' => 'Полный доступ',
                        'color' => '#3C7C2B',
                        'sort' => 2
                    ]
                ]
            ]*/
        );
        

        return $table_columns;
    }

    public static function entities()
    {
        $table_columns = [
            [
                "id" => null,
                "title" => "Сущность",
                "key" => "name",
                "width" => "200px",
                "enabled" => true,
                "sort_order" => null,
                "type" => "text",
                "is_plural" => 0,
                "external_link" => "",
                "is_external_link" => 0,
                "is_link" => 0,
                "required" => 0,
                "fixed" => true,
                "index" => 2,
                "fixTarget" => "0px",
                "read_only" => 1,
                "unit" => "",
                "mask" => null,
                "can_edit" => 0,
                "color" => "#000",
                "is_hidden" => 0,
                "visible_always" => 1,
                "options" => []
            ],
            [
                "id" => 2149,
                "title" => "Статус",
                "key" => "enable",
                "width" => "200px",
                "enabled" => true,
                "sort_order" => null,
                "type" => "select_dropdown",
                "is_plural" => 0,
                "external_link" => "",
                "is_external_link" => 0,
                "is_link" => 0,
                "required" => 0,
                "fixed" => null,
                "index" => 6,
                "fixTarget" => "0px",
                "read_only" => 0,
                "unit" => "",
                "mask" => null,
                "can_edit" => 0,
                "color" => "#000",
                "have_null_option" => 0,
                "is_hidden" => 0,
                "visible_always" => 0,
                "options" => [
                    [
                        "value" => 1,
                        "label" => "Активный",
                        "sort" => 0
                    ],
                    [
                        "value" => 0,
                        "label" => "Выключен",
                        "sort" => 1
                    ]
                ]
            ]
        ];
        

        return $table_columns;
    }

    public static function balance()
    {
        $table_columns = array(
            [
                'id' => null,
                'title' => 'Дата операции',
                'key' => 'date',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'text',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 2,
                'fixTarget' => '0px',
                'read_only' => 1,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 1,
                'options' => []
            ],
            [
                'id' => null,
                'title' => 'Тип операции',
                'key' => 'type',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'text',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 2,
                'fixTarget' => '0px',
                'read_only' => 1,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 1,
                'options' => []
            ],
            [
                'id' => null,
                'title' => 'Баланс до списания',
                'key' => 'balance_before',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'text',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 2,
                'fixTarget' => '0px',
                'read_only' => 1,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 1,
                'options' => []
            ],
            [
                'id' => null,
                'title' => 'Сумма операции',
                'key' => 'sum',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'text',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 2,
                'fixTarget' => '0px',
                'read_only' => 1,
                'unit' => 'руб.',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 1,
                'options' => []
            ],
            [
                'id' => null,
                'title' => 'Комментарий',
                'key' => 'comment',
                'width' => '200px',
                'enabled' => true,
                'sort_order' => null,
                'type' => 'text',
                'is_plural' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'is_link' => 0,
                'required' => 0,
                'fixed' => null,
                'index' => 2,
                'fixTarget' => '0px',
                'read_only' => 1,
                'unit' => '',
                'mask' => null,
                'can_edit' => 0,
                'color' => '#000',
                'is_hidden' => 0,
                'visible_always' => 1,
                'options' => []
            ]
        );
        

        return $table_columns;
    }

}