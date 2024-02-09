<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FieldController extends Controller
{
	public function list($slug, Request $request)
    {
    	$settings = get_settings();

    	$entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];

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
                        $field_values = $settings[$slug]['list_values'][$field->field];
                    }
                    $data[$field->field]['options'] = $field_values;
                };
                if($field->type == 'relation') {
                    $data[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                }
            }
        }

        return response()->json($data);
    }

    public function status_store(Request $request) {
        if(!$request->field_id || !$request->color)
            return response()->json(['error' => 400]);
        $data = array(
            'sort' => $request->sort ?? 0,
            'file' => $request->file ?? null,
            'is_hidden' => 1,
            'field_id' => $request->field_id,
            'color' => $request->color,
            'value' => $request->text ?? '',
        );
        $field_id = \DB::table('field_values')->insertGetId($data);
        $data['id'] = $field_id;
        $data = array(
            'label' => array(
                'id' => $data['id'],
                'sort' => $data['sort'],
                'file' => $data['file'],
                'is_hidden' => $data['is_hidden'],
                'field_id' => $data['field_id'],
                'color' => $data['color'],
                'text' => $data['value']
            ),
            'value' => $data['id']
        );

        return response()->json($data);
        // id = value
        // sort // не важен, т.к. поле скрыто
        // file // у скрытых null
        // field_id // привязанность к определенному полю, я при запросе отправляю его тебе
        // text // у скрытых null
    }

    public function store(Request $request) {
        $row_type = \DB::table('data_types')->where('name', $request->model)->first();
        $field = \Str::slug($request->title, '_');
        // if (\DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'field' => $field])->exists())
        //     return 'error';
        
        $details = array();
        $type = $request->type;
        if($request->values) {
            foreach($request->values as $key => $item) {
                $details['options'][] = array(
                    'label' => $item['label'],
                    'value' => $item['value'],
                    'sort' => $item['sort']
                );
            }
        };

        

        //if (\DB::table('data_rows')->where('field', $field)->where('data_type_id', $row_type->id)->doesntExist()) {
            $max_sort = \DB::table('data_rows')->where('data_type_id', $row_type->id)->max('sort');
            $max_sort++;

            if($type == 'text_group') {
                
                $group_id = \DB::table('data_rows')->insertGetId([
                    'data_type_id' => $row_type->id,
                    'data_type_id' => $row_type->id,
                    'field' => $field,
                    'type' => $type,
                    'display_name' => $request->title,
                    'visible_always' => $request->visible_always,
                    'section_id' => $request->section_id ? $request->section_id : 0,
                    'hide' => $request->section_id == 0 ? 1 : 0,
                    'roles_read' => $request->has_roles_read ? $request->roles_read : '',
                    'roles_write' => $request->has_roles_write ? $request->roles_write : '',
                    'mobile_pages' => $request->show_in_mobile ? $request->mobile_pages : '',
                    'unit' => $request->unit ? $request->unit : '',
                    'sort' => $max_sort,
                    'is_external_link' => 0,
                    'external_link' => '',
                    'hide' => $request->section_id == 0 ? 1 : 0,
                ]);
                
                $field_group = \DB::table('data_rows')->where('id', $group_id)->first();
                \DB::table('data_rows')->where('id', $group_id)->update([
                    'field' => $field_group->field.'_'.$field_group->id,
                ]);
                $field = $field_group->field.'_'.$field_group->id;

                foreach($request->values as $value) {
                    //$arr_val = json_decode($value, true);
                    $field_slug = \Str::slug($value['label'], '_').'_'.$field_group->id;
                    if (\DB::table('data_rows')->where('field', $field_slug)->doesntExist()) {
                        \DB::table('data_rows')->insert([
                            'data_type_id' => $row_type->id,
                            'field' => $field_slug,
                            'type' => 'text',
                            'display_name' => $value['label'],
                            'display_parent_name' => $field_group->display_name,
                            'group_id' => $field_group->id,
                            'unit' => $request->unit ? $request->unit : '',
                        ]);
                        \Schema::table($request->model, function($table) use ($field_slug) {
                            $table->string($field_slug)->nullable();
                        });
                    }
                }
                $field_id = $group_id;
            } elseif($type == 'status') {
                if($request->hasFile('files'))
                    $files = $request->file('files');
                $field_id = \DB::table('data_rows')->insertGetId([
                    'data_type_id' => $row_type->id,
                    'field' => $field,
                    'type' => $type,
                    'display_name' => $request->title,
                    'visible_always' => $request->visible_always ?? 0,
                    'details' => '',
                    'label_color' => $request->set_color ? $request->color : '',
                    'section_id' => $request->section_id ? $request->section_id : 0,
                    'hide' => $request->section_id == 0 ? 1 : 0,
                    //'measure' => $request->measure ? $request->measure : 0,
                    'button_name' => $request->button_name ? $request->button_name : 0,
                    //'show_file_image' => $request->show_file_image ? $request->show_file_image : 0,
                    'is_plural' => $request->is_plural ? $request->is_plural : 0,
                    'required' => $request->required ? $request->required : 0,
                    'roles_read' => $request->has_roles_read ? $request->roles_read : '',
                    'roles_write' => $request->has_roles_write ? $request->roles_write : '',
                    'mobile_pages' => $request->show_in_mobile ? $request->mobile_pages : '',
                    'unit' => $request->unit ? $request->unit : '',
                    'sort' => $max_sort,
                    'is_external_link' => 0,
                    'external_link' => '',
                ]);
                \DB::table('data_rows')->where('field', $field)->update([
                    'field' => $field.'_'.$field_id,
                ]);
                $field = $field.'_'.$field_id;
                foreach($request->statuses as $key => $status) {
                    // $path = '';
                    // if(is_array($status['icon']) && isset($status['icon']['raw'])) {
                    //     $path = $status['icon']['raw']->store('/public/field_icons');
                    // }
                    \DB::table('field_values')->insert([
                        'field_id' => $field_id,
                        'color' => $status['color'],
                        'file' => $status['icon'],//$path,
                        'value' => $status['value'],
                        'is_hidden' => isset($status['is_hidden']) ? $status['is_hidden'] : 0,
                        'sort' => $key
                    ]);
                }

                
            } else {

                $field_id = \DB::table('data_rows')->insertGetId([
                    'data_type_id' => $row_type->id,
                    'field' => $field,
                    'type' => $type,
                    'display_name' => $request->title,
                    'visible_always' => $request->visible_always ?? 0,
                    'details' => $request->values ? json_encode($details, true) : '',
                    'label_color' => $request->set_color ? $request->color : '',
                    'section_id' => $request->section_id ? $request->section_id : 0,
                    'hide' => $request->section_id == 0 ? 1 : 0,
                    //'measure' => $request->measure ? $request->measure : 0,
                    'button_name' => $request->button_name ? $request->button_name : 0,
                    //'show_file_image' => $request->show_file_image ? $request->show_file_image : 0,
                    'is_plural' => $request->is_plural ? $request->is_plural : 0,
                    'required' => $request->required ? $request->required : 0,
                    'show_file_name' => $request->show_file_name ? $request->show_file_name : 0,
                    'roles_read' => $request->has_roles_read ? json_encode($request->roles_read, true) : '',
                    'roles_write' => $request->has_roles_write ? json_encode($request->roles_write, true) : '',
                    'mobile_pages' => $request->show_in_mobile ? json_encode($request->mobile_pages, true) : '',
                    'unit' => $request->unit ? $request->unit : '',
                    'sort' => $max_sort,
                    'is_external_link' => $request->is_external_link ? $request->is_external_link : 0,
                    'external_link' => $request->external_link ? $request->external_link : '',
                ]);
                \DB::table('data_rows')->where('field', $field)->update([
                    'field' => $field.'_'.$field_id,
                ]);
                $field = $field.'_'.$field_id;

                
            }
            if($type == 'file')
                \Schema::table($request->model, function($table) use ($field) {
                    $table->text($field)->nullable();
                });
            else {
                if($request->is_plural)
                    \Schema::table($request->model, function($table) use ($field) {
                        $table->text($field)->nullable();
                    });
                else
                    \Schema::table($request->model, function($table) use ($field) {
                        $table->string($field)->nullable();
                    });
            }

            \App\Models\Settings::clear_cache();
            //cache()->flush();
            $field = \DB::table('data_rows')->where('id', $field_id)->first();

            $settings = get_settings();

            $entity = $settings['models'][$request->model];
            $entity_class = $entity->model_name;
            $model_fields = $settings[$request->model]['fields'];//$entity_class::getFields();

            $field_colors = array();
            $perms = array(
                'read' => array(),
                'write' => array(),
            );

            foreach($model_fields as $f) {
                if($f->type == 'status')
                    $fields_values[$f->field] = \App\Models\Field::getStatusesVisible($f->id);
                $field_colors[$f->field] = $f->label_color ? $f->label_color : null;
            }

            $res = array(
                'id' => $field->id,
                'title' => $field->display_name,
                'key' => $field->field,
                'value' => '',
                'type' => $field->type,
                'visible_always' => $field->visible_always,
                'is_hidden' => $field->hide,
                'is_plural' => $field->is_plural,
                'show_file_name' => $field->show_file_name,
                'button_name' => $field->button_name,
                'is_external_link' => $field->is_external_link,
                'external_link' => $field->external_link,
                'required' => $field->required,
                'read_only' => $field->only_read,
                'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
                'color' => $field_colors[$field->field],
                'group_id' => $field->group_id,
                'unit' => $field->unit,
                'sort' => $field->sort,
                'mask' => $field->mask,
                'editableFields' => array(
                    'id' => $field->id,
                    'model' => $entity->slug,
                    'title' => $field->display_name,
                    'type' => $field->type,
                    'is_plural' => $field->is_plural,
                    'show_file_name' => $field->show_file_name,
                    'is_external_link' => $field->is_external_link,
                    'external_link' => $field->external_link,
                    'visible_always' => $field->visible_always,
                    'section_id' => $field->section_id,
                    'values' => array(
                        array('id' => 0, 'value' => '')
                    ),
                    'set_color' => ($field->label_color ? $field->label_color : 0),
                    'color' => ($field->label_color ? $field->label_color : ''),
                    'button_name' => $field->button_name,
                    'has_roles_read' => ($field->roles_read ? 1 : 0),
                    'has_roles_write' => ($field->roles_write ? 1 : 0),
                    'roles_read' => ($field->roles_read ? json_decode($field->roles_read, true) : array()),
                    'roles_write' => ($field->roles_write ? json_decode($field->roles_write, true) : array()),
                    'show_in_mobile' => ($field->mobile_pages ? 1 : 0),
                    'mobile_pages' => ($field->mobile_pages ? json_decode($field->mobile_pages, true) : array()),
                    'unit' => $field->unit,
                    'statuses' => array()
                )
            );
            if($field->type == 'text_group') {
                $subfields = \App\Models\Field::getByGroup($field->id);
                $values = array();
                $res['options'] = array();
                $res['options_key_value'] = array();
                foreach($subfields as $subfield) {
                    $res['options'][$subfield->id] = $subfield->display_name;
                    $res['options_key_value'][$subfield->id] = $subfield->display_name;
                    $values[] = array(
                        'id' => $subfield->id,
                        'value' => $subfield->display_name
                    );
                };
                $res['editableFields']['values'] = $values;
                foreach($subfields as $subfield) {
                    $res['subfields'][] = array(
                        'id' => $subfield->id,
                        'title' => $subfield->display_name,
                        'key' => $subfield->field,
                        'value' => '',
                        'type' => $subfield->type,
                        'visible_always' => $field->visible_always,
                        'is_hidden' => $field->hide,
                        'read_only' => $field->only_read,
                        'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
                        'color' => $field_colors[$field->field],
                        'group_id' => $subfield->group_id,
                        'unit' => $field->unit,
                        'sort' => $subfield->sort
                    );
                }
            }
            if(isset($settings[$entity->slug]['list_values'][$field->field])) {
                $values = array();
                $res['options'] = $settings[$entity->slug]['list_values'][$field->field];
                $res['options_key_value'] = $settings[$entity->slug]['list_values'][$field->field];
                if($field->type == 'status') {
                    $simple_options = array();
                    foreach($settings[$entity->slug]['list_values'][$field->field] as $k => $option) {
                        $simple_options[$k] = $option;
                        $values[] = $option;
                    }
                    $res['options_key_value'] = $simple_options;
                } else {
                    foreach($settings[$entity->slug]['list_values'][$field->field] as $k => $option) {
                        $simple_options[$k] = $option;
                        // $values[] = array(
                        //     'label' => [    
                        //         'id' => is_array($option) ? $option['value'] : $option,
                        //         'sort' => is_array($option) ? $option['sort'] : $option,
                        //         'file' => $avatar,
                        //         'is_hidden' => 0,
                        //         'field_id' => $field->id,
                        //         'color' => isset($current->color) && !$current->color ? $current->getColor() : ($current->color ?? ''),
                        //         'text' => is_array($option) ? $option['label'] : $option
                        //     ],
                        //     'value' => $k
                        // );
                        $values[] = $option;
                        // $values[] = array(
                        //     'value' => is_array($option) ? $option['value'] : $option,
                        //     'label' => is_array($option) ? $option['label'] : $option,
                        //     'sort' => is_array($option) ? $option['sort'] : $k
                        // );
                    }
                }
                $res['editableFields']['values'] = $values;
                if($field->type == 'status')
                    $res['editableFields']['statuses'] = $settings[$entity->slug]['list_values'][$field->field];
            }
            $res['user_id'] = \Auth::user()->id;
            \App\Events\FieldUpdated::dispatch('FieldCreated', $res);

            $now = Carbon::now();
            if(\DB::table('local_cache')->where(['url' => "fields/$request->model", 'user_id' => \Auth::user()->id])->exists())
                \DB::table('local_cache')->where(['url' => "fields/$request->model", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
            else
                \DB::table('local_cache')->insert(['url' => "fields/$request->model", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

            return response()->json($res);
        // } else {
        //     return false;
        // }
        
    }

    public function update($id, Request $request) {

        $field = \DB::table('data_rows')->where(['id' => $id])->first();
        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        if($request->section_id && $request->change_section) {
            if($request->sort)
                \DB::table('data_rows')->where(['id' => $id])->update(['section_id' => $request->section_id, 'sort' => $request->sort, 'hide' => 0]);
            else
                \DB::table('data_rows')->where(['id' => $id])->update(['section_id' => $request->section_id, 'hide' => 0]);

            \App\Models\Settings::clear_cache();
            //cache()->flush();
            $res = $request->all();

            // $settings = get_settings();

            
            // $entity_class = $entity->model_name;
            // $model_fields = $entity_class::getFields();

            // $field_colors = array();
            // $perms = array(
            //     'read' => array(),
            //     'write' => array(),
            // );

            // foreach($model_fields as $f) {
            //     if($f->type == 'status')
            //         $fields_values[$f->field] = \App\Models\Field::getStatusesVisible($f->id);
            //     $field_colors[$f->field] = $f->label_color ? $f->label_color : null;
            // }


            // $res = array(
            //     'id' => $field->id,
            //     'title' => $field->display_name,
            //     'key' => $field->field,
            //     'type' => $field->type,
            //     'visible_always' => $field->visible_always,
            //     'is_hidden' => 0,
            //     'is_plural' => $field->is_plural,
            //     'show_file_name' => $field->show_file_name,
            //     'is_external_link' => $field->is_external_link,
            //     'external_link' => $field->external_link,
            //     'required' => $field->required,
            //     'read_only' => $field->only_read,
            //     'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
            //     'color' => $field_colors[$field->field],
            //     'group_id' => $field->group_id,
            //     'sort' => $request->sort ? $request->sort : $field->sort,
            //     'button_name' => $field->button_name,
            //     'editableFields' => array(
            //         'id' => $field->id,
            //         'model' => $entity->slug,
            //         'name' => $field->display_name,
            //         'type' => $field->type,
            //         'is_plural' => $field->is_plural,
            //         'show_file_name' => $field->show_file_name,
            //         'is_external_link' => $field->is_external_link,
            //         'external_link' => $field->external_link,
            //         'visible_always' => $field->visible_always,
            //         'section_id' => $field->section_id,
            //         'values' => array(
            //             array('id' => 0, 'value' => '')
            //         ),
            //         'set_color' => ($field->label_color ? $field->label_color : 0),
            //         'label_color' => ($field->label_color ? $field->label_color : ''),
            //         'button_name' => $field->button_name,
            //         'has_roles_read' => ($field->roles_read ? 1 : 0),
            //         'has_roles_write' => ($field->roles_write ? 1 : 0),
            //         'roles_read' => ($field->roles_read ? json_decode($field->roles_read, true) : array()),
            //         'roles_write' => ($field->roles_write ? json_decode($field->roles_write, true) : array()),
            //         'show_in_mobile' => ($field->mobile_pages ? 1 : 0),
            //         'mobile_pages' => ($field->mobile_pages ? json_decode($field->mobile_pages, true) : array()),
            //         'statuses' => array()
            //     )
            // );
            // if(isset($settings[$entity->slug]['list_values'][$field->field])) {
            //     $values = array();
            //     $res['options'] = $settings[$entity->slug]['list_values'][$field->field];
            //     $res['options_key_value'] = $settings[$entity->slug]['list_values'][$field->field];
            //     if($field->type == 'status') {
            //         $simple_options = array();
            //         foreach($settings[$entity->slug]['list_values'][$field->field] as $option) {
            //             $simple_options[$option->id] = $option->value;
            //             $values[] = array(
            //                 'id' => $option->id,
            //                 'value' => $option->value
            //             );
            //         }
            //         $res['options_key_value'] = $simple_options;
            //     } else {
            //         foreach($settings[$entity->slug]['list_values'][$field->field] as $k => $option) {
            //             $simple_options[$k] = $option;
            //             $values[] = array(
            //                 'id' => $k,
            //                 'value' => is_array($option) ? $option['value'] : $option,
            //                 'sort' => is_array($option) ? $option['sort'] : $k
            //             );
            //         }
            //     }
            //     $res['editableFields']['values'] = $values;
            //     if($field->type == 'status')
            //         $res['editableFields']['statuses'] = $settings[$entity->slug]['list_values'][$field->field];
            // };
            // if($field->type == 'text_group') {
            //     $subfields = \App\Models\Field::getByGroup($field->id);
            //     $values = array();
            //     $res['options'] = array();
            //     $res['options_key_value'] = array();
            //     foreach($subfields as $subfield) {
            //         $res['options'][$subfield->id] = $subfield->display_name;
            //         $res['options_key_value'][$subfield->id] = $subfield->display_name;
            //         $values[] = array(
            //             'id' => $subfield->id,
            //             'value' => $subfield->display_name
            //         );
            //     };
            //     $res['editableFields']['values'] = $values;

            //     $res['subfields'] = array();
            //     foreach($subfields as $subfield) {
            //         $res['subfields'][] = array(
            //             'id' => $subfield->id,
            //             'title' => $subfield->display_name,
            //             'key' => $subfield->field,
            //             'value' => null,
            //             'type' => $subfield->type,
            //             'visible_always' => $field->visible_always,
            //             'is_hidden' => $field->hide,
            //             'is_plural' => $field->is_plural,
            //             'read_only' => $field->only_read,
            //             'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
            //             'color' => $field_colors[$field->field],
            //             'group_id' => $subfield->group_id,
            //             'sort' => $subfield->sort,

            //         );
            //     }
            // }
            $res['user_id'] = \Auth::user()->id;
            \App\Events\FieldUpdated::dispatch('FieldUpdated', $res);

            $now = Carbon::now();
            if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
                \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
            else
                \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

            return response()->json($res);

        }

        $data = array();
        $data['display_name'] = $request->title;
        $data['visible_always'] = $request->visible_always;
        //$data['show_file_image'] = $request->show_file_image ?? 0;
        if($request->rules)
            $data['rules'] = json_encode($request->rules, true);
        // if($field->type == 'multiple_checkbox' && !$request->is_plural) {
        //     $data['type'] = 'select_dropdown';
        // } elseif($field->type == 'select_dropdown' && $request->is_plural) {
        //     $data['type'] = 'multiple_checkbox';
        // }

        if($request->set_color)
            $data['label_color'] = $request->color;
        else
            $data['label_color'] = '';

        $details = array();
        info('REQ VALUES');
        if($request->values && $field->type != 'text_group' && $field->type != 'relation') {
            info($request->values);
            // $options = array();
            // if($field->details) {
            //     $field_details = json_decode($field->details, true);
            //     if(isset($field_details['options']))
            //         $options = $field_details['options'];
            // }
            foreach($request->values as $k => $item) {
                $details['options'][$item['value']] = array(
                    'value' => $item['value'],
                    'label' => $item['label'],
                    'sort' => $item['sort']
                );
            }
            
            $data['details'] = json_encode($details, true);
            info($data['details']);
        };
        //$data['is_plural'] = $request->is_plural ? $request->is_plural : 0;
        $data['roles_read'] = $request->has_roles_read ? $request->roles_read : '';
        $data['roles_write'] = $request->has_roles_write ? $request->roles_write : '';
        $data['mobile_pages'] = $request->show_in_mobile ? $request->mobile_pages : '';
        $data['button_name'] = $request->button_name ? $request->button_name : '';
        $data['show_file_name'] = $request->show_file_name ? $request->show_file_name : 0;
        $data['required'] = $request->required ? $request->required : 0;
        $data['is_external_link'] = $request->is_external_link ? $request->is_external_link : 0;
        $data['external_link'] = $request->external_link ? $request->external_link : '';
        $data['unit'] = $request->unit ? $request->unit : '';
        $data['section_id'] = $request->section_id ? $request->section_id : 0;
        if($request->section_id == 0) {
            $data['hide'] = 1;
        }
        $data['mask'] = $request->mask ? $request->mask : $field->mask;

        if($field->type == 'text_group') {
            $field_group = \DB::table('data_rows')->where('field', $field->field)->first();
            $fields_by_group = \DB::table('data_rows')->where('group_id', $field_group->id)->get();
            $fields_save = array();
            info('VALUES RE');
            info($request->values);
            foreach($request->values as $k => $item) {
                $field_slug = \Str::slug($item['label'], '_').'_'.$field_group->id;
                $fields_save[] = $item['value'];
                if (isset($item['new'])) {
                    $field_id = \DB::table('data_rows')->insertGetId([
                        'data_type_id' => $field_group->data_type_id,
                        'field' => $field_slug,
                        'type' => 'text',
                        'display_name' => $item['label'],
                        'display_parent_name' => $field_group->display_name,
                        'group_id' => $field_group->id,
                        'sort' => $item['sort']
                    ]);

                    $row_type = \DB::table('data_types')->where('id', $field_group->data_type_id)->first();
                    \Schema::table($row_type->name, function($table) use ($field_slug) {
                        $table->string($field_slug)->nullable();
                    });
                } else {
                    

                    \DB::table('data_rows')->where([
                        'id' => $item['value'],
                        'group_id' => $field_group->id
                    ])->update([
                        'display_name' => $item['label'],
                        'display_parent_name' => $data['display_name'],
                        'sort' => $item['sort']
                    ]);
                }
            }
            info('$fields_save');
            info($fields_save);
            foreach($fields_by_group as $field_r) {
                info('id '.$field_r->id);
                if(!in_array($field_r->id, $fields_save)) {
                    \DB::table('data_rows')->where('id', $field_r->id)->delete();
                    \Schema::table($entity->slug, function($table) use($field_r) {
                        if (\Schema::hasColumn($table->getTable(), $field_r->field)) {
                            $table->dropColumn($field_r->field);
                        }
                    });
                }
            }
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
        } elseif($field->type == 'status') {
            $files = $request->file('files');
            
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
            $field_values = \DB::table('field_values')->where('field_id', $field->id)->get()->keyBy('id')->toArray();
            info('STATUSES');
            info($request->statuses);
            foreach($request->statuses as $key => $status) {
                $path = '';
                // if($request->hasFile('files') && isset($files[$key])) {
                //     $path = $files[$key]->store('/public/field_icons');
                //     if($path)
                //         \DB::table('field_values')->where('id', $status['id'])->update([
                //             'file' => $path
                //         ]);
                // } else {
                //     \DB::table('field_values')->where('id', $status['id'])->update([
                //         'file' => ''
                //     ]);
                // }
                
                if($field_values && array_key_exists($status['value'], $field_values)) {
                    \DB::table('field_values')->where('id', $status['value'])->update([
                        'field_id' => $field->id,
                        'color' => $status['color'] ? $status['color'] : '',
                        'file' => $status['icon'],
                        'value' => $status['value'] ? $status['value'] : '',
                        'is_hidden' => isset($status['is_hidden']) ? $status['is_hidden'] : 0,
                        'sort' => $status['sort']
                    ]);
                    // if($request->file_remove_values[$key] == 1)
                    //     \DB::table('field_values')->where('id', $request->value_ids[$key])->update([
                    //         'file' => ''
                    //     ]);
                } else {
                    \DB::table('field_values')->insert([
                        'field_id' => $field->id,
                        'color' => $status['color'] ? $status['color'] : '',
                        'file' => $status['icon'],//$path,
                        'value' => $status['value'] ? $status['value'] : '',
                        'is_hidden' => isset($status['is_hidden']) ? $status['is_hidden'] : 0,
                        'sort' => $status['sort']
                    ]);
                }
                if($status['icon'] && isset($status['file_changed']) && $status['file_changed']) {
                    //$path = $status['icon']['raw']->store('/public/field_icons');
                    //if($path)
                        \DB::table('field_values')->where('id', $status['value'])->update([
                            'file' => $status['icon']
                        ]);
                } elseif(!$status['icon']) {
                    \DB::table('field_values')->where('id', $status['value'])->update([
                        'file' => ''
                    ]);
                }
                if($field_values && array_key_exists($status['value'], $field_values)) {
                    unset($field_values[$status['value']]);
                }
            }
            if(count($field_values)) {
                //удаляем значения,которых не было в переданных
                \DB::table('field_values')->whereIn('id', array_keys($field_values))/*->where('is_hidden', 0)*/->delete();
            }
        } else {
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
        }
        \App\Models\Settings::clear_cache();
        if($field->type == 'status') {
            $keys = cache()->getMemcached()->getAllKeys();
            $regex = tenant('id').':field-'.$field->id.'-statuses';
            foreach($keys as $item) {
                if(preg_match('/'.$regex.'/', $item)) {
                    cache()->getMemcached()->delete($item);
                }
            }
        }
        //cache()->flush();

        $field = \DB::table('data_rows')->where(['id' => $field->id])->first();

        $settings = get_settings();

        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        $entity_class = $entity->model_name;
        $model_fields = $settings[$entity->name]['fields'];

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $f) {
            if($f->type == 'status')
                $fields_values[$f->field] = \App\Models\Field::getStatusesVisible($f->id);
            $field_colors[$f->field] = $f->label_color ? $f->label_color : null;
        }
        
        $res = array(
            'editableFields' => array(
                'id' => $field->id,
                'model' => $entity->slug,
                'title' => $field->display_name,
                'type' => $field->type,
                'is_plural' => $field->is_plural,
                'show_file_name' => $field->show_file_name,
                'is_external_link' => $field->is_external_link,
                'external_link' => $field->external_link,
                'required' => $field->required,
                'visible_always' => $field->visible_always,
                'section_id' => $field->section_id,
                'values' => array(
                    array('value' => 0, 'label' => '')
                ),
                'set_color' => ($field->label_color ? $field->label_color : 0),
                'color' => ($field->label_color ? $field->label_color : ''),
                'button_name' => $field->button_name,
                'has_roles_read' => ($field->roles_read ? 1 : 0),
                'has_roles_write' => ($field->roles_write ? 1 : 0),
                'roles_read' => ($field->roles_read ? json_decode($field->roles_read, true) : array()),
                'roles_write' => ($field->roles_write ? json_decode($field->roles_write, true) : array()),
                'show_in_mobile' => ($field->mobile_pages ? 1 : 0),
                'mobile_pages' => ($field->mobile_pages ? json_decode($field->mobile_pages, true) : array()),
                'unit' => $field->unit,
                'statuses' => array()
            )
        );
        if(isset($settings[$entity->slug]['list_values'][$field->field]) && $field->type != 'relation') {
            $values = array();
            if($field->type == 'status') {
                $simple_options = array();
                foreach($settings[$entity->slug]['list_values'][$field->field] as $k => $option) {
                    $simple_options[$k] = $option;
                    $values[] = $option;
                }
            } else {
                foreach($settings[$entity->slug]['list_values'][$field->field] as $k => $option) {
                    $simple_options[$k] = $option;
                    $values[] = $option;
                }
            }
            $res['editableFields']['values'] = $values;
            if($field->type == 'status')
                $res['editableFields']['statuses'] = $settings[$entity->slug]['list_values'][$field->field];
        };
        if($field->type == 'text_group') {
            $subfields = \App\Models\Field::getByGroup($field->id);
            $values = array();
            foreach($subfields as $subfield) {
                $values[] = array(
                    'value' => $subfield->id,
                    'label' => $subfield->display_name,
                    'sort' => $subfield->sort
                );
            };
            $res['editableFields']['values'] = $values;

        }
        $res['user_id'] = \Auth::user()->id;
        \App\Events\FieldUpdated::dispatch('FieldUpdated', $res);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        info($res);

        return response()->json($res);
    }

    public function old_update($id, Request $request) {

        $field = \DB::table('data_rows')->where(['id' => $id])->first();
        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();

        if($request->section_id && $request->change_section) {
            if($request->sort)
                \DB::table('data_rows')->where(['id' => $id])->update(['section_id' => $request->section_id, 'sort' => $request->sort, 'hide' => 0]);
            else
                \DB::table('data_rows')->where(['id' => $id])->update(['section_id' => $request->section_id, 'hide' => 0]);

            \App\Models\Settings::clear_cache();
            //cache()->flush();
            $res = $request->all();

            // $settings = get_settings();

            
            // $entity_class = $entity->model_name;
            // $model_fields = $entity_class::getFields();

            // $field_colors = array();
            // $perms = array(
            //     'read' => array(),
            //     'write' => array(),
            // );

            // foreach($model_fields as $f) {
            //     if($f->type == 'status')
            //         $fields_values[$f->field] = \App\Models\Field::getStatusesVisible($f->id);
            //     $field_colors[$f->field] = $f->label_color ? $f->label_color : null;
            // }


            // $res = array(
            //     'id' => $field->id,
            //     'title' => $field->display_name,
            //     'key' => $field->field,
            //     'type' => $field->type,
            //     'visible_always' => $field->visible_always,
            //     'is_hidden' => 0,
            //     'is_plural' => $field->is_plural,
            //     'show_file_name' => $field->show_file_name,
            //     'is_external_link' => $field->is_external_link,
            //     'external_link' => $field->external_link,
            //     'required' => $field->required,
            //     'read_only' => $field->only_read,
            //     'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
            //     'color' => $field_colors[$field->field],
            //     'group_id' => $field->group_id,
            //     'sort' => $request->sort ? $request->sort : $field->sort,
            //     'button_name' => $field->button_name,
            //     'editableFields' => array(
            //         'id' => $field->id,
            //         'model' => $entity->slug,
            //         'name' => $field->display_name,
            //         'type' => $field->type,
            //         'is_plural' => $field->is_plural,
            //         'show_file_name' => $field->show_file_name,
            //         'is_external_link' => $field->is_external_link,
            //         'external_link' => $field->external_link,
            //         'visible_always' => $field->visible_always,
            //         'section_id' => $field->section_id,
            //         'values' => array(
            //             array('id' => 0, 'value' => '')
            //         ),
            //         'set_color' => ($field->label_color ? $field->label_color : 0),
            //         'label_color' => ($field->label_color ? $field->label_color : ''),
            //         'button_name' => $field->button_name,
            //         'has_roles_read' => ($field->roles_read ? 1 : 0),
            //         'has_roles_write' => ($field->roles_write ? 1 : 0),
            //         'roles_read' => ($field->roles_read ? json_decode($field->roles_read, true) : array()),
            //         'roles_write' => ($field->roles_write ? json_decode($field->roles_write, true) : array()),
            //         'show_in_mobile' => ($field->mobile_pages ? 1 : 0),
            //         'mobile_pages' => ($field->mobile_pages ? json_decode($field->mobile_pages, true) : array()),
            //         'statuses' => array()
            //     )
            // );
            // if(isset($settings[$entity->slug]['list_values'][$field->field])) {
            //     $values = array();
            //     $res['options'] = $settings[$entity->slug]['list_values'][$field->field];
            //     $res['options_key_value'] = $settings[$entity->slug]['list_values'][$field->field];
            //     if($field->type == 'status') {
            //         $simple_options = array();
            //         foreach($settings[$entity->slug]['list_values'][$field->field] as $option) {
            //             $simple_options[$option->id] = $option->value;
            //             $values[] = array(
            //                 'id' => $option->id,
            //                 'value' => $option->value
            //             );
            //         }
            //         $res['options_key_value'] = $simple_options;
            //     } else {
            //         foreach($settings[$entity->slug]['list_values'][$field->field] as $k => $option) {
            //             $simple_options[$k] = $option;
            //             $values[] = array(
            //                 'id' => $k,
            //                 'value' => is_array($option) ? $option['value'] : $option,
            //                 'sort' => is_array($option) ? $option['sort'] : $k
            //             );
            //         }
            //     }
            //     $res['editableFields']['values'] = $values;
            //     if($field->type == 'status')
            //         $res['editableFields']['statuses'] = $settings[$entity->slug]['list_values'][$field->field];
            // };
            // if($field->type == 'text_group') {
            //     $subfields = \App\Models\Field::getByGroup($field->id);
            //     $values = array();
            //     $res['options'] = array();
            //     $res['options_key_value'] = array();
            //     foreach($subfields as $subfield) {
            //         $res['options'][$subfield->id] = $subfield->display_name;
            //         $res['options_key_value'][$subfield->id] = $subfield->display_name;
            //         $values[] = array(
            //             'id' => $subfield->id,
            //             'value' => $subfield->display_name
            //         );
            //     };
            //     $res['editableFields']['values'] = $values;

            //     $res['subfields'] = array();
            //     foreach($subfields as $subfield) {
            //         $res['subfields'][] = array(
            //             'id' => $subfield->id,
            //             'title' => $subfield->display_name,
            //             'key' => $subfield->field,
            //             'value' => null,
            //             'type' => $subfield->type,
            //             'visible_always' => $field->visible_always,
            //             'is_hidden' => $field->hide,
            //             'is_plural' => $field->is_plural,
            //             'read_only' => $field->only_read,
            //             'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
            //             'color' => $field_colors[$field->field],
            //             'group_id' => $subfield->group_id,
            //             'sort' => $subfield->sort,

            //         );
            //     }
            // }
            $res['user_id'] = \Auth::user()->id;
            \App\Events\FieldUpdated::dispatch('FieldUpdated', $res);

            $now = Carbon::now();
            if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
                \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
            else
                \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

            return response()->json($res);

        }

        $data = array();
        $data['display_name'] = $request->title;
        $data['visible_always'] = $request->visible_always;
        //$data['show_file_image'] = $request->show_file_image ?? 0;
        if($request->rules)
            $data['rules'] = json_encode($request->rules, true);
        // if($field->type == 'multiple_checkbox' && !$request->is_plural) {
        //     $data['type'] = 'select_dropdown';
        // } elseif($field->type == 'select_dropdown' && $request->is_plural) {
        //     $data['type'] = 'multiple_checkbox';
        // }

        if($request->set_color)
            $data['label_color'] = $request->color;
        else
            $data['label_color'] = '';

        $details = array();

        if($request->values && $field->type != 'text_group' && $field->type != 'relation') {
            $options = array();
            if($field->details) {
                $field_details = json_decode($field->details, true);
                if(isset($field_details['options']))
                    $options = $field_details['options'];
            }
            foreach($request->values as $k => $item) {
                $details['options'][$item['value']] = array(
                    'value' => $item['value'],
                    'label' => $item['label'],
                    'sort' => $item['sort']
                );
            }
            
            $data['details'] = json_encode($details, true);
        };
        //$data['is_plural'] = $request->is_plural ? $request->is_plural : 0;
        $data['roles_read'] = $request->has_roles_read ? $request->roles_read : '';
        $data['roles_write'] = $request->has_roles_write ? $request->roles_write : '';
        $data['mobile_pages'] = $request->show_in_mobile ? $request->mobile_pages : '';
        $data['button_name'] = $request->button_name ? $request->button_name : '';
        $data['show_file_name'] = $request->show_file_name ? $request->show_file_name : 0;
        $data['required'] = $request->required ? $request->required : 0;
        $data['is_external_link'] = $request->is_external_link ? $request->is_external_link : 0;
        $data['external_link'] = $request->external_link ? $request->external_link : '';
        $data['unit'] = $request->unit ? $request->unit : '';
        $data['section_id'] = $request->section_id ? $request->section_id : 0;
        if($request->section_id == 0) {
            $data['hide'] = 1;
        }
        $data['mask'] = $request->mask ? $request->mask : $field->mask;

        if($field->type == 'text_group') {
            $field_group = \DB::table('data_rows')->where('field', $field->field)->first();
            $fields_by_group = \DB::table('data_rows')->where('group_id', $field_group->id)->get();
            $fields_save = array();
            info('VALUES RE');
            info($request->values);
            foreach($request->values as $k => $item) {
                $field_slug = \Str::slug($item['label'], '_').'_'.$field_group->id;
                $fields_save[] = $item['value'];
                if (isset($item['new'])) {
                    $field_id = \DB::table('data_rows')->insertGetId([
                        'data_type_id' => $field_group->data_type_id,
                        'field' => $field_slug,
                        'type' => 'text',
                        'display_name' => $item['label'],
                        'display_parent_name' => $field_group->display_name,
                        'group_id' => $field_group->id,
                        'sort' => $item['sort']
                    ]);

                    $row_type = \DB::table('data_types')->where('id', $field_group->data_type_id)->first();
                    \Schema::table($row_type->name, function($table) use ($field_slug) {
                        $table->string($field_slug)->nullable();
                    });
                } else {
                    

                    \DB::table('data_rows')->where([
                        'id' => $item['value'],
                        'group_id' => $field_group->id
                    ])->update([
                        'display_name' => $item['label'],
                        'display_parent_name' => $data['display_name'],
                        'sort' => $item['sort']
                    ]);
                }
            }
            info('$fields_save');
            info($fields_save);
            foreach($fields_by_group as $field_r) {
                info('id '.$field_r->id);
                if(!in_array($field_r->id, $fields_save)) {
                    \DB::table('data_rows')->where('id', $field_r->id)->delete();
                    \Schema::table($entity->slug, function($table) use($field_r) {
                        if (\Schema::hasColumn($table->getTable(), $field_r->field)) {
                            $table->dropColumn($field_r->field);
                        }
                    });
                }
            }
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
        } elseif($field->type == 'status') {
            $files = $request->file('files');
            
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
            $field_values = \DB::table('field_values')->where('field_id', $field->id)->get()->keyBy('id')->toArray();
           
            foreach($request->statuses as $key => $status) {
                $path = '';
                // if($request->hasFile('files') && isset($files[$key])) {
                //     $path = $files[$key]->store('/public/field_icons');
                //     if($path)
                //         \DB::table('field_values')->where('id', $status['id'])->update([
                //             'file' => $path
                //         ]);
                // } else {
                //     \DB::table('field_values')->where('id', $status['id'])->update([
                //         'file' => ''
                //     ]);
                // }
                
                if($field_values && array_key_exists($status['value'], $field_values)) {
                    \DB::table('field_values')->where('id', $status['value'])->update([
                        'field_id' => $field->id,
                        'color' => $status['color'] ? $status['color'] : '',
                        'file' => $status['icon'],
                        'value' => $status['value'] ? $status['value'] : '',
                        'is_hidden' => isset($status['is_hidden']) ? $status['is_hidden'] : 0,
                        'sort' => $status['sort']
                    ]);
                    // if($request->file_remove_values[$key] == 1)
                    //     \DB::table('field_values')->where('id', $request->value_ids[$key])->update([
                    //         'file' => ''
                    //     ]);
                } else {
                    \DB::table('field_values')->insert([
                        'field_id' => $field->id,
                        'color' => $status['color'] ? $status['color'] : '',
                        'file' => $status['icon'],//$path,
                        'value' => $status['value'] ? $status['value'] : '',
                        'is_hidden' => isset($status['is_hidden']) ? $status['is_hidden'] : 0,
                        'sort' => $status['sort']
                    ]);
                }
                if($status['icon'] && isset($status['file_changed']) && $status['file_changed']) {
                    //$path = $status['icon']['raw']->store('/public/field_icons');
                    //if($path)
                        \DB::table('field_values')->where('id', $status['value'])->update([
                            'file' => $status['icon']
                        ]);
                } elseif(!$status['icon']) {
                    \DB::table('field_values')->where('id', $status['value'])->update([
                        'file' => ''
                    ]);
                }
                if($field_values && array_key_exists($status['value'], $field_values)) {
                    unset($field_values[$status['value']]);
                }
            }
            if(count($field_values)) {
                //удаляем значения,которых не было в переданных
                \DB::table('field_values')->whereIn('id', array_keys($field_values))/*->where('is_hidden', 0)*/->delete();
            }
        } else {
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
        }
        \App\Models\Settings::clear_cache();
        //cache()->flush();

        $field = \DB::table('data_rows')->where(['id' => $field->id])->first();

        $settings = get_settings();

        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        $entity_class = $entity->model_name;
        $model_fields = $settings[$entity->name]['fields'];

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $f) {
            if($f->type == 'status')
                $fields_values[$f->field] = \App\Models\Field::getStatusesVisible($f->id);
            $field_colors[$f->field] = $f->label_color ? $f->label_color : null;
        }
        
        $res = array(
            'id' => $field->id,
            'title' => $field->display_name,
            'key' => $field->field,
            'type' => $field->type,
            'visible_always' => $field->visible_always,
            'is_hidden' => $field->hide,
            'is_plural' => $field->is_plural,
            'show_file_name' => $field->show_file_name,
            'is_external_link' => $field->is_external_link,
            'external_link' => $field->external_link,
            'required' => $field->required,
            'read_only' => $field->only_read,
            'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
            'color' => $field_colors[$field->field],
            'group_id' => $field->group_id,
            'sort' => $field->sort,
            'unit' => $field->unit,
            'button_name' => $field->button_name,
            'mask' => $field->mask,
            'editableFields' => array(
                'id' => $field->id,
                'model' => $entity->slug,
                'title' => $field->display_name,
                'type' => $field->type,
                'is_plural' => $field->is_plural,
                'show_file_name' => $field->show_file_name,
                'is_external_link' => $field->is_external_link,
                'external_link' => $field->external_link,
                'required' => $field->required,
                'visible_always' => $field->visible_always,
                'section_id' => $field->section_id,
                'values' => array(
                    array('id' => 0, 'value' => '')
                ),
                'set_color' => ($field->label_color ? $field->label_color : 0),
                'color' => ($field->label_color ? $field->label_color : ''),
                'button_name' => $field->button_name,
                'has_roles_read' => ($field->roles_read ? 1 : 0),
                'has_roles_write' => ($field->roles_write ? 1 : 0),
                'roles_read' => ($field->roles_read ? json_decode($field->roles_read, true) : array()),
                'roles_write' => ($field->roles_write ? json_decode($field->roles_write, true) : array()),
                'show_in_mobile' => ($field->mobile_pages ? 1 : 0),
                'mobile_pages' => ($field->mobile_pages ? json_decode($field->mobile_pages, true) : array()),
                'unit' => $field->unit,
                'statuses' => array()
            )
        );
        if(isset($settings[$entity->slug]['list_values'][$field->field]) && $field->type != 'relation') {
            $values = array();
            $res['options'] = $settings[$entity->slug]['list_values'][$field->field];
            $res['options_key_value'] = $settings[$entity->slug]['list_values'][$field->field];
            if($field->type == 'status') {
                $simple_options = array();
                foreach($settings[$entity->slug]['list_values'][$field->field] as $k => $option) {
                    $simple_options[$k] = $option;
                    $values[] = $option;
                }
                $res['options_key_value'] = $simple_options;
            } else {
                foreach($settings[$entity->slug]['list_values'][$field->field] as $k => $option) {
                    $simple_options[$k] = $option;
                    $values[] = $option;
                }
            }
            $res['editableFields']['values'] = $values;
            if($field->type == 'status')
                $res['editableFields']['statuses'] = $settings[$entity->slug]['list_values'][$field->field];
        };
        if($field->type == 'text_group') {
            $subfields = \App\Models\Field::getByGroup($field->id);
            $values = array();
            $res['options'] = array();
            $res['options_key_value'] = array();
            foreach($subfields as $subfield) {
                $res['options'][$subfield->id] = $subfield->display_name;
                $res['options_key_value'][$subfield->id] = $subfield->display_name;
                $values[] = array(
                    'value' => $subfield->id,
                    'label' => $subfield->display_name,
                    'sort' => $subfield->sort
                );
            };
            $res['editableFields']['values'] = $values;
            foreach($subfields as $subfield) {
                $res['subfields'][] = array(
                    'id' => $subfield->id,
                    'title' => $subfield->display_name,
                    'key' => $subfield->field,
                    'value' => '',
                    'type' => $subfield->type,
                    'visible_always' => $field->visible_always,
                    'is_hidden' => $field->hide,
                    'is_plural' => $field->is_plural,
                    'read_only' => $field->only_read,
                    'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
                    'color' => $field_colors[$field->field],
                    'group_id' => $subfield->group_id,
                    'sort' => $subfield->sort,
                    'unit' => $field->unit
                );
            }
        }
        $res['user_id'] = \Auth::user()->id;
        \App\Events\FieldUpdated::dispatch('FieldUpdated', $res);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);
        info($res);

        return response()->json($res);
    }

    public function changeSort(Request $request)
    {
        $field = \DB::table('data_rows')->where(['id' => $request->id])->first();

        \DB::table('data_rows')->where(['id' => $request->id])->update(['section_id' => $request->section_id]);

        \DB::table('data_rows')->upsert($request->fields, 'id', ['id', 'sort']);
        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        
        foreach($request->fields as $f) {
            

            if($f['id'] == $request->id) {
                $sort = $f['sort'];
                break;
            }
        }
        // $items = \DB::table('data_rows')->whereIn('id', $request->items)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->items).")"))->get();
        // foreach ($items as $key => $item) {
        //     \DB::table('data_rows')->where(['id' => $item->id])->update([
        //         'sort' => $key
        //     ]);
        // }

        \App\Models\Settings::clear_cache();
        //cache()->flush();
        $data = array(
            'id' => $request->id,
            'user_id' => \Auth::user()->id,
            'old_section' => $field->section_id,
            'new_section' => $request->section_id,
            'sort' => $sort
        );
        \App\Events\FieldUpdated::dispatch('FieldSorted', $data);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return json_encode(array('id' => $request->id, 'section_id' => $request->section_id));
    }

    public function hide(int $id, Request $request) {
        \DB::table('data_rows')->where(['id' => $id])->update([
            'hide' => 1
        ]);
        \App\Models\Settings::clear_cache();
        //cache()->flush();
        $field = \DB::table('data_rows')->where('id', $id)->first();
        $settings = get_settings();

        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        $entity_class = $entity->model_name;
        $model_fields = $settings[$entity->name]['fields'];
        
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $f) {
            $field_colors[$f->field] = $f->label_color ? $f->label_color : null;
        }

        $data = array(
            'id' => $field->id,
            'user_id' => \Auth::user()->id,
            'title' => $field->display_name,
            'key' => $field->field,
            'type' => $field->type,
            'visible_always' => $field->visible_always,
            'is_hidden' => $field->hide,
            'read_only' => $field->only_read,
            'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
            'color' => $field_colors[$field->field],
            'group_id' => $field->group_id
        );
        
        \App\Events\FieldUpdated::dispatch('FieldHidden', $data);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json($data);
    }

    public function hide_batch(Request $request) {
        \DB::table('data_rows')->whereIn('id', $request->ids)->update([
            'hide' => 1
        ]);
        \App\Models\Settings::clear_cache();
        //cache()->flush();
        $settings = get_settings();

        $fields = \DB::table('data_rows')->whereIn('id', $request->ids)->get();
        $field = $fields->first();
        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        $entity_class = $entity->model_name;
        $model_fields = $settings[$entity->name]['fields'];

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $f) {
            $field_colors[$f->field] = $f->label_color ? $f->label_color : null;
        }
        foreach($fields as $field) {
            $data = array(
                'id' => $field->id,
                'user_id' => \Auth::user()->id,
                'title' => $field->display_name,
                'key' => $field->field,
                'type' => $field->type,
                'visible_always' => $field->visible_always,
                'is_hidden' => $field->hide,
                'read_only' => $field->only_read,
                'can_edit' => !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
                'color' => $field_colors[$field->field],
                'group_id' => $field->group_id
            );
            \App\Events\FieldUpdated::dispatch('FieldHidden', $data);
        };
        
        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json($fields);
    }

    public function delete(Request $request, int $id) {
        
        $field = \DB::table('data_rows')->where(['id' => $id])->first();
        $subfields = \DB::table('data_rows')->where(['group_id' => $id])->get();
        $row_type = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        
        
        // if(!$subfields) {
        //     \Schema::table($row_type->slug, function($table) use($field){
        //         $table->dropColumn($field->field);
        //     });
        // }
        \Schema::table($row_type->slug, function($table) use($field){
            if (\Schema::hasColumn($table->getTable(), $field->field)) {
                $table->dropColumn($field->field);
            }
        });
        if($subfields) {
            foreach($subfields as $f) {
                \Schema::table($row_type->slug, function($table) use($f) {
                    if (\Schema::hasColumn($table->getTable(), $f->field)) {
                        $table->dropColumn($f->field);
                    }
                });
            }
        }
        \DB::table('data_rows')->where(['id' => $id])->whereNull('is_permanent')->delete();
        // ->update([
        //     'is_remove' => 1
        // ]);
        \DB::table('data_rows')->where(['group_id' => $id])->whereNull('is_permanent')->delete();
        // ->update([
        //     'is_remove' => 1
        // ]);

        \App\Models\Settings::clear_cache();
        //cache()->flush();

        \App\Events\FieldUpdated::dispatch('FieldDeleted', array('id' => $id, 'user_id' => \Auth::user()->id));

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$row_type->slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$row_type->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$row_type->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json([
            'status' => 200,
            'success' => true
        ]);
    }

    public function delete_batch($model, Request $request, int $id) {
        $fields = \DB::table('data_rows')->whereIn(['id' => $request->ids])->whereNotNull('is_permanent')->get();
        $subfields = \DB::table('data_rows')->whereIn(['group_id' => $request->ids])->whereNotNull('is_permanent')->get();

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$model", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$model", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$model", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json($fields);

        foreach($fields as $field) {
            // \Schema::table($model, function($table) {
            //     $table->dropColumn($field->field);
            // });
            //\DB::table('data_rows')->whereIn(['id' => $request->ids])->whereNotNull('is_permanent')->delete();
        }
        foreach($subfields as $field) {
            // \Schema::table($model, function($table) {
            //     $table->dropColumn($field->field);
            // });
            //\DB::table('data_rows')->whereIn(['id' => $request->ids])->whereNotNull('is_permanent')->delete();
        }
        
        \App\Models\Settings::clear_cache();
        //cache()->flush();

        return response()->json([
            'status' => 200,
            'success' => true
        ]);
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
                'values_list' => json_encode($request->values_list)
            ]);
        } else {
            \DB::table('comparison_fields')->where([
                'module' => $module,
                'entity' => $entity,
                'module_field' => $request->module_field
            ])->update([
                'entity_field' => $request->entity_field,
                'values_list' => json_encode($request->values_list)
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

    public function get_field_modules($field, Request $request) {
        $data = array();

        $comparisons_by_module = \DB::table('comparison_fields')->where([
            'entity_field' => $field,
        ])->get()->groupBy('module');

        foreach($comparisons_by_module as $module => $comparisons) {
            $item = array(
                'name' => $module,
                'fields' => array()
            );
            foreach($comparisons as $comparison) {
                $item['fields'][] = $comparison->module_field;
            }
            $data[] = $item;
        }
        
        return response()->json($data);
    }

    public function delete_compare($entity, $module, Request $request) {
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
                'values_list' => json_encode($request->values_list)
            ]);
        } else {
            \DB::table('comparison_fields')->where([
                'module' => $module,
                'entity' => $entity,
                'module_field' => $request->module_field
            ])->update([
                'entity_field' => $request->entity_field,
                'values_list' => json_encode($request->values_list)
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
}