<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu
{
    public static function get($slug, $settings = null)
    {
        $s = $settings ? $settings : app('settings');
        $user = \Auth::user() ? \Auth::user() : \App\Models\User::find(1);
        $item = \DB::table('settings')->where([
            'entity' => $slug,
            'type' => 'menu',
            'user_id' => $user->id
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
                'user_id' => $user->id
            ]);
            $item = \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
                'user_id' => $user->id
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
                
                $details = json_decode($field->details, true);
                $tab_slug = $field->relation_table ?: ($details['table'] ?? null);

                foreach($menu as $k => $menu_item) {
                    if($menu_item['tab'] == $field->field) {
                        if($menu_item['title'] != $field->title) {
                            $menu[$k]['title'] = $field->title;
                            $count_new++;
                        }
                        if($tab_slug && (!isset($menu_item['slug']) || $menu_item['slug'] != $tab_slug)) {
                            $menu[$k]['slug'] = $tab_slug;
                            $count_new++;
                        }
                        $need_create = false;
                        break;
                    }
                }
                if($need_create) {
                    if($tab_slug) {
                        $max_id++;
                        $menu[] = array(
                            'title' => $field->title,
                            'tab' => $field->field,
                            'slug' => $tab_slug,
                            'sort' => $max_id,
                            'enabled' => 1,
                            'id' => $max_id
                        );

                        $count_new++;
                    }
                }
            }
        }
        $permissions = $user->role->permissions->keyBy('entity_id');

        $permissions = $user->role->permissions->keyBy('entity_id');

        if($count_new) {
            \DB::table('settings')->where('id', $item->id)->update(['value' => json_encode($menu)]);
        }
        foreach($menu as $k => $menu_item) {
            info($item->id);
            if(isset($menu_item['slug']) && isset($s['models'][$menu_item['slug']]) && 
                isset($permissions[$s['models'][$menu_item['slug']]->id]) && 
                $permissions[$s['models'][$menu_item['slug']]->id]->read_p == 'N' && !$user->is_admin ||
                isset($menu_item['slug']) && !$s['models'][$menu_item['slug']]->enable
            ) {
                unset($menu[$k]);
            } elseif(isset($menu_item['has_roles_read']) && $menu_item['has_roles_read'] && isset($menu_item['roles_read']) && count($menu_item['roles_read']) && !in_array($user->role_id, $menu_item['roles_read']) && !$user->is_admin) {
                unset($menu[$k]);
            } elseif(in_array($slug, ['logistic_tasks', 'pickups', 'deals', 'payment_invoices', 'expense_invoices', 'product_returns', 'addresses']) && isset($menu_item['tab']) && $menu_item['tab'] == 'products' && !$user->is_admin
                && isset($s[$slug]['perms']['products']['read']) && !$s[$slug]['perms']['products']['read']) {
                unset($menu[$k]);
            } elseif(isset($menu_item['tab']) && $menu_item['tab'] == 'modules') {
                foreach($menu_item['childs'] as $i => $child) {
                    if(!isset($s['modules'][$child['alias']]) || !$s['modules'][$child['alias']]->enabled)
                        unset($menu[$k]['childs'][$i]);
                }
            }
        }
        foreach($menu as $k => $menu_item) {
            if(isset($menu_item['tab']) && $menu_item['tab'] == 'modules' && !count($menu_item['childs'])) {
                unset($menu[$k]);
            } elseif(isset($menu_item['tab']) && $menu_item['tab'] == 'modules') {
                $menu[$k]['childs'] = array_values($menu_item['childs']);
            }
        }
        

        return array_values($menu);
    }

    public static function tabs($slug)
    {
        $user = \Auth::user() ? \Auth::user() : \App\Models\User::find(1);
        $menu = array();
        $item = \DB::table('settings')->where([
            'entity' => $slug,
            'type' => 'tabs',
            'user_id' => $user->id
        ])->first();

        if($item) {
            $menu = json_decode($item->value);
        } else {
            $item = \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'tabs'
            ])->first();
            if($item) {
                \DB::table('settings')->insert([
                    'key' => $item->key,
                    'display_name' => $item->display_name,
                    'value' => $item->value,
                    'entity' => $slug,
                    'type' => 'tabs',
                    'user_id' => $user->id
                ]);
                $item = \DB::table('settings')->where([
                    'entity' => $slug,
                    'type' => 'tabs',
                    'user_id' => $user->id
                ])->first();

                $menu = json_decode($item->value);
            }
        }
        return $menu;
    }
}