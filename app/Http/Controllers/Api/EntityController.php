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
use App\Models\Entity;
use App\Models\Permission;
use App\Models\Role;

class EntityController extends Controller
{
	public function list(Request $request)
    {
    	$entities = \DB::table('data_types')->select(['slug', 'title_singular', 'title_plural', 'color'])->get();

    	return response()->json($entities);
    }

    public function compose_list(Request $request)
    {
        $entities = \DB::table('data_types')->select(['id', 'title_singular', 'title_plural', 'enable'])->where('hidden', 0)->get();
        $list = array();
        foreach($entities as $entity) {
            $list[] = array(
                'id' => $entity->id,
                'name' => $entity->title_plural,
                'enable' => $entity->enable
            );
        }
        $table = \App\Models\Table::entities();

        $data = array(
            'list' => $list,
            'table' => $table,
        );
        info($data);
        return response()->json($data);
    }

    public function get_menu($slug, Request $request)
    {
        $s = app('settings');
        $item = \DB::table('settings')->where([
            'entity' => $slug,
            'type' => 'menu',
            'user_id' => \Auth::user()->id
        ])->first();
        if(!$item) {
            $item = \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
            ])->first();
            \DB::table('settings')->insert([
                'key' => $item->key,
                'title' => $item->title,
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
                            'title' => $field->title,
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

        return response()->json($request->menu);
    }

    public function reset_menu($slug, Request $request)
    {
        $tenant = \App\Models\Tenant::find('seeds');
        $menu = $tenant->run(function ($tenant) use ($slug) {
            $menu = \DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
                'user_id' => 1
            ])->first();
            

            return json_decode($menu->value,true);
        });
        

        return response()->json($menu);
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

    public function update(Request $request)
    {
        if($request->rows) {
            $ids = array();
            foreach($request->rows as $entity)
                $ids[] = $entity['id'];
            $entities = \DB::table('data_types')->whereIntegerInRaw('id', $ids)->get()->keyBy('id');
            foreach($request->rows as $entity) {
                \DB::table('data_types')->where('id', $entity['id'])->update(['enable' => $entity['enable']]);
                
                if($entity['enable']) {
                    $roles = Role::get();
                    $permissions = array();
                    foreach($roles as $role) {
                        if(!Permission::where(['entity_id' => $entity['id'], 'role_id' => $role->id])->exists())
                            $permissions[] = array(
                                'role_id' => $role->id,
                                'entity_id' => $entity['id'],
                                'read_p' => $role->id == 1 ? 'A' : 'N',
                                'create_p' => $role->id == 1 ? 'A' : 'N',
                                'update_p' => $role->id == 1 ? 'A' : 'N',
                                'delete_p' => $role->id == 1 ? 'A' : 'N',
                                'export_p' => $role->id == 1 ? 'A' : 'N',
                                'import_p' => $role->id == 1 ? 'A' : 'N'
                            );
                       
                    }
                    Permission::insert($permissions);
                } else {
                    Permission::where('entity_id', $entity['id'])->delete();
                }
                if($entity['enable'] && !$entities[$entity['id']]->hidden) {
                    if(!\App\Models\SidebarItem::where('slug', $entities[$entity['id']]->slug)->exists()) {
                        $side_item = new \App\Models\SidebarItem;
                        $side_item->name = $entities[$entity['id']]->title_plural;
                        $side_item->slug = $entities[$entity['id']]->slug;
                        $side_item->link = "/objects/".$entities[$entity['id']]->slug;
                        $side_item->enabled = 1;
                        $side_item->save();
                    }
                    
                    
                } else {
                    
                    \App\Models\SidebarItem::where('slug', $entities[$entity['id']]->slug)->delete();
                }
            }
            $entities = \DB::table('data_types')->select(['id', 'slug', 'title_singular', 'title_plural', 'color'])->get();
            
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

            \App\Models\Settings::clear_cache();
            
            $table = \App\Models\Table::entities();

            $data = array(
                'list' => $entities,
                'table' => $table,
            );

            return response()->json($data);
        }
        
    }

    public function enable(Request $request)
    {
        $query = \DB::table('data_types')->whereIntegerInRaw('id', $request->ids);
        $query->update(['enable' => 1]);
        $entities = $query->get(); 
        foreach ($entities as $entity) {
            $side_item = new \App\Models\SidebarItem;
            $side_item->name = $entity->title_plural;
            $side_item->slug = $entity->name;
            $side_item->link = "/objects/".$entity->name;
            $side_item->save();
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

    public function last_modified()
    {
        $last_modified = [];
        $articles_modified = \DB::table('articles')->select('updated_at')->orderBy('updated_at','DESC')->first();
        $faq_modified = \DB::table('faq')->select('updated_at')->orderBy('updated_at','DESC')->first();
        $knowledge_modified = \DB::table('knowledge')->select('updated_at')->orderBy('updated_at','DESC')->first();
        $guides_modified = \DB::table('guides')->select('updated_at')->orderBy('updated_at','DESC')->first();
        if($articles_modified)
            $last_modified['articles'] = $articles_modified->updated_at;
        if($faq_modified)
            $last_modified['faq'] = $faq_modified->updated_at;
        if($knowledge_modified)
            $last_modified['knowledge'] = $knowledge_modified->updated_at;
        if($guides_modified)
            $last_modified['guides'] = $guides_modified->updated_at;

        return response()->json($last_modified);
    }
}