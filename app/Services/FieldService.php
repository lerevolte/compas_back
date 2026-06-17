<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\DTO\FieldCreateDto;
use App\DTO\FieldUpdateDto;
use Carbon\Carbon;
use App\Models\Field;

class FieldService
{
	public function create(FieldCreateDto $dto)
	{
		$entity_id = \DB::table('data_types')->select('id')->where('name', $dto->entity)->first()->id;
		$max_sort = \DB::table('data_rows')->where('data_type_id', $entity_id)->max('sort');
        $max_sort++;
        $title = $dto->title;
        $type = $dto->type;
        $key = \Str::slug($title, '_');
        $details = array();
        $options = array();

        
        if($type == 'select_dropdown') {
            foreach($dto->options as $item) {
                $details['options'][] = $item;//drop
                $options[] = $item;
            }
        }

        if($type == 'text_group') {
            
            $group_id = \DB::table('data_rows')->insertGetId([
                'data_type_id' => $entity_id,
                'field' => $key,
                'type' => $type,
                'title' => $title,
                'visible_always' => $dto->visible_always,
                'section_id' => $dto->section_id,
                'hide' => $dto->section_id ? 0 : 1,
                'roles_read' => $dto->has_roles_read ? json_encode($dto->roles_read) : null,
                'roles_write' => $dto->roles_write ? json_encode($dto->roles_write) : null,
                'subfields' => $dto->subfields ? json_encode($dto->subfields) : null,
                'unit' => $dto->unit,
                'sort' => $max_sort,
                'is_external_link' => $dto->is_external_link,
            ]);
            
            if(count($dto->subfields)) {
                foreach($dto->subfields as $k => $subfield) {
                    \DB::table('data_rows')->where('id', $subfield)->update(['group_id' => $group_id, 'sort' => $k]);
                }
            }
            $field_id = $group_id;
        } elseif($type == 'status') {
            $field_id = \DB::table('data_rows')->insertGetId([
                'data_type_id' => $entity_id,
                'field' => $key,
                'type' => $type,
                'title' => $title,
                'visible_always' => $dto->visible_always,
                'details' => null,
                'set_color' => $dto->set_color ? $dto->set_color : 0,
                'label_color' => $dto->set_color ? $dto->color : '',
                'section_id' => $dto->section_id,
                'hide' => $dto->section_id ? 0 : 1,
                'button_name' => $dto->button_name,
                'is_plural' => $dto->is_plural,
                'required' => $dto->required,
                'roles_read' => $dto->has_roles_read ? json_encode($dto->roles_read, true) : '',
                'roles_write' => $dto->has_roles_write ? json_encode($dto->roles_write, true) : '',
                'unit' => $dto->unit,
                'sort' => $max_sort,
                'is_external_link' => 0,
            ]);
            \DB::table('data_rows')->where('field', $key)->update([
                'field' => $key.'_'.$field_id,
            ]);
            $key = $key.'_'.$field_id;
            foreach($dto->options as $k => $status) {
                \DB::table('field_values')->insert([
                    'field_id' => $field_id,
                    'color' => $status['label']['color'],
                    'file' => $status['label']['file'],
                    'value' => $status['label']['text'],
                    'is_hidden' => $status['label']['is_hidden'],
                    'sort' => $k
                ]);
            }
        } else {
            $field_id = \DB::table('data_rows')->insertGetId([
                'data_type_id' => $entity_id,
                'field' => $key,
                'type' => $type,
                'title' => $title,
                'visible_always' => $dto->visible_always,
                'default_value' => $dto->default_value,
                'details' => count($details) ? json_encode($details) : null,
                'options' => count($options) ? json_encode($options) : null,
                'set_color' => $dto->set_color ? $dto->set_color : 0,
                'label_color' => $dto->set_color ? $dto->color : '',
                'section_id' => $dto->section_id,
                'hide' => $dto->section_id ? 0 : 1,
                'button_name' => $dto->button_name,
                'is_plural' => $dto->is_plural,
                'required' => $dto->required,
                'show_file_name' => $dto->show_file_name,
                'roles_read' => $dto->has_roles_read ? json_encode($dto->roles_read, true) : '',
                'roles_write' => $dto->has_roles_write ? json_encode($dto->roles_write, true) : '',
                'unit' => $dto->unit,
                'sort' => $max_sort,
                'is_external_link' => $dto->is_external_link
            ]);
            \DB::table('data_rows')->where('field', $key)->update([
                'field' => $key.'_'.$field_id,
            ]);
            $key = $key.'_'.$field_id;
        }
        if($type == 'file')
            \Schema::table($dto->entity, function($table) use ($key) {
                $table->text($key)->nullable();
            });
        else {
            if($dto->is_plural)
                \Schema::table($dto->entity, function($table) use ($key) {
                    $table->text($key)->nullable();
                });
            else
                \Schema::table($dto->entity, function($table) use ($key) {
                    $table->text($key)->nullable();
                });
        }


        \App\Models\Settings::clear_cache();
        $field = \DB::table('data_rows')->where('id', $field_id)->first();

        $settings = \App\Models\Settings::get(true);

        $entity = $settings['models'][$dto->entity];
        $entity_class = $entity->model_name;
        $model_fields = $settings[$dto->entity]['fields'];

        foreach($model_fields as $f) {
            if($f->type == 'status')
                $fields_values[$f->field] = \App\Models\Field::getStatusesVisible($f->id);
        }

        $res = $settings[$entity->slug]['field_data'][$field->field];
        $res['can_edit'] = 1;
        if($field->type == 'text_group') {
            $subfields = \App\Models\Field::getByGroup($field->id);
            $values = array();
            $res['options'] = array();
            $res['options_key_value'] = array();
            foreach($subfields as $subfield) {
                $res['options_key_value'][$subfield->id] = $subfield->title;
                $values[] = array(
                    'value' => $subfield->id,
                    'label' => $subfield->title,
                    'sort' => $subfield->sort
                );
            };
            $res['options'] = $values;
            $res['editableFields']['values'] = $values;
            foreach($subfields as $subfield) {
                $res['subfields'][] = array(
                    'id' => $subfield->id,
                    'title' => $subfield->title,
                    'key' => $subfield->field,
                    'value' => '',
                    'type' => $subfield->type,
                    'visible_always' => $field->visible_always,
                    'is_hidden' => $field->hide,
                    'read_only' => $field->only_read,
                    'can_edit' => 1,
                    'color' => $field->label_color,
                    'group_id' => $subfield->group_id,
                    'unit' => $field->unit,
                    'sort' => $subfield->sort
                );
            }
        }
        if(isset($settings['list_values'][$field->id])) {
            $values = array();
            $res['options'] = $settings['list_values'][$field->id];
            $res['options_key_value'] = $settings['list_values'][$field->id];
            if($field->type == 'status') {
                $simple_options = array();
                foreach($settings['list_values'][$field->id] as $k => $option) {
                    $simple_options[$k] = $option;
                    $values[] = $option;
                }
                $res['options_key_value'] = $simple_options;
            } else {
                foreach($settings['list_values'][$field->id] as $k => $option) {
                    $values[] = $option;
                }
            }
            $res['editableFields']['values'] = $values;
            if($field->type == 'status')
                $res['editableFields']['statuses'] = $settings['list_values'][$field->id];
        }

        $data = array(
            'user_id' => \Auth::user()->id,
            'slug' => $dto->entity,
            'viewDetail' => $res,
            'viewList' => array()
        );
        \App\Events\FieldUpdated::dispatch('FieldCreated', $data);

        $now = Carbon::now();

        if(\DB::table('local_cache')->where(['url' => "fields/$dto->entity", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$dto->entity", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$dto->entity", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return $res;
	}

    public function update(int $id, FieldUpdateDto $dto)
    {
        $field = \DB::table('data_rows')->where(['id' => $id])->first();
        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();

        $is_changed_table = 1;

        if($dto->visible_always || $dto->show_file_name || $dto->is_hidden)
            $is_changed_table = 0;



        if($dto->section_id && $dto->change_section) {
            $max_sort = \DB::table('data_rows')->where('section_id', $dto->section_id)->max('sort');
            $max_sort++;
            if($dto->sort)
                \DB::table('data_rows')->where(['id' => $id])->update(['section_id' => $dto->section_id, 'sort' => $dto->sort, 'hide' => 0]);
            else
                \DB::table('data_rows')->where(['id' => $id])->update(['section_id' => $dto->section_id, 'sort' => $max_sort, 'hide' => 0]);

            \App\Models\Settings::clear_cache();
            $res = array_filter((array)$dto);

            $res['user_id'] = \Auth::user()->id;

            $settings = app('settings');
        
            $res = $settings[$entity->slug]['field_data'][$field->field];
            
            if(isset($settings[$entity->slug]['options'][$field->field]))
                $field_values = $settings[$entity->slug]['options'][$field->field];
            elseif(isset($settings['list_values'][$field->id]))
                $field_values = $settings['list_values'][$field->id];
            if(isset($field_values))
                $res['options'] = array_values($field_values);

            $data = array(
                'slug' => $entity->slug,
                'user_id' => \Auth::user()->id,
                'is_changed_table' => $is_changed_table,
                'viewDetail' => $res,
                'viewList' => array_merge(
                    array('id' => $field->id, 'section_id' => $dto->section_id),
                )
            );
            \App\Events\FieldUpdated::dispatch('FieldUpdated', $data);

            $now = Carbon::now();
            if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
                \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
            else
                \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

            return $res;

        }

        $data = array();
        $data['title'] = isset($dto->title) ? $dto->title : $field->title;
        $data['visible_always'] = isset($dto->visible_always) ? $dto->visible_always : $field->visible_always;

        $details = array();
        if($dto->options && $field->type != 'text_group' && $field->type != 'relation' && $field->field != 'is_admin') {
            foreach($dto->options as $k => $item) {
                $details['options'][$item['value']] = array(
                    'value' => $item['value'],
                    'label' => $item['label']
                );
            }
            if (isset($dto->can_create)) {
                $details['can_create'] = $dto->can_create ? true : false;
            }
            $data['details'] = json_encode($details, true);
        } elseif (isset($dto->can_create)) {
            $existingDetails = json_decode($field->details, true) ?? [];
            $existingDetails['can_create'] = $dto->can_create ? true : false;
            $data['details'] = json_encode($existingDetails, true);
        };

        $data['set_color'] = isset($dto->set_color) ? $dto->set_color : $field->set_color;
        $data['label_color'] = isset($dto->color) ? $dto->color : $field->label_color;

        $data['roles_read'] = $field->roles_read;
        if(isset($dto->has_roles_read) && $dto->has_roles_read)
            $data['roles_read'] = count($dto->roles_read) ? $dto->roles_read : null;
        elseif(!isset($dto->has_roles_read) || !$dto->has_roles_read)
            $data['roles_read'] = null;

        $data['subfields'] = $field->subfields;
        if($dto->subfields)
            $data['subfields'] = count($dto->subfields) ? $dto->subfields : null;

        $data['roles_write'] = $field->roles_write;
        if(isset($dto->has_roles_write) && $dto->has_roles_write)
            $data['roles_write'] = count($dto->roles_write) ? $dto->roles_write : null;
        elseif(!isset($dto->has_roles_write) || !$dto->has_roles_write)
            $data['roles_write'] = null;
        $data['mobile_pages'] = isset($dto->show_in_mobile) ? $dto->mobile_pages : $field->mobile_pages;
        $data['button_name'] = isset($dto->button_name) ? $dto->button_name : $field->button_name;
        $data['show_file_name'] = isset($dto->show_file_name) ? $dto->show_file_name : $field->show_file_name;
        $data['required'] = isset($dto->required) ? $dto->required : $field->required;
        $data['is_external_link'] = isset($dto->is_external_link) ? $dto->is_external_link : $field->is_external_link;
        $data['unit'] = isset($dto->unit) ? $dto->unit : $field->unit;
        $data['section_id'] = isset($dto->section_id) ? $dto->section_id : $field->section_id;
        $data['hide'] = isset($dto->is_hidden) ? $dto->is_hidden : $field->hide;

        if($field->type == 'text_group') {
            $field_group = \DB::table('data_rows')->where('field', $field->field)->first();
            $fields_by_group = \DB::table('data_rows')->where('group_id', $field_group->id)->get();
            $fields_saved = array();

            \DB::table('data_rows')->where(['group_id' => $field->id])->update(['group_id' => null]);
            if(count($dto->subfields)) {
                foreach($dto->subfields as $k => $subfield) {
                    \DB::table('data_rows')->where('id', $subfield)->update(['group_id' => $field->id, 'sort' => $k]);
                }
            }

            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
        } elseif($field->type == 'status') {
            
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
            $field_values = \DB::table('field_values')->where('field_id', $field->id)->get()->keyBy('id')->toArray();

            if($dto->options) {
                foreach($dto->options as $key => $status) {
                    $path = '';
                    
                    if($field_values && array_key_exists($status['value'], $field_values)) {
                        \DB::table('field_values')->where('id', $status['value'])->update([
                            'field_id' => $field->id,
                            'color' => $status['label']['color'] ? $status['label']['color'] : '',
                            'file' => $status['label']['file'],
                            'value' => $status['label']['text'] ? $status['label']['text'] : '',
                            'is_hidden' => isset($status['label']['is_hidden']) ? $status['label']['is_hidden'] : 0,
                            'sort' => $key
                        ]);
                    } else {
                        \DB::table('field_values')->insert([
                            'field_id' => $field->id,
                            'color' => $status['label']['color'] ? $status['label']['color'] : '',
                            'file' => $status['label']['file'],
                            'value' => $status['label']['text'] ? $status['label']['text'] : '',
                            'is_hidden' => isset($status['label']['is_hidden']) ? $status['label']['is_hidden'] : 0,
                            'sort' => $key
                        ]);
                    }
                    if($status['label']['file'] && isset($status['file_changed']) && $status['file_changed']) {
                        \DB::table('field_values')->where('id', $status['value'])->update([
                            'file' => $status['label']['file']
                        ]);
                    } elseif(!$status['label']['file']) {
                        \DB::table('field_values')->where('id', $status['value'])->update([
                            'file' => ''
                        ]);
                    }
                    if($field_values && array_key_exists($status['value'], $field_values)) {
                        unset($field_values[$status['value']]);
                    }
                }
                if(count($field_values)) {
                    \DB::table('field_values')->whereIn('id', array_keys($field_values))->delete();
                }
            }
                
            
        } else {
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
        }
        
        if($field->type == 'status') {
            $keys = cache()->getMemcached()->getAllKeys();
            $regex = tenant('id').':field-'.$field->id.'-statuses';
            foreach($keys as $item) {
                if(preg_match('/'.$regex.'/', $item)) {
                    cache()->getMemcached()->delete($item);
                }
            }
            $cache_name = tenant('id').':field-'.$field->id.'-statuses';
            $field_values_cache = \DB::table('field_values')->orderBy('is_hidden', 'asc')->orderBy('sort')->where('field_id', $field->id)->where('is_hidden', '!=', 1)->get();
            cache()->getMemcached()->add($cache_name, $field_values_cache);
        }
        \App\Models\Settings::clear_cache();

        $field = \DB::table('data_rows')->where(['id' => $field->id])->first();

        $settings = app('settings');

        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        $entity_class = $entity->model_name;
        $model_fields = $settings[$entity->name]['fields'];

        $res = $settings[$entity->slug]['field_data'][$field->field];

        if(!$res['roles_read'])
            $res['has_roles_read'] = 0;
        if(!$res['roles_write']) 
            $res['has_roles_write'] = 0;

        if(isset($settings['list_values'][$field->id]) && $field->type != 'relation') {
            $values = array();
            if($field->type == 'status') {
                $simple_options = array();
                foreach($settings['list_values'][$field->id] as $k => $option) {
                    $simple_options[$k] = $option;
                    $values[] = $option;
                }
            } else {
                foreach($settings['list_values'][$field->id] as $k => $option) {
                    $simple_options[$k] = $option;
                    $values[] = $option;
                }
            }
            if(isset($settings[$entity->slug]['options'][$field->field]))
                $field_values = $settings[$entity->slug]['options'][$field->field];
            else
                $field_values = $settings['list_values'][$field->id];
            $res['options'] = array_values($field_values);
        };
        
        $data = array(
            'slug' => $entity->slug,
            'user_id' => \Auth::user()->id,
            'is_changed_table' => $is_changed_table,
            'viewDetail' => $res,
            'viewList' => $res
        );
        if($field->type == 'text_group') {
            $subfields = \App\Models\Field::getByGroup($field->id);
            $values = array();
            $data['viewDetail']['fields'] = $data['viewDetail']['options'] = array();
            foreach($subfields as $subfield) {
                $subfield_data = $settings[$entity->slug]['field_data'][$subfield->field];
                $subfield_data['can_read'] = $settings[$entity->slug]['perms'][$subfield->field]['read'] || \Auth::user()->is_admin ? 1 : 0;
                $subfield_data['can_edit'] = $subfield->only_read || !$settings[$entity->slug]['perms'][$subfield->field]['write'] && !\Auth::user()->is_admin ? 0 : 1;
                $data['viewDetail']['fields'][] = $subfield_data;

                $data['viewDetail']['options'][] = array(
                    'value' => $subfield->id,
                    'label' => $subfield->title,
                    'sort' => $subfield->sort
                );
            };
            $res['options'] = $data['viewDetail']['options'];
            $res['subfields'] = $data['viewDetail']['subfields'];
            $res['fields'] = $data['viewDetail']['fields'];
        }
        \App\Events\FieldUpdated::dispatch('FieldUpdated', $data);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return $res;
    }
}