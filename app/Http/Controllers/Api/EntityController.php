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

class EntityController extends Controller
{
	public function list(Request $request)
    {
    	$entities = \DB::table('data_types')->select(['slug', 'display_name_singular', 'display_name_plural', 'color'])->get();

    	return response()->json($entities);
    }

    public function get_menu($slug, Request $request)
    {
        // $item = \DB::table('settings')->where([
        //     'entity' => $slug,
        //     'type' => 'menu',
        //     'user_id' => \Auth::user()->id
        // ])->first();
        // if(!$item) {
        //     $item = \DB::table('settings')->where([
        //         'entity' => $slug,
        //         'type' => 'menu',
        //         'user_id' => 1
        //     ])->first();
        //     \DB::table('settings')->insert([
        //         'key' => $item->key,
        //         'display_name' => $item->display_name,
        //         'value' => $item->value,
        //         'entity' => $slug,
        //         'type' => 'menu',
        //         'user_id' => \Auth::user()->id
        //     ]);
        //     $item = \DB::table('settings')->where([
        //         'entity' => $slug,
        //         'type' => 'menu',
        //         'user_id' => \Auth::user()->id
        //     ])->first();
        // }
        // $menu = json_decode($item->value, true);
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
        // echo '<pre>';
        // print_r($menu);
        // echo '</pre>';
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

        return response()->json($menu);
    }

    public function set_menu($slug, Request $request)
    {
        $menu = json_encode($request->menu, JSON_UNESCAPED_UNICODE);
        \DB::table('settings')->where([
            'entity' => $slug,
            'type' => 'menu',
            'user_id' => \Auth::user()->id
        ])->update(['value' => $menu]);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "entities/$slug/menu", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "entities/$slug/menu", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "entities/$slug/menu", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);
        //$entity = \DB::table('data_types')->where('slug', $slug)->first();

        return response()->json($request->menu);
    }

    public function set_menu_role($slug, $role_id, Request $request)
    {
        $menu = json_encode($request->menu, JSON_UNESCAPED_UNICODE);

        $users = \App\Models\User::where('role_id', $role_id)->get();
        foreach($users as $user) {
            $user_settings = \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
                'user_id' => $user->id
            ])->first();
            
            if($user_settings)
                \DB::table('settings')->where([
                    'entity' => $slug,
                    'type' => 'menu',
                    'user_id' => $user->id
                ])->update(['value' => $menu]);
            else
                \DB::table('settings')->insert([
                    'entity' => $slug,
                    'key' => 'menu',
                    'type' => 'menu',
                    'user_id' => $user->id,
                    'value' => $menu
                ]);
            // \DB::table('settings')->where([
            //     'entity' => $slug,
            //     'type' => 'menu',
            //     'user_id' => $user->id
            // ])->update(['value' => $menu]);

            $now = Carbon::now();
            if(\DB::table('local_cache')->where(['url' => "entities/$slug/menu", 'user_id' => $user->id])->exists())
                \DB::table('local_cache')->where(['url' => "entities/$slug/menu", 'user_id' => $user->id])->update(['updated_at' => $now]);
            else
                \DB::table('local_cache')->insert(['url' => "entities/$slug/menu", 'user_id' => $user->id, 'created_at' => $now, 'updated_at' => $now]);
        }

        $role = \App\Models\Role::find($role_id);
        $menus = $role->menus;
        if($menus)
            $menus = json_decode($menus, true);
        if(!is_array($menus))
            $menus = array();
        $menus[$slug] = $menu;
        $role->menus = json_encode($menus);
        $role->saveQuietly();

        return response()->json($request->menu);
    }

    public function set_menu_all($slug, Request $request)
    {
        $menu = json_encode($request->menu, JSON_UNESCAPED_UNICODE);

        $users = \App\Models\User::get();
        foreach($users as $user) {
            \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
                'user_id' => $user->id
            ])->update(['value' => $menu]);

            $now = Carbon::now();
            if(\DB::table('local_cache')->where(['url' => "entities/$slug/menu", 'user_id' => $user->id])->exists())
                \DB::table('local_cache')->where(['url' => "entities/$slug/menu", 'user_id' => $user->id])->update(['updated_at' => $now]);
            else
                \DB::table('local_cache')->insert(['url' => "entities/$slug/menu", 'user_id' => $user->id, 'created_at' => $now, 'updated_at' => $now]);
        }

        $settings_all = \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
                'user_id' => null
            ])->first();
        if($settings_all)
            \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
                'user_id' => null
            ])->update([
                'value' => $menu
            ]);
        else 
            $settings_all = \DB::table('settings')->insert([
                'entity' => $slug,
                'key' => 'menu',
                'type' => 'menu',
                'user_id' => null,
                'value' => $menu
            ]);

        return response()->json($request->menu);
    }

    public function enable(Request $request)
    {
        $query = \DB::table('data_types')->whereIntegerInRaw('id', $request->ids);
        $query->update(['enable' => 1]);
        $entities = $query->get(); 
        // $entity = \DB::table('data_types')->where([
        //     'name' => $slug,
        //     'enable' => 0,
        // ])->first();
        // if(!$entity) {
        //     return response()->json(
        //         array(
        //             'code' => 404,
        //             'error' => 'Сущность не найдена',
        //         )
        //     );
        // }
        foreach ($entities as $entity) {
            $side_item = new \App\Models\SidebarItem;
            $side_item->name = $entity->display_name_plural;
            $side_item->slug = $entity->name;
            $side_item->link = "/objects/".$entity->name;
            $side_item->save();
        }
        
        \App\Models\SidebarItem::fixTree();
        //\DB::table('data_types')->where('name', $slug)->update(['enable' => 1]);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'sidebar', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);
        if(\DB::table('local_cache')->where(['url' => 'entities', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'entities', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'entities', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);
        cache()->getMemcached()->flush();

        return response()->json(
            array(
                'success' => true,
                'code' => 200
            )
        );
    }

    public function disable(Request $request)
    {
        // $entity = \DB::table('data_types')->where([
        //     'name' => $slug,
        //     'enable' => 1,
        // ])->first();
        // if(!$entity) {
        //     return response()->json(
        //         array(
        //             'code' => 404,
        //             'error' => 'Сущность не найдена',
        //         )
        //     );
        // }
        $query = \DB::table('data_types')->whereIntegerInRaw('id', $request->ids);
        $query->update(['enable' => 0]);
        $entities = $query->get();
        foreach($entities as $entity) {
            \DB::table('sidebar_items')->where([
                'link' => '/objects/'.$entity->name,
            ])->delete();
        }
        
        \App\Models\SidebarItem::fixTree();

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'sidebar', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);
        if(\DB::table('local_cache')->where(['url' => 'entities', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'entities', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'entities', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json(
            array(
                'success' => true,
                'code' => 200
            )
        );
    }
}