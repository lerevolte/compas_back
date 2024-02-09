<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu
{
    public static function get($slug)
    {
        $s = get_settings();
        $item = \DB::table('settings')->where([
            'entity' => $slug,
            'type' => 'menu',
            'user_id' => \Auth::user()->id
        ])->first();

        if(!$item) {
            $item = \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
                //'user_id' => \Auth::user()->id
            ])->first();
            \DB::table('settings')->insert([
                'key' => $item->key,
                'display_name' => $item->display_name,
                'value' => $item->value,
                'entity' => $slug,
                'type' => 'menu',
                'user_id' => \Auth::user()->id
            ]);
            $item = \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
                'user_id' => \Auth::user()->id
            ])->first();
        }
        $menu = json_decode($item->value, true);

        $max_id = 0;
        foreach($menu as $k => $menu_item) {
            if($menu_item['id'] > $max_id)
                $max_id = $menu_item['id'];
        }
        $count_new = 0;
        foreach($s[$slug]['fields'] as $field) {
            if($field->type == 'relation' && $field->is_plural && $field->field != 'role_id' && $field->field != 'category_id') {
                $need_create = true;
                
                foreach($menu as $k => $menu_item) {
                    if($menu_item['tab'] == $field->field) {
                        $need_create = false;
                        break;
                    }
                }
                if($need_create) {
                    $details = json_decode($field->details, true);
                    if(isset($details['table'])) {
                        $max_id++;
                        $menu[] = array(
                            'title' => $field->display_name,
                            'tab' => $field->field,
                            'slug' => $details['table'],
                            'sort' => $max_id,
                            'enabled' => 1,
                            'id' => $max_id
                        );
                        $count_new++;
                    }
                }
            }
        }
        if($count_new) {
            \DB::table('settings')->where('id', $item->id)->update(['value' => json_encode($menu)]);
        }

        return $menu;
    }
}