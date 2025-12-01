<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\EntityObject;

class SettingsController extends Controller
{
    public function account(Request $request)
    {
        $tenant = tenant('id');
        $settings = \DB::table('settings')->where([
            'type' => 'account'
        ])->get();
        if(!count($settings) || count($settings) < 4) {
            info('generate');
            \DB::table('settings')->insert([
                'key' => 'name',
                'display_name' => 'Название портала',
                'type' => 'account',
                'value' => $tenant
            ]);
            \DB::table('settings')->insert([
                'key' => 'timezone',
                'display_name' => 'Часовой пояс',
                'type' => 'account',
                'value' => 'Europe/Moscow'
            ]);

            \DB::table('settings')->insert([
                'key' => 'city',
                'display_name' => 'Город',
                'type' => 'account',
                'value' => 'Москва'
            ]);
            \DB::table('settings')->insert([
                'key' => 'login_days',
                'display_name' => 'Сколько дней хранить историю входа',
                'type' => 'account',
                'value' => 30
            ]);
            \DB::table('settings')->insert([
                'key' => 'docs_email',
                'display_name' => 'E-mail для получения документов',
                'type' => 'account',
                'value' => ''
            ]);
            \DB::table('settings')->insert([
                'key' => 'payment_method',
                'display_name' => 'Способ оплаты',
                'type' => 'payment',
                'value' => ''
            ]);

            \DB::table('settings')->insert([
                'key' => 'hints',
                'display_name' => 'Отображение подсказок',
                'type' => 'account',
                'value' => ''
            ]);

            $settings = \DB::table('settings')->where([
                'type' => 'account'
            ])->get();
        }
        
        $timezones = \DB::table('timezones')->get();
        
        $common = array();
        foreach($settings as $s) {
            $common[$s->key] = $s->key == 'hints' ? (int)$s->value : $s->value;
        }

        $timezones_data = array();
        foreach($timezones as $timezone) {
            $timezones_data[] = array(
                'label' => array('text' => $timezone->name),
                'value' => $timezone->value
            );

        }
        $pm = \DB::table('settings')->where([
                'key' => 'payment_method',
                'type' => 'payment'
            ])->first();
        $data = array(
            //'list' => $settings,
            'timezones' => $timezones_data,
            'common' => $common,
            'payment_method' => $pm ? $pm->value : ''
        );

        return response()->json($data);
    }

    public function account_update(Request $request)
    {
        info('account_update');
        info($request->all());
        if($request->timezone) {
            \DB::table('settings')->where([
                'key' => 'timezone',
                'type' => 'account'
            ])->update(['value' => $request->timezone]);
        }
        if($request->city) {
            \DB::table('settings')->where([
                'key' => 'city',
                'type' => 'account'
            ])->update(['value' => $request->city]);
        }
        if($request->login_days) {
            \DB::table('settings')->where([
                'key' => 'login_days',
                'type' => 'account'
            ])->update(['value' => $request->login_days]);
        }
        if($request->has('docs_email')) {
            \DB::table('settings')->where([
                'key' => 'docs_email',
                'type' => 'account'
            ])->update(['value' => $request->docs_email]);
        }
        if($request->has('payment_method')) {
            \DB::table('settings')->where([
                'key' => 'payment_method',
                'type' => 'account'
            ])->update(['value' => $request->payment_method]);
            if(!\DB::table('settings')->where([
                'key' => 'payment_method',
                'type' => 'payment'
            ])->first())
                \DB::table('settings')->insert([
                    'key' => 'payment_method',
                    'display_name' => 'Способ оплаты',
                    'type' => 'payment',
                    'value' => $request->payment_method
                ]);
        }

        if($request->has('hints')) {
            \DB::table('settings')->where([
                'key' => 'hints',
                'type' => 'account'
            ])->update(['value' => (int)$request->hints]);
            if(!\DB::table('settings')->where([
                'key' => 'hints',
                'type' => 'account'
            ])->first())
                \DB::table('settings')->insert([
                    'key' => 'hints',
                    'display_name' => 'Отображение подсказок',
                    'type' => 'account',
                    'value' => (int)$request->hints
                ]);
        }

        $settings = \DB::table('settings')->where([
                'type' => 'account'
            ])->get();

        $timezones = \DB::table('timezones')->get();

        $data = array(
            'list' => $settings,
            'timezones' => $timezones
        );

        return response()->json($data);
    }

    public function compose(Request $request)
    {
        $table = \App\Models\Table::roles();
        if(isset($table['error'])) {
            return response()->json([
                    'message' => $table['error']['message']
                ], $table['error']['code']);
        }
        $sort_field = 'id';
        $sort_order = 'desc';
        if($request->sort_field && $request->sort_order && $request->sort_field != 'null') {
            $sort_field = $request->sort_field;
            $sort_order = $request->sort_order;
        } else {
            foreach($table as $column) {
                if($column['sort_order']) {
                    $sort_field = $column['key'];
                    $sort_order = $column['sort_order'];
                    break;
                }
            }
        }
        
        $sort_params = ['sort_order' => $sort_order, 'sort_field' => $sort_field];
        $request->merge($sort_params);
        $list = EntityObject::list('users', $request);

        $roles = Role::list();

        $data = array(
            'list' => $list,
            'table' => $table,
            'roles' => $roles
        );

        return response()->json($data);
    }
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
                if(isset($settings['list_values'][$field->id])) {
                    $data[$field->field]['options'] = $settings['list_values'][$field->id];
                };
                if($field->type == 'relation') {
                    $data[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                }
            }
        }

        return response()->json($data);
    }
}