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
use Carbon\Carbon;

class RoleController extends Controller
{
    public function values(Request $request)
    {
        $data = array();

        $items = Role::get();
        foreach($items as $item) {
            $data[] = array(
                'label' => $item->display_name,
                'value' => $item->id
            );
        }

        return response()->json($data);
    }

    public function list(Request $request)
    {
        $data = array();

        $items = Role::get();
        foreach($items as $item) {
            $data[] = array(
                'id' => $item->id,
                'label' => $item->display_name,
                'value' => $item->id,
                'is_admin' => $item->is_admin,
                'is_permanent' => $item->is_permanent
            );
        }

        return response()->json($data);

        // $user = Auth::user();

        // $roles = $user->roles_all();
        // foreach($roles as $k => $role) {
        //     if(!$role)
        //         unset($roles[$k]);
        // }
        

        // return response()->json($roles);
    }
    public function show($id, Request $request)
    {
        $role = Role::find($id);
        $data_types = \DB::table('data_types')->where('enable', 1)->pluck('display_name_plural', 'name')->toArray();
        //$permissions = $role->permissions_tables();
        $permissions = array();
        $res = \App\Models\Permission::where('area', 'entity')->where('role_id', $id)->get()->groupBy('entity')->toArray();

        foreach($data_types as $entity => $data_type_name) {
            if(!array_key_exists($entity, $res)) {
                $new_permissions = [
                    [
                        'key' => 'read_'.$entity,
                        'entity' => $entity,
                        'type' => 'read',
                        'value' => 'N',
                        'role_id' => $id,
                        'area' => 'entity'
                    ],
                    [
                        'key' => 'write_'.$entity,
                        'entity' => $entity,
                        'type' => 'write',
                        'value' => 'N',
                        'role_id' => $id,
                        'area' => 'entity'
                    ],
                    [
                        'key' => 'add_'.$entity,
                        'entity' => $entity,
                        'type' => 'add',
                        'value' => 'N',
                        'role_id' => $id,
                        'area' => 'entity'
                    ],
                    [
                        'key' => 'delete_'.$entity,
                        'entity' => $entity,
                        'type' => 'delete',
                        'value' => 'N',
                        'role_id' => $id,
                        'area' => 'entity'
                    ],
                    [
                        'key' => 'export_'.$entity,
                        'entity' => $entity,
                        'type' => 'export',
                        'value' => 'N',
                        'role_id' => $id,
                        'area' => 'entity'
                    ],
                    [
                        'key' => 'import_'.$entity,
                        'entity' => $entity,
                        'type' => 'import',
                        'value' => 'N',
                        'role_id' => $id,
                        'area' => 'entity'
                    ]
                ];
                $permissions[] = array(
                    'title' => $entity,
                    'display_name' => $data_type_name,
                    'permissions' => $new_permissions
                );
                \DB::table('permissions')->insert($new_permissions);
            }
        }
        foreach($res as $entity => $entity_permissions) {
            if(isset($data_types[$entity]))
                $permissions[] = array(
                    'title' => $entity,
                    'display_name'=> $data_types[$entity] ?? '',
                    'permissions' => $entity_permissions
                );
        }
        $data = array_merge($role->toArray(), array('entities' => $permissions));

        return response()->json($data);
    }

    public function show_modules($id, Request $request)
    {
        $role = Role::find($id);
        $data_types = \DB::table('modules')->where('enabled', 1)->pluck('name', 'slug')->toArray();
        $permissions = array();
        $res = \App\Models\Permission::where('area', 'module')->where('role_id', $id)->get()->groupBy('entity')->toArray();

        foreach($data_types as $entity => $data_type_name) {
            if(!array_key_exists($entity, $res)) {
                $new_permissions = [
                    [
                        'key' => 'read_module_'.$entity,
                        'entity' => $entity,
                        'type' => 'read',
                        'value' => 'N',
                        'role_id' => $id,
                        'area' => 'module'
                    ],
                    [
                        'key' => 'write_module_'.$entity,
                        'entity' => $entity,
                        'type' => 'write',
                        'value' => 'N',
                        'role_id' => $id,
                        'area' => 'module'
                    ]
                ];
                $permissions[] = array(
                    'title' => $entity,
                    'display_name' => $data_type_name,
                    'permissions' => $new_permissions
                );
                \DB::table('permissions')->insert($new_permissions);
            }
        }
        foreach($res as $entity => $entity_permissions) {
            if(isset($data_types[$entity]))
                $permissions[] = array(
                    'title' => $entity,
                    'display_name'=> $data_types[$entity] ?? '',
                    'permissions' => $entity_permissions
                );
        }
        $data = array_merge($role->toArray(), array('entities' => $permissions));

        return response()->json($data);
    }

