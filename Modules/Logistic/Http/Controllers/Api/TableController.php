<?php

namespace Modules\Logistic\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TableController extends Controller
{
    public function get($slug, Request $request)
    {
        $settings = get_settings();
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        
        $tables = \Auth::user()->tables;
        if($tables)
            $tables = json_decode($tables, true);

        if($slug == 'tasks' || $slug == 'route_tasks')
            $data_type = 'tasks';
        elseif($slug == 'routes')
            $data_type = 'routes';

        if(isset($tables['logistic_'.$slug])) {
            $entity = \DB::table('data_types')->where('slug', $data_type)->first();
            $entity_class = $entity->model_name;
            $model_fields = $entity_class::getFields();
            $table_columns = collect($tables['logistic_'.$slug]);
            $table_columns = $table_columns->keyBy('key')->toArray();
            foreach($table_columns as $key => $column) {
                if(!$model_fields->contains('field', $key) && $key != 'isChoose' && $key != 'actions' && $key != 'iconDrag' && $key != 'iconDelete')
                    unset($table_columns[$key]);
            }
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
                    "index" => 0
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
                    "fixTarget" => "40px"
                );
            }
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
                    "read_only" => 1
                );
            } else {
                if($data_type == 'routes') 
                    unset($table_columns['iconDrag']);
            }
            foreach ($model_fields as $field) {
                $field_values = array();
                $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
                if(isset($settings[$data_type]['list_values'][$field->field])) {
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings[$data_type]['list_values'][$field->field], 0, 19, true); 
                    } else {
                        $field_values = $settings[$data_type]['list_values'][$field->field];
                    }
                };
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->type != 'password') {
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_name,
                        'key' => $field->field,
                        'width' => '200px',
                        'enabled' => 0,
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
                        'read_only' => $field->only_read,
                        'unit' => $field->unit
                    );
                    $table_columns[$field->field]['type'] = $field->type;
                    $table_columns[$field->field]['read_only'] = $field->only_read;
                    $table_columns[$field->field]['can_edit'] = !$settings[$data_type]['perms'][$field->field]['write'] ? 1 : 0;
                    $table_columns[$field->field]['color'] = $field_colors[$field->field];
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = $field_values;
                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        $table_columns[$field->field]['can_create'] = 1;
                        
                    }
                } elseif($field->type != 'text_group' && $field->type != 'password') {
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_name,
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
                        'read_only' => $field->only_read,
                        'unit' => $field->unit
                    );
                    $table_columns[$field->field]['type'] = $field->type;
                    $table_columns[$field->field]['read_only'] = $field->only_read;
                    $table_columns[$field->field]['can_edit'] = !$settings[$data_type]['perms'][$field->field]['write'] ? 1 : 0;
                    $table_columns[$field->field]['color'] = $field_colors[$field->field];
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = $field_values;
                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        $table_columns[$field->field]['can_create'] = 1;
                    }
                }
                
                
            }
        } else {
            $entity = \DB::table('data_types')->where('slug', $data_type)->first();
            $entity_class = $entity->model_name;
            $model_fields = $entity_class::getFields();
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
                    "index" => 0
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
                    "fixTarget" => "40px"
                );
            }
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
                    "read_only" => 1
                );
            } else {
                if($data_type == 'routes')
                    unset($table_columns['iconDrag']);
            }
            foreach ($model_fields as $field) {
                $field_values = array();
                $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
                if(isset($settings[$data_type]['list_values'][$field->field])) {
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings[$data_type]['list_values'][$field->field], 0, 19, true); 
                    } else {
                        $field_values = $settings[$data_type]['list_values'][$field->field];
                    }
                };
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->type != 'password') {
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_name,
                        'key' => $field->field,
                        'width' => '200px',
                        'enabled' => 0,
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
                        'read_only' => $field->only_read,
                        'unit' => $field->unit
                    );
                    $table_columns[$field->field]['type'] = $field->type;
                    $table_columns[$field->field]['read_only'] = $field->only_read;
                    $table_columns[$field->field]['can_edit'] = !$settings[$data_type]['perms'][$field->field]['write'] ? 1 : 0;
                    $table_columns[$field->field]['color'] = $field_colors[$field->field];
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = $field_values;
                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        $table_columns[$field->field]['can_create'] = 1;
                    }
                }
                
            }
            
        }
        
        $table_columns = array_values($table_columns);

        return response()->json($table_columns);
    }

    public function set($slug, Request $request)
    {
        info('UPDATE TABLE');
        $user = \Auth::user();
        $tables = $user->tables;
        if($tables)
            $tables = json_decode($tables, true);
        if(!is_array($tables))
            $tables = array();

        $tables['logistic_'.$slug] = $request->fields;
        $user->tables = json_encode($tables);
        $user->save();
        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "tables/$slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "tables/$slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "tables/$slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json($tables['logistic_'.$slug]);
    }

}