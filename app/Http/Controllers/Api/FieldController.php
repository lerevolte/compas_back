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
use App\Http\Requests\FieldCreateRequest;
use App\Http\Requests\FieldUpdateRequest;
use App\Services\FieldService;

class FieldController extends Controller
{
    private FieldService $fieldService;
 
    public function __construct(FieldService $fieldService)
    {
        $this->fieldService = $fieldService;
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
        $field_value_id = \DB::table('field_values')->insertGetId($data);
        $field = \DB::table('data_rows')->select('data_type_id')->where('id', $request->field_id)->first();
        $entity = \DB::table('data_types')->selecT('slug')->where('id', $field->data_type_id)->first();

        \App\Models\Settings::clear_cache();
        $field_data = \App\Models\Field::find($request->field_id)->getData();
        $field_data['options'][] = array(
            'label' => array(
                'id' => $field_value_id,
                'sort' => $data['sort'],
                'file' => $data['file'],
                'is_hidden' => $data['is_hidden'],
                'field_id' => $data['field_id'],
                'color' => $data['color'],
                'text' => $data['value']
            ),
            'value' => $field_value_id
        );
        $res = [
            'slug' => $entity->slug,
            'user_id' => \Auth::user()->id,
            'changed_by' => \Auth::user()->id,
            'is_changed_table' => 1,
            'viewDetail' => $field_data,
            'viewList' => array(
                'id' => $data['field_id'],
                'options' => $field_data['options']
            )
        ];
        // $data['id'] = $field_id;
        $data = array(
            'label' => array(
                'id' => $field_value_id,
                'sort' => $data['sort'],
                'file' => $data['file'],
                'is_hidden' => $data['is_hidden'],
                'field_id' => $data['field_id'],
                'color' => $data['color'],
                'text' => $data['value']
            ),
            'value' => $field_value_id
        );

        // $socket_data = array(
        //     'field_id' => 0,
        //     'options' => $data
        // );
        \App\Events\FieldUpdated::dispatch('FieldUpdated', $res);

        return response()->json($data);
        // id = value
        // sort // не важен, т.к. поле скрыто
        // file // у скрытых null
        // field_id // привязанность к определенному полю, я при запросе отправляю его тебе
        // text // у скрытых null
    }

    public function store(FieldCreateRequest $request) 
    {
        if (!$res = $this->fieldService->create($request->getDto())) {
            return response()->json(['error' => 400]);
        }
        
        return response()->json($res);
    }

    public function new_update(int $id, FieldUpdateRequest $request) 
    {
        if (!$res = $this->fieldService->update($id, $request->getDto())) {
            return response()->json(['error' => 400]);
        }
        
        return response()->json($res);
    }