    public function update($id, Request $request)
    {
        $role = Role::find($id);
        $data_types = \DB::table('data_types')->pluck('display_name_plural', 'name')->toArray();

        $ids = array();
        $name = $role->name;
        if(!$name)
            $name = \Str::of($request->display_name)->slug('_');
        $is_admin = $request->is_admin;
        if($role->is_permanent)
            $is_admin = $role->is_admin;
        $role->update(['is_admin' => $request->is_admin, 'display_name' => $request->display_name, 'name' => $name]);
        $filterKeys = ['entity' => '', 'area' => '', 'type' => '', 'value' => '', 'key' => '', 'role_id' => ''];

        if($request->entities && is_array($request->entities)) {
            foreach($request->entities as $entity) {
                $perms = $entity['permissions'];
                foreach($perms as $k => $perm) {
                    $perms[$k] = array_intersect_key($perm, $filterKeys);
                }
                
                \DB::table('permissions')->upsert(
                    $perms, 
                    ['key', 'role_id'], 
                    ['value']
                );
                $types = array(
                    'add',
                    'write',
                    'read',
                    'delete',
                    'export',
                    'import'
                );
                // foreach($types as $type) {
                //     if(!\App\Models\Permission::where([
                //         'entity' => $entity['title'], 
                //         'role_id' => $role->id,
                //         'type' => $type
                //     ])->exists()) {
                //         $perm = \App\Models\Permission::create(
                //             [
                //                 'entity' => $entity['title'],
                //                 'role_id' => $role->id,
                //                 'area' => 'entity',
                //                 'key' => $type.'_'.$entity['title'],
                //                 'type' => $type,
                //                 'value' => 'N',
                //             ]
                //         );
                //         $ids[] = $perm->id;
                //     }
                // }

                
                // foreach($entity['permissions'] as $permission) {
                //     info($permission);
                //     $perm = \App\Models\Permission::updateOrCreate(
                //         [
                //             'entity' => $entity['title'],
                //             //'role_id' => $role->id,
                //             'area' => 'entity',
                //             //'key' => $permission['type'].'_'.$entity['title'],
                //             'type' => $permission['type'],
                //             'value' => $permission['value'],
                //             //'parent_id' => isset($permission['parent_id']) ? $permission['parent_id'] : '',
                //             //'is_parent' => 
                //         ],
                //         [
                //             'type' => $permission['type'],
                //             'key' => $permission['type'].'_'.$entity['title'],
                //             'role_id' => $role->id
                //             //'is_parent' => isset($perms['childs']) && count($perms['childs']) ? 1 : 0,
                //         ]
                //     );
                //     $ids[] = $perm->id;
                // }
            }
            //$role->permissions()->syncWithoutDetaching($ids);
        }
        if(count($ids))
            $role->permissions()->syncWithoutDetaching($ids);
        cache()->flush();

        $permissions = array();
        $res = \App\Models\Permission::where('area', 'entity')->where('role_id', $role->id)->get()->groupBy('entity')->toArray();

        foreach($res as $entity => $entity_permissions) {
            $permissions[] = array(
                'title' => $entity,
                'display_name'=> $data_types[$entity] ?? '',
                'permissions' => $entity_permissions
            );
        }
        $data = array_merge($role->toArray(), array('entities' => $permissions));

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => 'roles', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'roles', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'roles', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data_types = \DB::table('data_types')->pluck('display_name_plural', 'name')->toArray();
        $role = new Role;
        $role->display_name = 'Без названия';
        if($request->display_name)
            $role->display_name = $request->display_name;
        $role->is_admin = 0;
        $role->save();
        $types = array(
            'add',
            'write',
            'read',
            'delete',
            'export',
            'import'
        );
        $entities = \DB::table('data_types')->select(['slug', 'display_name_singular', 'display_name_plural', 'color'])/*->where('area', 'entity')*/->get();

        foreach($entities as $entity) {
            foreach($types as $type) {
                $perm = \App\Models\Permission::create(
                    [
                        'entity' => $entity->slug,
                        'role_id' => $role->id,
                        'area' => 'entity',
                        'key' => $type.'_'.$entity->slug,
                        'type' => $type,
                        'value' => 'N',
                    ]
                );
                $ids[] = $perm->id;
            }
        }

        $modules = \DB::table('modules')->where('enabled', 1)->pluck('name', 'slug')->toArray();

        foreach($modules as $module => $name) {
            $perm = \App\Models\Permission::create(
                [
                    'entity' => $module,
                    'role_id' => $role->id,
                    'area' => 'module',
                    'key' => 'read_module_'.$module,
                    'type' => 'read',
                    'value' => 'N',
                ]
            );
            $ids[] = $perm->id;
            $perm = \App\Models\Permission::create(
                [
                    'entity' => $module,
                    'role_id' => $role->id,
                    'area' => 'module',
                    'key' => 'write_module_'.$module,
                    'type' => 'write',
                    'value' => 'N',
                ]
            );
            $ids[] = $perm->id;
        }

        $role->permissions()->syncWithoutDetaching($ids);

        cache()->flush();

        $permissions = array();
        $res = \App\Models\Permission::where('area', 'entity')->where('role_id', $role->id)->get()->groupBy('entity')->toArray();

        foreach($res as $entity => $entity_permissions) {
            $permissions[] = array(
                'title' => $entity,
                'display_name'=> $data_types[$entity] ?? '',
                'permissions' => $entity_permissions
            );
        }
        $data = array_merge($role->toArray(), array('entities' => $permissions));

        return response()->json($data);
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if($role->is_permanent)
            return response()->json(
                    array(
                        'code' => 403,
                        'error' => 'Роль не может быть удалена',
                    )
            );
        $role->delete();

        return response()->json(
                    array(
                        'code' => 200,
                        'error' => 'Роль удалена',
                    )
            );
    }
}