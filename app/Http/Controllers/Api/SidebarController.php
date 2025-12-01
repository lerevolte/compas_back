<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SidebarController extends Controller
{
    public function get(Request $request)
    {
        $menu = \Auth::user()->getSidebar();

        return response()->json($menu);
    }

    public function list($id = null, Request $request)
    {
        if($id) {
            $menu = \App\Models\SidebarItem::descendantsOf($id)->toTree();
        } else {
            $settings = get_settings();
            $menu = $settings['settings']['sidebar_items'];
        }
        

        return response()->json($menu);
    }

    public function sort(Request $request)
    {
        $items = \App\Models\SidebarItem::whereIn('id', $request->items)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->items).")"))->get();
        foreach ($items as $key => $item) {
            $item->sort = $key;
            $item->save();
        }

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'sidebar', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        cache()->getMemcached()->delete(tenant('id').':sidebarmenu-'.\Auth::user()->id);

        // $keys = cache()->getMemcached()->getAllKeys();
        // $regex = tenant('id').':sidebar-'.$item->page.'-*';
        // foreach($keys as $item) {
        //     if(preg_match('/'.$regex.'/', $item)) {
        //         cache()->getMemcached()->delete($item);
        //     }
        // }

        //cache()->flush();
    }

    public function update($id, Request $request)
    {
        $item = $request->item;
        if(isset($item['children']))
            unset($item['children']);
        \DB::table('sidebar_items')->where('id', $id)->update($item);
        $item = \DB::table('sidebar_items')->where('id', $id)->first();

        return response()->json($item);
    }

    public function set(Request $request)
    {
        $menu = json_encode($request->menu, JSON_UNESCAPED_UNICODE);
        \DB::table('settings')->where([
            'type' => 'sidebar',
            'user_id' => \Auth::user()->id
        ])->update(['value' => $menu]);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "sidebar/get", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "sidebar/get", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "sidebar/get", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        $cache_name = tenant('id').':sidebarmenu-'.\Auth::user()->id;
        cache()->getMemcached()->delete($cache_name);
        //$entity = \DB::table('data_types')->where('slug', $slug)->first();

        return response()->json($request->menu);
    }

    public function set_group(Request $request)
    {
        $sidebar = \DB::table('settings')->where([
            'type' => 'sidebar',
            'user_id' => \Auth::user()->id
        ])->first();
        $menu = json_decode($sidebar->value, true);
        foreach($menu as $i => $item) {
            if($item['id'] == $request->id && $item['is_group']) {
                $menu[$i] = $request->data;
            }
        }
        if(!$request->id) {
            $menu[] = $request->data;
        }
        info('set_group');
        info($request->data);
        info($menu);
        $menu = json_encode($menu, JSON_UNESCAPED_UNICODE);
        $sidebar = \DB::table('settings')->where([
            'type' => 'sidebar',
            'user_id' => \Auth::user()->id
        ])->update(['value' => $menu]);
        info($menu);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "sidebar/get", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "sidebar/get", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "sidebar/get", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        $cache_name = tenant('id').':sidebarmenu-'.\Auth::user()->id;
        cache()->getMemcached()->delete($cache_name);
        //$entity = \DB::table('data_types')->where('slug', $slug)->first();

        return response()->json($request->menu);
    }

    public function reset(Request $request)
    {
        $tenant = \App\Models\Tenant::find('seeds');
        $menu = $tenant->run(function ($tenant) {
            $user = \App\Models\User::find(1);
            $menu = $user->getSidebar();

            return $menu;
        });

        return response()->json($menu);
    }

    public function set_role($role_id, Request $request)
    {
        $menu = json_encode($request->menu, JSON_UNESCAPED_UNICODE);

        $users = \App\Models\User::where('role_id', $role_id)->get();
        foreach($users as $user) {
            $user_settings = \DB::table('settings')->where([
                'type' => 'sidebar',
                'user_id' => $user->id
            ])->first();

            if($user_settings)
                \DB::table('settings')->where([
                    'type' => 'sidebar',
                    'user_id' => $user->id
                ])->update(['value' => $menu]);
            else
                \DB::table('settings')->insert([
                    'key' => 'sidebar',
                    'type' => 'sidebar',
                    'user_id' => $user->id,
                    'value' => $menu
                ]);

            $now = Carbon::now();
            if(\DB::table('local_cache')->where(['url' => "sidebar/get", 'user_id' => $user->id])->exists())
                \DB::table('local_cache')->where(['url' => "sidebar/get", 'user_id' => $user->id])->update(['updated_at' => $now]);
            else
                \DB::table('local_cache')->insert(['url' => "sidebar/get", 'user_id' => $user->id, 'created_at' => $now, 'updated_at' => $now]);

            $cache_name = tenant('id').':sidebarmenu-'.$user->id;
            cache()->getMemcached()->delete($cache_name);
        }

        $role = \App\Models\Role::find($role_id);
        $role->sidebar = $menu;
        $role->saveQuietly();



        return response()->json($request->menu);
    }

    public function set_all(Request $request)
    {
        $menu = json_encode($request->menu, JSON_UNESCAPED_UNICODE);

        $users = \App\Models\User::get();
        foreach($users as $user) {
            \DB::table('settings')->where([
                'type' => 'sidebar',
                'user_id' => $user->id
            ])->update(['value' => $menu]);

            $now = Carbon::now();
            if(\DB::table('local_cache')->where(['url' => "sidebar/get", 'user_id' => $user->id])->exists())
                \DB::table('local_cache')->where(['url' => "sidebar/get", 'user_id' => $user->id])->update(['updated_at' => $now]);
            else
                \DB::table('local_cache')->insert(['url' => "sidebar/get", 'user_id' => $user->id, 'created_at' => $now, 'updated_at' => $now]);
            $cache_name = tenant('id').':sidebarmenu-'.$user->id;
            cache()->getMemcached()->delete($cache_name);
        }

        $settings_all = \DB::table('settings')->where([
                'type' => 'sidebar',
                'user_id' => null
            ])->first();
        if($settings_all)
            \DB::table('settings')->where([
                'type' => 'sidebar',
                'user_id' => null
            ])->update([
                'value' => $menu
            ]);
        else 
            $settings_all = \DB::table('settings')->insert([
                'key' => 'sidebar',
                'type' => 'sidebar',
                'user_id' => null,
                'value' => $menu
            ]);

        return response()->json($request->menu);
    }
}