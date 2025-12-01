<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\ValueHelper;
use App\Services\SearchService;

class KnowledgeController extends Controller
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function list(Request $request)
    {
        if($request->filter)
            $add_params = $request->all();
        else
            $add_params = [];
        $add_params['filter']['is_active'] = 1;
        $request->merge($add_params);
        $list = \App\Models\EntityObject::list('knowledge', $request);
      
        $categories = \App\Models\KnowledgeCategory::get()->toTree()->toArray();

        foreach($categories as $k => $category) {
            
            if($name = json_decode($category['name'], true)) {
                $categories[$k]['name'] = $name['value'];
            }

            foreach($category['children'] as $i => $child) {
                if($name = json_decode($child['name'], true)) {
                    $categories[$k]['children'][$i]['name'] = $name['value'];
                }
            }

            
        }
        $data = array(
            'list' => $list,
            'categories' => $categories
        );

        return response()->json($data);
    }

    public function detail($slug, Request $request)
    {
        $object = \App\Models\Knowledge::whereJsonContains('slug->value', $slug)->orWhere('slug', $slug)->first();
        if(!$object) {
            return [
                'error' => array(
                    'message' => 'Object not found',
                    'code' => 404
                )
            ];
        }
        $slug = 'knowledge';
        $id = $object->id;
        $settings = app('settings');
        $entity = $settings['models'][$slug];

        if(!$entity) {
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
        if($id)
            $current = $entity_class::withTrashed()->where(['id' => $id])->first();

        if(!$current) {
            return [
                'error' => array(
                    'message' => 'Object not found',
                    'code' => 404
                )
            ];
        }

        foreach($model_fields as $field) {
                    
            if(!array_key_exists($field->field, $fields_data)) {
                if($field->type == 'relation' && $field->relation_table && !$settings['models'][$field->relation_table]->enable)
                    continue;
                if($field->type == 'status')
                    $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
                
                $val = (string)$current->{$field->field};
                $field_value = ValueHelper::isJson($val) && is_array(json_decode($val, true)) ? json_decode($val, true) : $val;
                if($field->type == 'relation' && $field->is_plural && $field->relation_table) {
                    $relation_table = $field->relation_table;

                    $field_value = $current->{$relation_table}->pluck('id')->toArray();
                }

                $fields_data[$field->field] = $settings[$slug]['field_data'][$field->field];
                $fields_data[$field->field]['value'] = $field->field == 'password' ? '' : $field_value;

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

                if(isset($settings['list_values'][$field->id])) {
                    $values = array();
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true);
                        if($field->is_plural && isset($fields_data[$field->field]['value']['value'])) {
                            foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                                $field_values[$field_val] = $settings['list_values'][$field->id][$field_val];
                            }
                        } elseif($current->{$field->field} && isset($settings['list_values'][$field->id][$current->{$field->field}])) {
                            $field_values[$current->{$field->field}] = $settings['list_values'][$field->id][$current->{$field->field}];
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
                                    $field_values[$field_val] = $settings['list_values'][$field->id][$field_val]['value'];
                                }
                            } elseif($current->{$field->field} && isset($settings['list_values'][$field->id][$current->{$field->field}])) {
                                $field_values[$current->{$field->field}] = $settings['list_values'][$field->id][$current->{$field->field}];
                            } elseif($current->{$field->field}) {
                                $field_values[$current->{$field->field}] = null;
                            }
                        } else {
                            $field_values = $settings['list_values'][$field->id];
                        }
                        foreach($field_values as $k => $option) {
                            $simple_options[$k] = $option;
                            $values[] = $option;
                        }
                    }
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
                            'value' => $current->{$subfield->field},
                            'sort' => $subfield->sort
                        );
                    }
                }
            }
        }

        if(!\DB::table('views_content')->where([
                'ip' => $request->ip(),
                'entity' => 'knowledge',
                'entity_id' => $id
            ])->exists()) {
            \DB::table('views_content')->insert([
                [
                    'ip' => $request->ip(),
                    'entity' => 'knowledge',
                    'entity_id' => $id
                ]
            ]);
            $object->views++;
            $object->timestamps = false;
            $object->saveQuietly();
        }

        return response()->json($fields_data);
    }

    public function search(Request $request)
    {
        $result = $this->searchService->find($request->all());

        return response()->json($result);
    }
}