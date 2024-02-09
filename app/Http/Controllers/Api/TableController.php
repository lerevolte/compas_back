<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TableController extends Controller
{
    public function get($slug, Request $request)
    {
        
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        $user = \Auth::user();        
        $tables = \Auth::user()->tables;
        if($tables)
            $tables = json_decode($tables, true);
        else
            $tables = array();
        if(!isset($tables[$slug])) {
            $role = \App\Models\Role::find(\Auth::user()->role_id);
            if($role && $role->tables) {
                $role_tables = json_decode($role->tables, true);
                if(!is_array($role_tables))
                    $role_tables = array();
                if(isset($role_tables[$slug])) {
                    $tables[$slug] = $role_tables[$slug];
                    $user->tables = json_encode($tables);
                    $user->saveQuietly();
                }
            }

            if(!isset($tables[$slug])) {
                $settings = \DB::table('settings')->where('key', 'tables')->first();
                if($settings->value) {
                    $tables_all = json_decode($settings->value, true);
                    if(isset($tables_all[$slug])) {
                        $tables[$slug] = $tables_all[$slug];
                        $user->tables = json_encode($tables);
                        $user->saveQuietly();
                    }
                }
            }
        }
        $settings = get_settings();

        if(isset($tables[$slug])) {
            $entity = \DB::table('data_types')->where('slug', $slug)->first();
            $entity_class = $entity->model_name;
            $model_fields = $entity_class::getFields();
            $table_columns = collect($tables[$slug]);
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
                if(isset($settings[$slug]['list_values'][$field->field])) {
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings[$slug]['list_values'][$field->field], 0, 19, true); 
                        // if($item->{$field->field})
                        //     $field_values[$item->{$field->field}] = $settings[$slug]['list_values'][$field->field][$item->{$field->field}];
                    } else {
                        $field_values = $settings[$slug]['list_values'][$field->field];
                    }
                };
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->type != 'password') {
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_name,
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
                        'read_only' => $field->only_read,
                        'unit' => $field->unit,
                        'mask' => $field->mask
                    );
                    $table_columns[$field->field]['type'] = $field->type;
                    $table_columns[$field->field]['read_only'] = $field->only_read;
                    $table_columns[$field->field]['can_edit'] = isset($settings[$slug]['perms'][$field->field]) && !$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0;
                    $table_columns[$field->field]['color'] = $field_colors[$field->field];
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = $field_values;
                    $table_columns[$field->field]['choosed'] = [];
                    if(isset($settings[$slug]['fields'][$field->field]->choosed))
                        $table_columns[$field->field]['choosed'] = $settings[$slug]['fields'][$field->field]->choosed;
                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        if($field->field == 'category_id')
                            $table_columns[$field->field]['can_create'] = 0;
                        else
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
                        'unit' => $field->unit,
                        'mask' => $field->mask
                    );
                    $table_columns[$field->field]['type'] = $field->type;
                    $table_columns[$field->field]['read_only'] = $field->only_read;
                    $table_columns[$field->field]['can_edit'] = !$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0;
                    $table_columns[$field->field]['color'] = $field_colors[$field->field];
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = $field_values;
                    $table_columns[$field->field]['choosed'] = [];
                    if(isset($settings[$slug]['fields'][$field->field]->choosed))
                        $table_columns[$field->field]['choosed'] = $settings[$slug]['fields'][$field->field]->choosed;
                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        if($field->field == 'category_id')
                            $table_columns[$field->field]['can_create'] = 0;
                        else
                            $table_columns[$field->field]['can_create'] = 1;
                    }
                }
                
                
            }
        } else {
            $entity = \DB::table('data_types')->where('slug', $slug)->first();
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
                if(isset($settings[$slug]['list_values'][$field->field])) {
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings[$slug]['list_values'][$field->field], 0, 19, true); 
                        // if($item->{$field->field})
                        //     $field_values[$item->{$field->field}] = $settings[$slug]['list_values'][$field->field][$item->{$field->field}];
                    } else {
                        $field_values = $settings[$slug]['list_values'][$field->field];
                    }
                };
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $field->type != 'password') {
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_name,
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
                        'read_only' => $field->only_read,
                        'unit' => $field->unit,
                        'mask' => $field->mask
                    );
                    $table_columns[$field->field]['type'] = $field->type;
                    $table_columns[$field->field]['read_only'] = $field->only_read;
                    $table_columns[$field->field]['can_edit'] = !$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0;
                    $table_columns[$field->field]['color'] = $field_colors[$field->field];
                    $table_columns[$field->field]['is_plural'] = $field->is_plural;
                    $table_columns[$field->field]['is_hidden'] = $field->hide;
                    $table_columns[$field->field]['visible_always'] = $field->visible_always;
                    $table_columns[$field->field]['options'] = $field_values;
                    if($field->type == 'relation') {
                        $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                        if($field->field == 'category_id')
                            $table_columns[$field->field]['can_create'] = 0;
                        else
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
        $user = \Auth::user();
        $tables = $user->tables;
        if($tables)
            $tables = json_decode($tables, true);
        if(!is_array($tables))
            $tables = array();
        $tables[$slug] = $request->fields;
        $user->tables = json_encode($tables);
        $user->save();
        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "tables/$slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "tables/$slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "tables/$slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json($tables[$slug]);
    }

    public function set_role($slug, $role_id, Request $request)
    {
        // $role = \Auth::user()->role;
        // $role_tables = $role->tables;
        // if($role_tables)
        //     $role_tables = json_decode($role_tables, true);
        // if(!is_array($role_tables))
        //     $role_tables = array();

        // $role_tables[$slug] = $request->fields;
        // $role->tables = json_encode($role_tables);
        // $role->save();

        
        $users = \App\Models\User::where('role_id', $role_id)->get();
        foreach($users as $user) {
            $tables = $user->tables;
            if($tables)
                $tables = json_decode($tables, true);
            if(!is_array($tables))
                $tables = array();
            $tables[$slug] = $request->fields;
            $user->tables = json_encode($tables);
            $user->saveQuietly();
        }

        $role = \App\Models\Role::find($role_id);
        $tables = $role->tables;
        if($tables)
            $tables = json_decode($tables, true);
        if(!is_array($tables))
            $tables = array();
        $tables[$slug] = $request->fields;
        $role->tables = json_encode($tables);
        $role->saveQuietly();

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "tables/$slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "tables/$slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "tables/$slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json($request->fields);
    }

    public function set_all($slug, Request $request)
    {
        $users = \App\Models\User::get();
        foreach($users as $user) {
            $tables = $user->tables;
            if($tables)
                $tables = json_decode($tables, true);
            if(!is_array($tables))
                $tables = array();
            $tables[$slug] = $request->fields;
            $user->tables = json_encode($tables);
            $user->save();
        }

        $settings = \DB::table('settings')->where('key', 'tables')->first();
        $tables = $settings->value;
        if($tables)
            $tables = json_decode($tables, true);
        if(!is_array($tables))
            $tables = array();
        $tables[$slug] = $request->fields;
        \DB::table('settings')->where('key', 'tables')->update(['value' => json_encode($tables)]);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "tables/$slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "tables/$slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "tables/$slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json($tables[$slug]);
    }

    public function get_order_products(Request $request)
    {
        $tables = \Auth::user()->tables;
        if($tables)
            $tables = json_decode($tables, true);

        if(isset($tables['order_products'])) {
            //$entity = \DB::table('data_types')->where('slug', 'remnants')->first();
            $entity = \DB::table('data_types')->where('slug', 'products')->first();
            $entity_class = $entity->model_name;
            $model_fields = $entity_class::getFields();
            $table_columns = collect($tables['order_products']);
            $table_columns = $table_columns->keyBy('key')->toArray();
            foreach($table_columns as $key => $column) {
                if(!$model_fields->contains('field', $key) && $key != 'isChoose' && $key != 'actions' && $key != 'remnant_name' && $key != 'product_name' && $key != 'product_price' && $key != 'product_count' && $key != 'product_weight' && $key != 'product_sum' && $key != 'iconDrag' && $key != 'iconDelete' || $key == 'price' || $key == 'name')
                    unset($table_columns[$key]);
            }
            foreach ($model_fields as $field) {
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group' && $key != 'price' && $key == 'name') {
                    $table_columns[$field->field] = array(
                        'id' => $field->id,
                        'title' => $field->display_name,
                        'key' => $field->field,
                        'width' => '200px',
                        'enabled' => 0,
                        'sort_order' => '',
                        'type' => $field->type,
                        'fixed' => '',
                        'index' => count($table_columns) + 1,
                        'fixTarget' => '0px',
                        'read_only' => $field->only_read,
                        'unit' => $field->unit,
                        "mask" => ""
                    );
                }
            }
            if(!isset($table_columns['product_name']))
                $table_columns['product_name'] = array(
                    'id' => null,
                    'title' => 'Наименование товара',
                    'key' => 'product_name',
                    'width' => '200px',
                    'enabled' => 1,
                    'sort_order' => '',
                    'type' => 'text',
                    'fixed' => '',
                    'index' => 0,
                    'fixTarget' => '0px',
                    'read_only' => 0,
                    'related_table' => 'products',
                    "mask" => ""
                );
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
                    "mask" => ""
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
                    "mask" => ""
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
                    "mask" => ""
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
                    'index' => 4,
                    'fixTarget' => '0px',
                    'read_only' => 1,
                    "mask" => ""
                );
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
                    "mask" => ""
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
                    "mask" => ""
                );
            }
            $table_columns = array_values($table_columns);

            return response()->json($table_columns);
        } else {

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
            $table_columns['product_name'] = array(
                'id' => null,
                'title' => 'Наименование товара',
                'key' => 'product_name',
                'width' => '200px',
                'enabled' => 1,
                'sort_order' => '',
                'type' => 'text',
                'fixed' => '',
                'index' => 0,
                'fixTarget' => '0px',
                'read_only' => 0,
                'related_table' => 'products',
                "mask" => ""
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
                "mask" => ""
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
                "mask" => ""
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
                "mask" => ""
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
                'index' => 4,
                'fixTarget' => '0px',
                'read_only' => 1,
                "mask" => ""
            );

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
                    "mask" => ""
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
                    "mask" => ""
                );
            }
            return response()->json(array_values($table_columns));
        }
    }
}