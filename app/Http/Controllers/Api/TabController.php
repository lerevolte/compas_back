<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;

class TabController extends Controller
{
    public function get($slug)
    {
        $data = \App\Models\Menu::tabs($slug);

        return response()->json($data);
    }

    public function trash()
    {
        $tabs = array();
        $sidebar = \Auth::user()->getSidebar();
        $sidebar = collect($sidebar)->keyBy('slug')->toArray();
        $types = collect(\DB::table('data_types')->select(['id', 'model_name', 'slug', 'title_plural'])->where('enable', 1)->get())->keyBy('model_name')->toArray();
        $type_ids = array();
        foreach($types as $model => $entity) {
            if(!strstr($model,'TCG') && $entity->slug != 'roles') {
                $type_ids[] = $entity->id;
            }
        }
        
        $fields = \DB::table('data_rows')->whereIntegerInRaw('data_type_id', $type_ids)->where('is_remove', 0)->orderBy('sort')->get()->groupBy('data_type_id')->toArray();
        $sort = 0;
        $last_tabs = array();
        foreach($types as $model => $entity) {
            if(!strstr($model,'TCG') && $entity->slug != 'roles' && isset($fields[$entity->id]) && $entity->slug != 'analytics') {
                if(isset($sidebar[$entity->slug])) {
                    $tabs[] = array(
                        'id' => $entity->id,
                        'tab' => $entity->slug,
                        'title' => $entity->title_plural,
                        'sort' => $sidebar[$entity->slug]['sort'],
                        'enabled' => $model::onlyTrashed()->first() ? true : false
                    );
                    $sort = $sidebar[$entity->slug]['sort']++;
                } else {
                    $last_tabs[] = array(
                        'id' => $entity->id,
                        'tab' => $entity->slug,
                        'title' => $entity->title_plural,
                        'sort' => $sort,
                        'enabled' => $model::onlyTrashed()->first() ? true : false
                    );
                    $sort++;
                }

            }
        }

        $tabs = array_values(collect($tabs)->sortBy('sort')->toArray());
        $tabs = array_merge($tabs, $last_tabs);
        
        return response()->json($tabs);
    }
    // public function get($entity)
    // {
    //     $data = array();
    //     $user_roles = \Auth::user()->roles;
    //     $tabs = Tab::with('roles')->where('entity', $entity)->orderBy('sort')->get();
    //     foreach ($tabs as $tab) {
    //         if($tab->has_roles_read && $user_roles->intersect($tab->roles)->count() || !$tab->has_roles_read) {
    //             $data[] = array(
    //                 'id' => $tab->id,
    //                 'title' => $tab->title,
    //                 'tab' => $tab->tab,
    //                 'sort' => $tab->sort,
    //                 'enabled' => $tab->enabled,
    //                 'has_roles_read' => $tab->has_roles_read,
    //                 'roles_read' => $tab->roles()->pluck('id')
    //             );
    //         }
    //     }

    //     return response()->json($data);
    // }

    public function permissions($slug, Request $request)
    {
        $menus = \DB::table('settings')->where([
            'entity' => $slug,
            'type' => 'menu',
        ])->get();

        foreach($menus as $menu) {
            $menu_items = json_decode($menu->value, true);
            foreach($menu_items as $k => $menu_item) {
                info('menu');
                info($request->menu);
                foreach($request->menu as $new_menu_item) {
                    if($new_menu_item['tab'] == $menu_item['tab']) {
                        $sort = $menu_item['sort'];
                        $menu_items[$k] = $new_menu_item;
                        $menu_items[$k]['sort'] = $sort;
                        info($new_menu_item);
                        if(empty($new_menu_item['has_roles_read']) || empty($new_menu_item['roles_read'])) {
                            $menu_items[$k]['roles_read'] = null;
                        }
                    }
                }
            }

            \DB::table('settings')->where('id', $menu->id)->update(['value' => json_encode($menu_items, JSON_UNESCAPED_UNICODE)]);
            
        }
        

        return response()->json($request->menu);
        // $tab = Tab::findOrFail($id);

        // $tab->roles()->sync($request->roles_read);
        // $tab->has_roles_read = $request->has('has_roles_read') ? $request->has_roles_read : $tab->has_roles_read;
        // $tab->save();

        // return response()->json($tab);
    }

    public function events_visibility($slug, Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $roles = array_values(array_map('intval', (array) $request->input('roles_read', [])));
        $hasRoles = (bool) $request->input('has_roles_read') && count($roles) > 0;

        $value = json_encode([
            'has_roles_read' => $hasRoles,
            'roles_read' => $hasRoles ? $roles : [],
        ]);

        $exists = \DB::table('settings')
            ->where(['type' => 'events_visibility', 'entity' => $slug])
            ->whereNull('user_id')
            ->first();

        if ($exists) {
            \DB::table('settings')->where('id', $exists->id)->update(['value' => $value]);
        } else {
            \DB::table('settings')->insert([
                'key' => "events_visibility_{$slug}",
                'display_name' => 'События',
                'value' => $value,
                'entity' => $slug,
                'type' => 'events_visibility',
                'user_id' => null,
            ]);
        }

        return response()->json([
            'has_roles_read' => $hasRoles,
            'roles_read' => $hasRoles ? $roles : [],
        ]);
    }
}