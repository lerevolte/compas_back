<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use \App\Models\Role;
use \App\Models\SidebarItem;
use Nwidart\Modules\Facades\Module;

class RoleController extends Controller
{
    public function destroy(Role $role)
    {
        $role->delete();
    }

    public function changeSort(Request $request)
    {
        $items = Role::whereIn('id', $request->items)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->items).")"))->get();
        foreach ($items as $key => $item) {
            $item->sort = $key;
            $item->save();
        }
    }

    public function create()
    {
        $sidebar_items = SidebarItem::orderBy('sort')->pluck('name', 'code')->toArray();
        $role = new Role;
        $values = array(
                'read' => '',
                'add' => '',
                'write' => '',
                'delete' => '',
                'export' => '',
                //'import' => ''
            );
        $perm_table = array(
            'logistic' => array(
                'name' => 'Логистика',
                'values' => array(
                    'read' => '',
                    'add' => '',
                    'write' => '',
                    'delete' => '',
                )
            ),
            'addr' => array(
                'name' => 'Справочник адресов',
                'values' => array(
                    'read' => '',
                    'add' => '',
                    'write' => '',
                    'delete' => '',
                )
            ),

            'carriers' => array(
                'name' => 'Перевозчики',
                'values' => $values,
                'childs' => array(
                    'carriers' => [
                        'name' => 'Главная',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                    'cars' => [
                        'name' => 'Машины',
                        'values' => $values,
                    ],
                    'drivers' => [
                        'name' => 'Водители',
                        'values' => $values,
                    ],
                    'routes' => [
                        'name' => 'Статистика маршрутов',
                        'values' => array(
                            'read' => '',
                            'export' => '',
                        )
                    ],
                    'expenses' => [
                        'name' => 'Журнал расходов',
                        'values' => array(
                            'read' => '',
                            'export' => '',
                        )
                    ],
                    'salaries' => [
                        'name' => 'Зарплаты',
                        'values' => array(
                            'read' => '',
                            'export' => '',
                        )
                    ]
                ),
            ),
            'orders' => array(
                'name' => 'Нераспределенные заказы',
                'values' => array(
                    'read' => '',
                    'add' => '',
                    'write' => '',
                    'delete' => '',
                )
            ),
            'infostore' => array(
                'name' => 'Информация для склада',
                'values' => array(
                    'read' => ''
                ),
            ),
            'products' => array(
                'name' => 'Товары',
                'values' => array(
                    'read' => ''
                ),
            ),
            'supplies' => array(
                'name' => 'Задачи',
                'values' => array(
                    'read' => '',
                    'add' => '',
                    'write' => '',
                    'delete' => '',
                )
            ),
            'fines' => array(
                'name' => 'Памятка',
                'values' => array(
                    'read' => '',
                    'add' => '',
                    'write' => '',
                    'delete' => '',
                )
            ),
            'settings' => array(
                'name' => 'Настройки',
                'values' => array(
                        'read' => '',
                        'add' => '',
                        'write' => '',
                        'delete' => '',
                    ),
                'childs' => array(
                    'users' => [
                        'name' => 'Пользователи',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                    'roles' => [
                        'name' => 'Роли',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                ),
            ),
        );
        foreach($perm_table as $code => $item) {
            if(isset($sidebar_items[$code]))
                $perm_table[$code]['name'] = $sidebar_items[$code];
        }
        foreach($role->permissions_tables as $permission) {
            if(!$permission->childs || !$permission->parent_id || $permission->is_parent) 
                $perm_table[$permission->table_name]['values'][$permission->perm_type] = $permission->perm_value;
            if($permission->childs) {
                $all_access = array(
                    'read' => 'O',
                    'add' => 'O',
                    'write' => 'O',
                    'delete' => 'O',
                    'export' => 'O',
                );
                foreach($permission->childs as $permission_child) {

                    $perm_table[$permission->table_name]['childs'][$permission_child->table_name]['values'][$permission_child->perm_type] = $permission_child->perm_value;
                    if($permission_child->perm_value != 'O') {

                        $perm_table[$permission->table_name]['values'][$permission_child->perm_type] = 'N';
                        $all_access[$permission_child->perm_type] = 'N';
                    }
                }

            }
        }

        $perm_table = array_merge($sidebar_items, $perm_table);

        
        $perm_table_fields = array(
            'routes' => array(
                'name' => 'Машины',
                'fields' => array(
                )
            ),
            'points' => array(
                'name' => 'Точки',
                'fields' => array(
                )
            ),
            'orders' => array(
                'name' => 'Нераспределенные заказы',
                'fields' => array(
                )
            )
        );
        $default_value = count($role->permissions_fields) ? 'O' : 'N';
        $perm_table_parents = array(
            'fields_logistic' => array(
                'values' => array(
                    'read' => $default_value,
                    'write' => $default_value
                )
            ),
            'fields_routes' => array(
                'values' => array(
                    'read' => $default_value,
                    'write' => $default_value
                )
            ),
            'fields_points' => array(
                'values' => array(
                    'read' => $default_value,
                    'write' => $default_value
                )
            ),
            'fields_orders' => array(
                'values' => array(
                    'read' => $default_value,
                    'write' => $default_value
                )
            ),
        );
        return view('roles.edit', compact('role', 'perm_table', 'perm_table_fields', 'perm_table_parents'));
    }

    public function edit(int $id)
    {
        $sidebar_items = SidebarItem::orderBy('sort')->pluck('name', 'code')->toArray();
        $role = Role::find($id);
        $values = array(
                'read' => '',
                'add' => '',
                'write' => '',
                'delete' => '',
                'export' => '',
                //'import' => ''
            );
        $perm_table = array(
            'main' => array(
                'name' => 'Ядро',
                'values' => array(
                        'read' => '',
                        'add' => '',
                        'write' => '',
                        'delete' => '',
                    ),
                'childs' => array(
                    'orders' => [
                        'name' => 'Задачи',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                    'products' => [
                        'name' => 'Товары',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                    'remnants' => [
                        'name' => 'Остатки',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                    'cars' => [
                        'name' => 'Машины',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                    'drivers' => [
                        'name' => 'Водители',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                    'companies' => [
                        'name' => 'Компании',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                    'clients' => [
                        'name' => 'Клиенты',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                ),
            ),
            'settings' => array(
                'name' => 'Настройки',
                'values' => array(
                        'read' => '',
                        'add' => '',
                        'write' => '',
                        'delete' => '',
                    ),
                'childs' => array(
                    'users' => [
                        'name' => 'Пользователи',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                    'roles' => [
                        'name' => 'Роли',
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                        )
                    ],
                ),
            )
        );
        $modules = Module::getByStatus(1);
        foreach($modules as $module) {
            if($module->get('main'))
                continue;
            if($entities = $module->get('entities')) {
                $alias = $module->get('alias');
                $perm_table[$alias] = array('name' => $module->get('display_name'), 'values' => $values);
                foreach($entities as $entity) {
                    $perm_table[$alias]['childs'][$entity['name']] = array(
                        'name' => $entity['display_name_singular'],
                        'values' => array(
                            'read' => '',
                            'add' => '',
                            'write' => '',
                            'delete' => '',
                            'export' => '',
                        )
                    );
                }
            }
        }
        foreach($perm_table as $code => $item) {
            if(isset($sidebar_items[$code]))
                $perm_table[$code]['name'] = $sidebar_items[$code];
        }
        //dd($role);
        foreach($role->permissions_tables as $permission) {
            if(!$permission->childs || !$permission->parent_id || $permission->is_parent) 
                $perm_table[$permission->table_name]['values'][$permission->perm_type] = $permission->perm_value;
            if($permission->childs) {
                $all_access = array(
                    'read' => 'O',
                    'add' => 'O',
                    'write' => 'O',
                    'delete' => 'O',
                    'export' => 'O',
                );
                foreach($permission->childs as $permission_child) {
                    if(isset($perm_table[$permission->table_name]['childs'][$permission_child->table_name]['name'])) {
                        $perm_table[$permission->table_name]['childs'][$permission_child->table_name]['values'][$permission_child->perm_type] = $permission_child->perm_value;
                        if($permission_child->perm_value != 'O') {
                            $perm_table[$permission->table_name]['values'][$permission_child->perm_type] = 'N';
                            $all_access[$permission_child->perm_type] = 'N';
                        }
                    }
                }

            }
        }

        //$perm_table = array_merge($sidebar_items, $perm_table);

        //dd($perm_table);
        $perm_table_fields = array(
            'routes' => array(
                'name' => 'Машины',
                'fields' => array(
                )
            ),
            'points' => array(
                'name' => 'Точки',
                'fields' => array(
                )
            ),
            'orders' => array(
                'name' => 'Нераспределенные заказы',
                'fields' => array(
                )
            )
        );
        $default_value = count($role->permissions_fields) ? 'O' : 'N';
        $perm_table_parents = array(
            'fields_logistic' => array(
                'values' => array(
                    'read' => $default_value,
                    'write' => $default_value
                )
            ),
            'fields_routes' => array(
                'values' => array(
                    'read' => $default_value,
                    'write' => $default_value
                )
            ),
            'fields_points' => array(
                'values' => array(
                    'read' => $default_value,
                    'write' => $default_value
                )
            ),
            'fields_orders' => array(
                'values' => array(
                    'read' => $default_value,
                    'write' => $default_value
                )
            ),
        );
        // foreach($perm_table_fields as $table_name => $permission) {
        //     if($table_name == 'points' || $table_name == 'orders')
        //         $row_type = \DB::table('data_types')->where('name', 'orders')->first();
        //     else
        //         $row_type = \DB::table('data_types')->where('name', $table_name)->first();

        //     $fields = \DB::table('data_rows')->where(['data_type_id' => $row_type->id])->where('perm_access', 1)->get();
        //     foreach($fields as $field) {
        //         $perm_table_fields[$table_name]['fields'][$field->field]['name'] = $field->display_name;
        //         $perm_table_fields[$table_name]['fields'][$field->field]['values'] = array(
        //                 'read' => '',
        //                 'write' => ''
        //             );
        //     }
        // }
        
        // foreach($role->permissions_fields as $permission) {
            
        //     $perm_table_fields[$permission->table_name]['fields'][$permission->field_name]['name'] = $permission->field_display_name;
        //     $perm_table_fields[$permission->table_name]['fields'][$permission->field_name]['values'][$permission->perm_type] = $permission->perm_value;
        //     if($permission->perm_value != 'O' && $perm_table_parents['fields_logistic']['values'][$permission->perm_type] == 'O') {
        //         $perm_table_parents['fields_logistic']['values'][$permission->perm_type] = 'N';
        //         $perm_table_parents['fields_'.$permission->table_name]['values'][$permission->perm_type] = 'N';
        //     }
        // }
        // echo '<pre>';
        // print_r($perm_table);
        // echo '</pre>';
        // die();
        return view('roles.edit', compact('role', 'perm_table', 'perm_table_fields', 'perm_table_parents'));
    }

    public function store(Request $request)
    {
        $role = new Role;
        $ids = array();
        foreach($request->all()['perms'] as $model => $perms) {
            $parents = array();
            foreach($perms['values'] as $type => $perm) {
                $permission = \App\Models\Permission::updateOrCreate(
                    [
                        'table_name' => $model,
                        'role_id' => $role->id,
                        'area' => 'table',
                        'key' => $type.'_'.$model,
                        'account_id' => Auth::user()->account_id,
                    ],
                    [
                        'perm_type' => $type,
                        'is_parent' => isset($perms['childs']) && count($perms['childs']) ? 1 : 0,
                    ]
                );
                $permission->perm_value = $perm;
                $permission->save();
                $parent_id = $permission->id;
                $ids[] = $parent_id;
                $parents[$type] = $parent_id;
            }

            if(isset($perms['childs'])) {
                $need_change_parent = array(
                    'read' => true,
                    'add' => true,
                    'write' => true,
                    'delete' => true,
                    'export' => true
                );
                foreach($perms['childs'] as $child_model => $child_perms) {

                    foreach($child_perms as $child_type => $child_perm) {
                        $permission = \App\Models\Permission::firstOrCreate(
                            [
                                'table_name' => $child_model,
                                'perm_type' => $child_type,
                                'account_id' => Auth::user()->account_id,
                                'role_id' => $role->id,
                                'area' => 'table',
                                'key' => $child_type.'_'.$child_model,
                                'is_parent' => 0,
                            ],
                            [
                                'parent_id' => $parents[$child_type],
                            ]
                        );
                        $permission->perm_value = $child_perm;
                        $permission->save();
                        $ids[] = $permission->id;
                        if($child_perm != 'O')
                            $need_change_parent[$child_type] = false;
                    }
                }
                if($need_change_parent) {
                    foreach($need_change_parent as $parent_type => $parent_value) {
                        if($parent_value) {
                            $permission = \App\Models\Permission::updateOrCreate(
                                [
                                    'table_name' => $model,
                                    'role_id' => $role->id,
                                    'area' => 'table',
                                    'key' => $parent_type.'_'.$model,
                                    'account_id' => Auth::user()->account_id,
                                    'is_parent' => 1,
                                ],
                                [
                                    'perm_type' => $parent_type,
                                    'perm_value' => 'O',
                                    'is_parent' => 1,
                                ]
                            );
                        } else {
                            $permission = \App\Models\Permission::updateOrCreate(
                                [
                                    'table_name' => $model,
                                    'role_id' => $role->id,
                                    'area' => 'table',
                                    'key' => $parent_type.'_'.$model,
                                    'account_id' => Auth::user()->account_id,
                                    'is_parent' => 1,
                                ],
                                [
                                    'perm_type' => $parent_type,
                                    'perm_value' => 'N',
                                    'is_parent' => 1,
                                ]
                            );
                        }
                    }
                }
            }

        }
        $role->name = \Str::of($request->display_name)->slug('_');
        $role->display_name = $request->display_name;
        $role->is_admin = $request->is_admin;
        $role->save();
        $role->permissions()->syncWithoutDetaching($ids);

        cache()->flush();

        return redirect()->route('roles.edit', $role->id);
    }

    public function update(Request $request, Role $role)
    {
        //dd($request->all()['perms']);
        $role = Role::find($request->id);
        $ids = array();

        //dd($request->all()['perms']);
        foreach($request->all()['perms'] as $model => $perms) {
            //echo '---------<br>';
            $parents = array();
            foreach($perms['values'] as $type => $perm) {
                //echo $type.'_'.$model.' - '.(isset($perms['childs']) && count($perms['childs']) ? 1 : 0).'<br>';
                //continue;
                $permission = \App\Models\Permission::updateOrCreate(
                    [
                        'table_name' => $model,
                        'role_id' => $role->id,
                        'area' => 'table',
                        'key' => $type.'_'.$model,
                        'account_id' => Auth::user()->account_id,
                    ],
                    [
                        'perm_type' => $type,
                        'is_parent' => isset($perms['childs']) && count($perms['childs']) ? 1 : 0,
                    ]
                );
                //echo $permission->id.' '.$type.'_'.$model.' - '.(isset($perms['childs']) && count($perms['childs']) ? 1 : 0).'<br>';
                $permission->perm_value = $perm;
                $permission->save();
                $parent_id = $permission->id;
                $ids[] = $parent_id;
                $parents[$type] = $parent_id;
            }
            
            if(isset($perms['childs'])) {
                $need_change_parent = array(
                    'read' => true,
                    'add' => true,
                    'write' => true,
                    'delete' => true,
                    'export' => true
                );
                foreach($perms['childs'] as $child_model => $child_perms) {

                    foreach($child_perms as $child_type => $child_perm) {
                        $permission = \App\Models\Permission::firstOrCreate(
                            [
                                'table_name' => $child_model,
                                'perm_type' => $child_type,
                                'account_id' => Auth::user()->account_id,
                                'role_id' => $role->id,
                                'area' => 'table',
                                'key' => $child_type.'_'.$child_model,
                                'is_parent' => 0,
                            ],
                            [
                                'parent_id' => $parents[$child_type],
                            ]
                        );
                        $permission->perm_value = $child_perm;
                        $permission->save();
                        //echo $permission->id.' '.$child_type.'_'.$child_model.' '.$child_perm.'<br>';
                        $ids[] = $permission->id;
                        if($child_perm != 'O') {
                            $need_change_parent[$child_type] = false;
                        }
                    }
                }
                //die();
                //perms[carriers][values][read]
                //perms[carriers][childs][carriers][read]
                //dd($need_change_parent);
                if($need_change_parent) {
                    //dd($need_change_parent);
                    //echo 'need_change_parent';
                    foreach($need_change_parent as $parent_type => $parent_value) {
                        if($parent_value) {
                            //echo $parent_type.' '.'O<BR>';
                            $permission = \App\Models\Permission::updateOrCreate(
                                [
                                    'table_name' => $model,
                                    'role_id' => $role->id,
                                    'area' => 'table',
                                    'key' => $parent_type.'_'.$model,
                                    'account_id' => Auth::user()->account_id,
                                    'is_parent' => 1,
                                ],
                                [
                                    'perm_type' => $parent_type,
                                    'perm_value' => 'O',
                                    'is_parent' => 1,
                                ]
                            );
                        } else {
                            $permission = \App\Models\Permission::updateOrCreate(
                                [
                                    'table_name' => $model,
                                    'role_id' => $role->id,
                                    'area' => 'table',
                                    'key' => $parent_type.'_'.$model,
                                    'account_id' => Auth::user()->account_id,
                                    'is_parent' => 1,
                                ],
                                [
                                    'perm_type' => $parent_type,
                                    'perm_value' => 'N',
                                    'is_parent' => 1,
                                ]
                            );
                        }
                    }
                    //die();
                }
            }

        }
        //die();
        if(!$role->name)
            $role->name = \Str::of($request->display_name)->slug('_');
        $role->display_name = $request->display_name;
        $role->is_admin = $request->is_admin;
        $role->save();
        // foreach($request->all()['perms_field'] as $model => $perms) {
        //     foreach($perms['fields'] as $field_name => $field_perms) {
        //         foreach($field_perms as $type => $perm) {
        //             if($model == 'points')
        //                 $row_type = \DB::table('data_types')->where('name', 'orders')->first();
        //             else
        //                 $row_type = \DB::table('data_types')->where('name', $model)->first();
        //             $field = \DB::table('data_rows')->where(['data_type_id' => $row_type->id])->where('field', $field_name)->first();

        //             $permission = \App\Models\Permission::firstOrCreate(
        //                 [
        //                     'table_name' => $model,
        //                     'field_name' => $field_name,
        //                     'field_display_name' => $field->display_name,
        //                     'perm_type' => $type,
        //                     'account_id' => Auth::user()->account_id,
        //                     'role_id' => $role->id,
        //                     'area' => 'field',
        //                     'key' => $type.'_'.$model.'_'.$field_name,
        //                 ]
        //             );
        //             $permission->perm_value = $perm;
        //             $permission->save();
        //             $ids[] = $permission->id;
        //         }
                
        //     }
            
        // }
        //die();
        $role->permissions()->syncWithoutDetaching($ids);
        //die();
        cache()->flush();
        return redirect()->back();
    }

}