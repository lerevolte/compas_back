<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Models\SidebarItem;
use App\Helpers\ValueHelper;

class TrashController extends Controller
{
	public function index(Request $request)
    {
        $settings = get_settings();
        $limit = $request->per_page ? $request->per_page : 25;
        $page = $request->page ? $request->page : 1;
        $sort_field = $request->sort_field ? $request->sort_field : 'id';
        $sort_order = $request->sort_order ? $request->sort_order : 'desc';
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        
        $tables = \Auth::user()->tables;
        if($tables)
            $tables = json_decode($tables, true);
        $types = collect(\DB::table('data_types')->where('enable', 1)->get())->keyBy('model_name')->toArray();
        $type_ids = array();
        foreach($types as $model => $entity) {
            if(!strstr($model,'TCG') && $model::onlyTrashed()->first()) {
                $type_ids[] = $entity->id;

            }
        }
        $res = array(
            'tables' => array(),
            'objects' => array(),
            'menu_items' => array()
        );
        $fields = \DB::table('data_rows')->whereIntegerInRaw('data_type_id', $type_ids)->where('is_remove', 0)->orderBy('sort')->get()->groupBy('data_type_id')->toArray();

        foreach($types as $model => $entity) {
            if(!strstr($model,'TCG') && $model::onlyTrashed()->first() && isset($fields[$entity->id])) {
                $res['menu_items'][] = $entity;
                if(isset($tables[$entity->slug])) {
                    $entity_class = $entity->model_name;
                    $model_fields = collect($fields[$entity->id]);
                    $table_columns = collect($tables[$entity->slug]);
                    $table_columns = $table_columns->keyBy('key')->toArray();
                    foreach($table_columns as $key => $column) {
                        if(!$model_fields->contains('field', $key) && $key != 'isChoose' && $key != 'actions' && $key != 'iconDrag' && $key != 'iconDelete')
                            unset($table_columns[$key]);
                    }

                    foreach ($model_fields as $field) {
                        $field_values = array();
                        $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
                        if(isset($settings[$entity->slug]['list_values'][$field->field])) {
                            if($field->type == 'relation') {
                                $field_values = array_slice($settings[$entity->slug]['list_values'][$field->field], 0, 19, true);
                            } else {
                                $field_values = $settings[$entity->slug]['list_values'][$field->field];
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
                            );
                            $table_columns[$field->field]['type'] = $field->type;
                            $table_columns[$field->field]['read_only'] = $field->only_read;
                            $table_columns[$field->field]['can_edit'] = 1;//!$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0;
                            $table_columns[$field->field]['color'] = $field_colors[$field->field];
                            $table_columns[$field->field]['is_plural'] = $field->is_plural;
                            $table_columns[$field->field]['options'] = $field_values;
                            if($field->type == 'relation') {
                                $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
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
                            );
                            $table_columns[$field->field]['type'] = $field->type;
                            $table_columns[$field->field]['read_only'] = $field->only_read;
                            $table_columns[$field->field]['can_edit'] = !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0;
                            $table_columns[$field->field]['color'] = $field_colors[$field->field];
                            $table_columns[$field->field]['is_plural'] = $field->is_plural;
                            $table_columns[$field->field]['options'] = $field_values;
                            if($field->type == 'relation') {
                                $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                            }
                        }
                        
                        
                    }
                } else {
                    $model_fields = collect($fields[$entity->id]);
                    $table_columns = array();

                    foreach ($model_fields as $field) {
                        $field_values = array();
                        $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
                        if(isset($settings[$entity->slug]['list_values'][$field->field])) {
                            if($field->type == 'relation') {
                                $field_values = array_slice($settings[$entity->slug]['list_values'][$field->field], 0, 19, true); 
                            } else {
                                $field_values = $settings[$entity->slug]['list_values'][$field->field];
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
                            );
                            $table_columns[$field->field]['type'] = $field->type;
                            $table_columns[$field->field]['read_only'] = $field->only_read;
                            $table_columns[$field->field]['can_edit'] = !$settings[$entity->slug]['perms'][$field->field]['write'] ? 1 : 0;
                            $table_columns[$field->field]['color'] = $field_colors[$field->field];
                            $table_columns[$field->field]['is_plural'] = $field->is_plural;
                            $table_columns[$field->field]['options'] = $field_values;
                            if($field->type == 'relation') {
                                $table_columns[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                            }
                        }
                        
                    }
                    
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
                $table_columns = array_values($table_columns);

                $res['tables'][$entity->slug] = $table_columns;

                $entity_class = $entity->model_name;
                $paginator = $entity_class::orderBy($sort_field, $sort_order)->onlyTrashed();
                if($request->filter && is_array($request->filter)){
                    foreach($request->filter as $field => $val) {
                        if($field == 'created_at' || $field == 'updated_at')
                            $paginator = $paginator->whereDate($field, $val);
                        else {
                            if(isset($settings[$entity->slug]['fields'][$field]) && $settings[$entity->slug]['fields'][$field]->is_plural) {
                                if($settings[$entity->slug]['fields'][$field]->type == 'relation')
                                    $paginator = $paginator->whereJsonContains($field, (int)$val);
                                else {
                                    //->whereRaw("json_contains(`client_id`, ?)", [15])->whereRaw('json_contains(`tip_tk`, \'"'.$str.'"\')')
                                    $paginator = $paginator->whereRaw('json_contains('.$field.', \'"'.$val.'"\')');
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
                                    $paginator = $paginator->where($field, $val);
                                }
                                
                            }
                            
                        }
                    }
                }

                if($request->q) {
                    $search_columns = $model_fields->filter(function ($field) {
                                        return ($field->type != 'relation' && $field->type != 'status' && $field->type != 'text_group');
                                    })->pluck('field')->toArray();
                    $q = $request->q;
                    $paginator = $paginator->where(function ($query) use ($search_columns, $q) {
                        foreach ($search_columns as $column) {
                            $query->orWhere($column, 'like', "%{$q}%");
                        }
                    });
                };

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
                            $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;
                            if($field->type == 'relation' && $field->is_plural) {
                                $values = $data[$field->field];
                                $data[$field->field] = array();
                                if(is_array($values)) {
                                    foreach($values as $val) {
                                        if(isset($settings[$entity->slug]['list_values'][$field->field][$val]))
                                            $data[$field->field][] = $settings[$entity->slug]['list_values'][$field->field][$val];
                                    }
                                }
                            } elseif($field->type == 'relation' && isset($settings[$entity->slug]['list_values'][$field->field][$value])) {
                                $data[$field->field] = $settings[$entity->slug]['list_values'][$field->field][$value];
                            } elseif($field->type == 'relation') {
                                $data[$field->field] = null;
                            };
                        }
                    }
                    
                    $objects[] = $data;
                }
                $res['objects'][$entity->slug] = array(
                    'count' => $paginator->count(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'data' => $objects
                );
            }


        }

    	return response()->json($res);
    }

}