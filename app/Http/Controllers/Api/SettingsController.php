<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
	public function getFields($slug, Request $request)
    {
    	$settings = get_settings();

    	$entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();
        $visible_fields = \App\Models\Field::getVisibleFields($slug);

        $users = \App\Models\User::get(['id', 'name', 'last_name'])->keyBy('id')->toArray();
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        $data = array();
        foreach($model_fields as $field) {
            if($field->type == 'status')
                $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
            $perms['read'][$field->field] = (!optional($request->user())->canRead($field->field, $slug) ? 'disabled':'');
            $perms['write'][$field->field] = (!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'');
            if(!array_key_exists($field->field, $data)) {
                $data[$field->field] = array(
                    'type' => $field->type,
                    'read_only' => $field->only_read,
                    'can_edit' => !$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0,
                    'color' => $field_colors[$field->field]
                );
                if(isset($settings[$slug]['list_values'][$field->field])) {
                    $data[$field->field]['options'] = $settings[$slug]['list_values'][$field->field];
                };
                if($field->type == 'relation') {
                    $data[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                }
            }
        }

        return response()->json($data);
    }
}