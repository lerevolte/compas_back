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
use App\Models\Permission;
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

        $data = Role::list();

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
        if(!\Auth::user()->is_admin)
            return response()->json([
                'message' => 'Доступ ограничен'
            ], 403);
        $role = Role::find($id);
        $data_types = \DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
        //$permissions = $role->permissions_tables();
        $permissions = array();
        $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', $id)->get()->keyBy('entity_id')->toArray();

        $new_permissions_exist = false;
        foreach($data_types as $entity_id => $entity) {
            if(!array_key_exists($entity_id, $res)) {
                \DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => $id]]);
                $new_permissions_exist = true;
            }
        }
        if($new_permissions_exist)
            $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', $id)->get()->keyBy('entity_id')->toArray();
        foreach($res as $entity => $entity_permission) {
            if(isset($data_types[$entity]))
                $permissions[] = array(
                    'id' => $entity_permission['id'],
                    'entity_id' => $entity_permission['entity_id'],
                    'slug' => $data_types[$entity_permission['entity_id']]->slug,
                    'name' => $data_types[$entity_permission['entity_id']]->title_plural,
                    'read_p' => $entity_permission['read_p'],
                    'create_p' => $entity_permission['create_p'],
                    'update_p' => $entity_permission['update_p'],
                    'delete_p' => $entity_permission['delete_p'],
                    'export_p' => $entity_permission['export_p'],
                    //'import_p' => $entity_permission['import_p'],
                );
        }

        $permissions = collect($permissions)->sortBy('name')->toArray();
        
        $table = \App\Models\Table::roles();
        $roles = Role::list();

        $res_perms = array(
            'count' => count($permissions),
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 100,
            'total' => count($permissions),
            'from' => 0,
            'to' => 100,
            'data' => array_values($permissions),
            'buttons' => [],
            'sort_field' => 'id',
            'sort_order' => 'asc',
            'restrictions' => [],
            'empty_text' => 'Нет данных',
            'category_slug' => null,
            'guide' => null
        );

        $data = array(
            'list' => $res_perms,
            'table' => $table,
            'roles' => $roles
        );

        return response()->json($data);
    }

    // public function show_modules($id, Request $request)
    // {
    //     $role = Role::find($id);
    //     $data_types = \DB::table('modules')->where('enabled', 1)->pluck('name', 'slug')->toArray();
    //     $permissions = array();
    //     $res = \App\Models\Permission::where('area', 'module')->where('role_id', $id)->get()->groupBy('entity')->toArray();

    //     foreach($data_types as $entity => $data_type_name) {
    //         if(!array_key_exists($entity, $res)) {
    //             $new_permissions = [
    //                 [
    //                     'key' => 'read_module_'.$entity,
    //                     'entity' => $entity,
    //                     'type' => 'read',
    //                     'value' => 'N',
    //                     'role_id' => $id,
    //                     'area' => 'module'
    //                 ],
    //                 [
    //                     'key' => 'write_module_'.$entity,
    //                     'entity' => $entity,
    //                     'type' => 'write',
    //                     'value' => 'N',
    //                     'role_id' => $id,
    //                     'area' => 'module'
    //                 ]
    //             ];
    //             $permissions[] = array(
    //                 'title' => $entity,
    //                 'display_name' => $data_type_name,
    //                 'permissions' => $new_permissions
    //             );
    //             \DB::table('permissions')->insert($new_permissions);
    //         }
    //     }
    //     foreach($res as $entity => $entity_permissions) {
    //         if(isset($data_types[$entity]))
    //             $permissions[] = array(
    //                 'title' => $entity,
    //                 'display_name'=> $data_types[$entity] ?? '',
    //                 'permissions' => $entity_permissions
    //             );
    //     }
    //     $data = array_merge($role->toArray(), array('entities' => $permissions));

    //     return response()->json($data);
    // }

    public function update($id, Request $request)
    {
        $role = Role::findOrFail($id);
        $is_admin = $request->has('is_admin') ? $request->is_admin : $role->is_admin;
        if($role->is_permanent)
            $is_admin = $role->is_admin;
        $name = $role->name;
        if(!$name)
            $name = \Str::of($request->title)->slug('_');
        $role->update(['is_admin' => $is_admin, 'display_name' => ($request->title ? $request->title : $role->display_name), 'name' => $name]);

        if($request->rows && is_array($request->rows)) {
            $allowed = ['read_p', 'create_p', 'update_p', 'delete_p', 'export_p', 'import_p'];
            foreach($request->rows as $row) {
                if(empty($row['id']))
                    continue;
                $update = array_intersect_key($row, array_flip($allowed));
                if(!empty($update))
                    Permission::where('id', $row['id'])->update($update);
            }
        }
        \App\Models\Settings::clear_cache();

        // $permissions = array();

        // $data_types = \DB::table('data_types')->where('enable', 1)->get()->keyBy('id')->toArray();
        // $res = Permission::whereNotNull('entity_id')->where('role_id', $id)->get()->keyBy('entity_id')->toArray();
        // foreach($res as $entity => $entity_permission) {
        //     if(isset($data_types[$entity]))
        //         $permissions[] = array(
        //             'id' => $entity_permission['id'],
        //             'entity_id' => $entity_permission['entity_id'],
        //             'name' => $data_types[$entity_permission['entity_id']]->title_plural,
        //             'read_p' => $entity_permission['read_p'],
        //             'create_p' => $entity_permission['create_p'],
        //             'update_p' => $entity_permission['update_p'],
        //             'delete_p' => $entity_permission['delete_p'],
        //             'export_p' => $entity_permission['export_p'],
        //             'import_p' => $entity_permission['import_p'],
        //         );
        // }

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => 'roles', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'roles', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'roles', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        $res = $role->format();

        return response()->json($res);
    }

    public function store(Request $request)
    {
        $data_types = \DB::table('data_types')->where('enable', 1)->get()->keyBy('id')->toArray();
        $role = new Role;
        $role->display_name = 'Без названия';
        if($request->title)
            $role->display_name = $request->title;
        $role->is_admin = 0;
        $role->save();

        foreach($data_types as $entity_id => $entity) {
            \DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => $role->id]]);
        }

        // $res = Permission::whereNotNull('entity_id')->where('role_id', $role->id)->get()->keyBy('entity_id')->toArray();
        // foreach($res as $entity => $entity_permission) {
        //     $permissions[] = array(
        //         'id' => $entity_permission['id'],
        //         'entity_id' => $entity_permission['entity_id'],
        //         'name' => $data_types[$entity_permission['entity_id']]->title_plural,
        //         'read_p' => $entity_permission['read_p'],
        //         'create_p' => $entity_permission['read_p'],
        //         'update_p' => $entity_permission['update_p'],
        //         'delete_p' => $entity_permission['delete_p'],
        //         'export_p' => $entity_permission['export_p'],
        //         'import_p' => $entity_permission['import_p'],
        //     );
        // }

        $res = $role->format();

        return response()->json($res);
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