    public function update($id, Request $request) {

        $field = \DB::table('data_rows')->where(['id' => $id])->first();
        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();

        $is_changed_table = 1;

        if($request->has('visible_always') || $request->has('show_file_name') || $request->has('is_hidden'))
            $is_changed_table = 0;

        if($request->section_id && $request->change_section) {
            $max_sort = \DB::table('data_rows')->where('section_id', $request->section_id)->max('sort');
            $max_sort++;
            if($request->sort)
                \DB::table('data_rows')->where(['id' => $id])->update(['section_id' => $request->section_id, 'sort' => $request->sort, 'hide' => 0]);
            else
                \DB::table('data_rows')->where(['id' => $id])->update(['section_id' => $request->section_id, 'sort' => $max_sort, 'hide' => 0]);

            \App\Models\Settings::clear_cache();
            $res = $request->all();

            $res['user_id'] = \Auth::user()->id;
            $data = array(
                'slug' => $entity->slug,
                'user_id' => \Auth::user()->id,
                'changed_by' => \Auth::user()->id,
                'is_changed_table' => $is_changed_table,
                'viewDetail' => $res,
                'viewList' => array_merge(
                    array('id' => $field->id, 'section_id' => $request->section_id),
                )
            );
            \App\Events\FieldUpdated::dispatch('FieldUpdated', $data);

            $now = Carbon::now();
            if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
                \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
            else
                \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

            return response()->json($res);

        }

        $data = array();
        $data['title'] = $request->title ? $request->title : $field->title;
        $data['visible_always'] = $request->has('visible_always') ? $request->visible_always : $field->visible_always;
        if($request->rules)
            $data['rules'] = json_encode($request->rules, true);

        $details = array();
        if($request->options && $field->type != 'text_group' && $field->type != 'relation' && $field->field != 'is_admin') {
            foreach($request->options as $k => $item) {
                $details['options'][$item['value']] = array(
                    'value' => $item['value'],
                    'label' => $item['label']
                );
            }
            $data['details'] = json_encode($details, true);
        };

        $data['set_color'] = $request->has('set_color') ? $request->set_color : $field->set_color;
        $data['label_color'] = $request->has('color') ? $request->color : $field->label_color;
        $data['roles_read'] = $field->roles_read;

        if($request->has('has_roles_read') && $request->has_roles_read)
            $data['roles_read'] = count($request->roles_read) ? $request->roles_read : null;
        elseif(!$request->has('has_roles_read') || !$request->has_roles_read)
            $data['roles_read'] = null;

        $data['roles_write'] = $field->roles_write;
        if($request->has('has_roles_write') && $request->has_roles_write)
            $data['roles_write'] = count($request->roles_write) ? $request->roles_write : null;
        elseif(!$request->has('has_roles_write') || !$request->has_roles_write)
            $data['roles_write'] = null;
        $data['subfields'] = $field->subfields;
        if($request->has('subfields')) {
            \DB::table('data_rows')->where(['group_id' => $id])->update(['group_id' => null]);
            $data['subfields'] = count($request->subfields) ? $request->subfields : null;
            if(count($request->subfields))
                \DB::table('data_rows')->whereIn('id', $request->subfields)->update(['group_id' => $id]);
        }
        //$data['roles_write'] = $request->has('has_roles_write') && $request->roles_write ? $request->roles_write : $field->roles_write;
        $data['mobile_pages'] = $request->has('show_in_mobile') ? $request->mobile_pages : $field->mobile_pages;
        $data['button_name'] = $request->button_name ? $request->button_name : $field->button_name;
        $data['show_file_name'] = $request->has('show_file_name') ? $request->show_file_name : $field->show_file_name;
        $data['required'] = $request->has('required') ? $request->required : $field->required;
        $data['is_external_link'] = $request->has('is_external_link') ? $request->is_external_link : $field->is_external_link;
        $data['external_link'] = $request->external_link ? $request->external_link : $field->external_link;
        $data['unit'] = $request->has('unit') ? $request->unit : $field->unit;
        $data['section_id'] = $request->section_id ? $request->section_id : $field->section_id;
        $data['hide'] = $request->has('is_hidden') ? $request->is_hidden : $field->hide;

        $data['mask'] = $request->mask ? $request->mask : $field->mask;

        if($field->type == 'text_group') {
            $field_group = \DB::table('data_rows')->where('field', $field->field)->first();
            $fields_by_group = \DB::table('data_rows')->where('group_id', $field_group->id)->get();
            $fields_save = array();
            if($request->options) {
                foreach($request->options as $k => $item) {
                    $field_slug = \Str::slug($item['label'], '_').'_'.$field_group->id;
                    $fields_save[] = $item['value'];
                    if (isset($item['new'])) {
                        $field_id = \DB::table('data_rows')->insertGetId([
                            'data_type_id' => $field_group->data_type_id,
                            'field' => $field_slug,
                            'type' => 'text',
                            'title' => $item['label'],
                            'display_parent_name' => $field_group->title,
                            'group_id' => $field_group->id
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
                            'title' => $item['label'],
                            'display_parent_name' => $data['title']
                        ]);
                    }
                }
                foreach($fields_by_group as $field_r) {
                    if(!in_array($field_r->id, $fields_save)) {
                        \DB::table('data_rows')->where('id', $field_r->id)->delete();
                        \Schema::table($entity->slug, function($table) use($field_r) {
                            if (\Schema::hasColumn($table->getTable(), $field_r->field)) {
                                $table->dropColumn($field_r->field);
                            }
                        });
                    }
                }
            }
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
        } elseif($field->type == 'status') {
            $files = $request->file('files');
            
            \DB::table('data_rows')->where(['id' => $field->id])->update($data);
            $field_values = \DB::table('field_values')->where('field_id', $field->id)->get()->keyBy('id')->toArray();

            if($request->options)
                foreach($request->options as $key => $status) {
                    $path = '';
                    
                    if($field_values && array_key_exists($status['value'], $field_values)) {
                        \DB::table('field_values')->where('id', $status['value'])->update([
                            'field_id' => $field->id,
                            'color' => $status['label']['color'] ? $status['label']['color'] : '',
                            'file' => $status['label']['file'],
                            'value' => $status['label']['text'] ? $status['label']['text'] : '',
                            'is_hidden' => isset($status['label']['is_hidden']) ? $status['label']['is_hidden'] : 0,
                            'sort' => $key
                            //'sort' => $status['label']['sort']
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
                //удаляем значения,которых не было в переданных
                \DB::table('field_values')->whereIn('id', array_keys($field_values))->delete();
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

        foreach($model_fields as $f) {
            if($f->type == 'status')
                $fields_values[$f->field] = \App\Models\Field::getStatusesVisible($f->id);
        }
        
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
            if($field->type == 'status') {
                $res['options'] = $settings['list_values'][$field->id];
            }
        };
        
        $data = array(
            'slug' => $entity->slug,
            'user_id' => \Auth::user()->id,
            'changed_by' => \Auth::user()->id,
            'is_changed_table' => $is_changed_table,
            'viewDetail' => $res,
            'viewList' => array_merge(
                array('id' => $field->id),
                $request->all()
            )
        );
        if($field->type == 'text_group') {
            $subfields = \App\Models\Field::getByGroup($field->id);
            $values = $data['viewDetail']['fields'] = array();

            foreach($subfields as $subfield) {
                $data['viewDetail']['options'][] = array(
                    'value' => $subfield->id,
                    'label' => $subfield->title,
                    'sort' => $subfield->sort
                );
                $subfield_data = $settings[$entity->slug]['field_data'][$subfield->field];
                $subfield_data['can_read'] = $settings[$entity->slug]['perms'][$subfield->field]['read'] || \Auth::user()->is_admin ? 1 : 0;
                $subfield_data['can_edit'] = $subfield->only_read || !$settings[$entity->slug]['perms'][$subfield->field]['write'] && !\Auth::user()->is_admin ? 0 : 1;
                if(!$id && $permissions['create_p'] == 'Y' && $field->field == 'user_id' && !\Auth::user()->is_admin) {
                    $subfield_data['can_edit'] = 0;
                }
                if($id && $permissions['update_p'] == 'Y' && $current->user_id != \Auth::user()->id && !\Auth::user()->is_admin && $slug != 'users' && \Auth::user()->id != $id || 
                    $id && $permissions['update_p'] == 'N' && !\Auth::user()->is_admin) {
                        $subfield_data['can_edit'] = 0;
                }
                $subfield_data['value'] = $current->{$subfield->field};
                $data['viewDetail']['fields'][] = $subfield_data;
            };
            $res['fields'] = $data['viewDetail']['fields'];
        }
        \App\Events\FieldUpdated::dispatch('FieldUpdated', $data);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$entity->slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);



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

        \App\Models\Settings::clear_cache();

        $data = array(
            'id' => $request->id,
            'user_id' => \Auth::user()->id,
            'changed_by' => \Auth::user()->id,
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
        $field = \DB::table('data_rows')->where('id', $id)->first();
        $settings = app('settings');

        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        $entity_class = $entity->model_name;
        $model_fields = $settings[$entity->name]['fields'];

        $data = array(
            'id' => $field->id,
            'user_id' => \Auth::user()->id,
            'changed_by' => \Auth::user()->id,
            'title' => $field->title,
            'key' => $field->field,
            'type' => $field->type,
            'visible_always' => $field->visible_always,
            'is_hidden' => $field->hide,
            'read_only' => $field->only_read,
            'can_edit' => 1,//!$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
            'color' => $field->label_color,
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
        $settings = app('settings');

        $fields = \DB::table('data_rows')->whereIn('id', $request->ids)->get();
        $field = $fields->first();
        if($field) {
            $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();
            $entity_class = $entity->model_name;
            $model_fields = $settings[$entity->name]['fields'];

            foreach($fields as $field) {
                $data = array(
                    'id' => $field->id,
                    'user_id' => \Auth::user()->id,
                    'changed_by' => \Auth::user()->id,
                    'title' => $field->title,
                    'key' => $field->field,
                    'type' => $field->type,
                    'visible_always' => $field->visible_always,
                    'is_hidden' => $field->hide,
                    'read_only' => $field->only_read,
                    'can_edit' => 1,//!$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0,
                    'color' => $field->label_color,
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
    }

    public function delete(Request $request, int $id) {
        
        $field = \DB::table('data_rows')->where(['id' => $id])->first();
        $subfields = \DB::table('data_rows')->where(['group_id' => $id])->get();
        $row_type = \DB::table('data_types')->where('id', $field->data_type_id)->first();
        
        \Schema::table($row_type->slug, function($table) use($field){
            if (\Schema::hasColumn($table->getTable(), $field->field)) {
                $table->dropColumn($field->field);
            }
        });
        \DB::table('data_rows')->where(['id' => $id])->whereNull('is_permanent')->delete();
        \DB::table('data_rows')->where(['group_id' => $id])->update(['group_id' => null]);//->whereNull('is_permanent')->delete();

        \App\Models\Settings::clear_cache();
        $settings = \App\Models\Settings::get(true);
        \App\Events\FieldUpdated::dispatch('FieldDeleted', array('id' => $id, 'user_id' => \Auth::user()->id, 'changed_by' => \Auth::user()->id, 'slug' => $row_type->slug));

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

        foreach($fields as $field) {
            \Schema::table($model, function($table) use($field){
                if (\Schema::hasColumn($table->getTable(), $field->field)) {
                    $table->dropColumn($field->field);
                }
            });
        }

        if($subfields) {
            \DB::table('data_rows')->whereIn(['group_id' => $request->ids])->update(['group_id' => null]);
        }
        
        
        \App\Models\Settings::clear_cache();
        $settings = \App\Models\Settings::get(true);
        
        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "fields/$model", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "fields/$model", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "fields/$model", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json($fields);
    }
}