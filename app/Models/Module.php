<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nwidart\Modules\Facades\Module as ModuleConfig;
use Auth;

class Module extends Model
{
    public function statusesEntities($entity, $id)
    {
        $s = get_settings();
        $entity_model = $s['models'][$entity]->model_name;
        $fields = $s[$entity]['fields'];
        $current = $entity_model::withTrashed()->where('id', $id)->first();
        $data = array();
        $required_fields = \DB::table('module_required_fields')->where('module', $this->slug)->get()->groupBy('entity')->toArray();

        foreach($fields as $field) {
            if($field->type == 'relation'
               && $field->is_plural
               && $field->relation_table 
               && array_key_exists($field->relation_table, $required_fields)
            ) {
                $entity_obj = $s['models'][$field->relation_table];
                $data[$field->relation_table] = array(
                    'id' => $entity_obj->id,
                    'title' => $entity_obj->title_plural,
                    'slug' => $entity_obj->slug,
                    'description' => '',
                    'status' => 1
                );
                if($current->{$field->relation_table}->count()) {
                    foreach($current->{$field->relation_table} as $relation) {
                        foreach($required_fields[$field->relation_table] as $relation_field) {
                            if(!$relation->{$relation_field->field}) {
                                $data[$field->relation_table]['status'] = 0;
                                break;
                            }
                        }
                    }
                } else {
                    $data[$field->relation_table]['status'] = 0;
                }
            } elseif($field->type == 'relation'
               && array_key_exists($field->relation_table, $required_fields)
            ) {
                $entity_obj = $s['models'][$field->relation_table];
                $data[$field->relation_table] = array(
                    'id' => $entity_obj->id,
                    'title' => $entity_obj->title_plural,
                    'slug' => $entity_obj->slug,
                    'description' => '',
                    'status' => 1
                );
                if($current->{$field->field}) {
                    foreach($required_fields[$field->relation_table] as $relation_field) {
                        $relation = $current->{$entity_obj->slug_singular};
                        if($relation && !$relation->{$relation_field->field}) {
                            $data[$field->relation_table]['status'] = 0;
                            break;
                        }
                    }
                } else {
                    $data[$field->relation_table]['status'] = 0;
                }
            }
        }

        return array_values($data);
    }
}
