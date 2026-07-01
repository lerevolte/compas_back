<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use TCG\Voyager\Traits\VoyagerUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Support\Str;
use App\Services\CrudService;
use App\Notifications\PasswordResetNotification;

class User extends \TCG\Voyager\Models\User
{
    use FieldValue, ModelActions, HasFactory, ColorGenerator, Notifiable, VoyagerUser, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    //     'crm_id',
    //     'last_name',
    //     'second_name',
    //     'phone',
    //     'work_phone'
    // ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'tables',
        'bitrix24_config',
        'api_token'
    ];
    // protected $colors = [
    //     'linear-gradient(to bottom, #aeee90 2%, #65dd78 100%)',
    //     'linear-gradient(to bottom, #ffdc96 2%, #ffc28d)',
    //     'linear-gradient(to bottom, #f1c3ff 2%, #ee9cff)',
    //     'linear-gradient(to bottom, #9ce1ff 2%, #6dc2ff 100%)',
    //     'linear-gradient(to bottom, #a8c7ff 2%, #948fff)',
    //     'linear-gradient(to bottom, #71d2fc 2%, #9490ff 100%)',
    //     'linear-gradient(to bottom, #5ef9e2 2%, #50e2d2 100%)',
    //     'linear-gradient(to bottom, #f1c3ff 2%, #a8c7ff)',
    //     'linear-gradient(to bottom, #aeee90 2%, #48b85a 99%)',
    //     'linear-gradient(to bottom, #ffdc96 2%, #ff8d8d 100%)',
    //     'linear-gradient(to bottom, #ffab8e 2%, #ff8596 100%)',
    //     'linear-gradient(to bottom, #ee9090 2%, #6765dd 100%)',
    //     'linear-gradient(to bottom, #ee90d2 4%, #dd6565 100%)',
    //     'linear-gradient(to bottom, #ee90d2 4%, #dd6565 100%)',
    //     'linear-gradient(to bottom, #9390ee 2%, #dd65d5)'
    // ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function($model)
        {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
            if($model->role_id && $model->role->sidebar) {
                $role = $model->role;
                $model->tables = $role->tables;

                \DB::table('settings')->insert([
                    'key' => 'sidebar',
                    'type' => 'sidebar',
                    'user_id' => $model->id,
                    'value' => $role->sidebar
                ]);

                if($role->menus) {
                    $menus = json_decode($role->menus, true);
                    foreach($menus as $slug => $menu) {
                        \DB::table('settings')->insert([
                            'entity' => $slug,
                            'key' => 'menu',
                            'type' => 'menu',
                            'user_id' => $model->id,
                            'value' => $menu
                        ]);
                    }
                    
                }
            } else {
                $settings = \DB::table('settings')->where('key', 'tables')->where('user_id', 1)->first();
                if($settings) {
                    $tables = $settings->value;
                    $model->tables = $tables;
                }

                $settings = \DB::table('settings')->where([
                    'type' => 'sidebar',
                    'user_id' => 1
                ])->first();
                if($settings) {
                    \DB::table('settings')->insert([
                        'key' => 'sidebar',
                        'type' => 'sidebar',
                        'user_id' => $model->id,
                        'value' => $settings->value
                    ]);
                }

                $menus = \DB::table('settings')->where([
                    'key' => 'menu',
                    'type' => 'menu',
                    'user_id' => 1
                ])->get();

                foreach($menus as $menu) {
                    \DB::table('settings')->insert([
                        'entity' => $menu->entity,
                        'key' => 'menu',
                        'type' => 'menu',
                        'user_id' => $model->id,
                        'value' => $menu->value
                    ]);
                }
            }

            
            
        });
        static::updating(function($model)
        {   

            if($model->getOriginal('role_id') != $model->role_id) {
                if($model->role_id && $role = Role::find($model->role_id)) {
                    $model->tables = $role->tables;

                    $user_sidebar = \DB::table('settings')->where([
                        'type' => 'sidebar',
                        'user_id' => $model->id
                    ])->first();
                    
                    if($user_sidebar && $role->sidebar) {
                        \DB::table('settings')->where([
                            'type' => 'sidebar',
                            'user_id' => $model->id
                        ])->update(['value' => $role->sidebar]);
                    } elseif($role->sidebar) {
                        \DB::table('settings')->insert([
                            'key' => 'sidebar',
                            'type' => 'sidebar',
                            'user_id' => $model->id,
                            'value' => $role->sidebar
                        ]);
                    };
                    cache()->getMemcached()->delete(tenant('id').':sidebarmenu-'.$model->id);

                    if($role->menus) {
                        $menus = json_decode($role->menus, true);
                        foreach($menus as $slug => $menu) {
                            $user_menu = \DB::table('settings')->where([
                                'entity' => $slug,
                                'type' => 'menu',
                                'user_id' => $model->id,
                            ])->first();
                            
                            if($user_menu)
                                \DB::table('settings')->where([
                                    'entity' => $slug,
                                    'type' => 'menu',
                                    'user_id' => $model->id,
                                ])->update(['value' => $menu]);
                            else
                                \DB::table('settings')->insert([
                                    'entity' => $slug,
                                    'key' => 'menu',
                                    'type' => 'menu',
                                    'user_id' => $model->id,
                                    'value' => $menu
                                ]);
                        }
                    }
                }

            }


        });

        static::updated(function($model){
            \App\Jobs\UserAccountJob::dispatch();
            // $tenant = tenant('id');
            // $c1 = \DB::table('users')->whereNull('deleted_at')->count();
            // $c2 = \DB::table('users')->whereNotNull('deleted_at')->count();

            // tenancy()->central(function () use ($tenant, $c1, $c2) {
            //     $crudService = new CrudService;
            //     $account = Account::where('tenant_id', $tenant)->first();
            //     $data = [
            //         'id' => $account->id,
            //         'count_users' => $c1,
            //         'count_deleted_users' => $c2
            //     ];

            //     $result = $crudService->batch('accounts', [$data]);
            // });
        });

        static::created(function($model){
            \App\Jobs\UserAccountJob::dispatch();
            // $tenant = tenant('id');
            // $count_users = \DB::table('users')->whereNull('deleted_at')->count();
            // $count_deleted_users = \DB::table('users')->whereNotNull('deleted_at')->count();
            // if($tenant) {
            //     tenancy()->central(function () use ($tenant, $count_users, $count_deleted_users) {
            //         $crudService = new CrudService;
            //         $account = Account::where('tenant_id', $tenant)->first();
                    
            //         $data = [
            //             'id' => $account->id,
            //             'count_users' => $count_users,
            //             'count_deleted_users' => $count_deleted_users
            //         ];

            //         $result = $crudService->batch('accounts', [$data]);
            //     });
            // }
        });

        static::deleted(function($model){
            \App\Jobs\UserAccountJob::dispatch();
            // $tenant = tenant('id');
            // $c1 = \DB::table('users')->whereNull('deleted_at')->count();
            // $c2 = \DB::table('users')->whereNotNull('deleted_at')->count();
            // if($tenant) {
            //     tenancy()->central(function () use ($tenant, $c1, $c2) {
            //         $crudService = new CrudService;
            //         $account = Account::where('tenant_id', $tenant)->first();
            //         $data = [
            //             'id' => $account->id,
            //             'count_users' => $c1,
            //             'count_deleted_users' => $c2
            //         ];

            //         $result = $crudService->batch('accounts', [$data]);
            //     });
            // }
        });
    }

    public function generateToken()
    {
        $token = $this->api_token = Str::random(60);
        $tenant = tenant('id');

        $t = \DB::table('settings')->where([
            'type' => 'account',
            'key' => 'login_days'
        ])->first();

        if(!$t) {
            \DB::table('settings')->insert([
                'key' => 'login_days',
                'display_name' => 'Сколько дней хранить историю входа',
                'type' => 'account',
                'value' => 30
            ]);


            $t = \DB::table('settings')->where([
                'type' => 'account',
                'key' => 'login_days'
            ])->first();
        }
        $this->token_expiration_date = date('Y-m-d H:i:s', strtotime("+ $t->value days"));
        $this->timestamps = false;
        $this->saveQuietly();

        if($tenant) {
            tenancy()->central(function () use ($tenant, $token) {
                $account = Account::where('tenant_id', $tenant)->first();
                $account->admin_token = $token;
                $account->saveQuietly();
            });
        }

        return $this->api_token;
    }

    public function isAdmin()
    {
        return $this->is_admin;
        // $this->loadPermissionsRelations();

        // $roles = $this->roles_all();
        // foreach ($roles as $role) {
        //     if(is_object($role) && $role->is_admin)
        //         return true;
        // }

        // return false;
    }

    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class, 'user_roles')->orderBy('name');
    // }

    public function getPermission($name)
    {
        $this->loadPermissionsRelations();

        $_permissions = $this->roles_all()
                              ->pluck('permissions')->flatten()
                              ->unique()->toArray();
        foreach ($_permissions as $perm) {
            if(isset($perm['key']) && $perm['key'] == $name && $perm['area'] && !$perm['is_parent'])
                return $perm['value'];
        }

        return;
    }

    public function getPermissions()
    {
        $this->loadPermissionsRelations();

        $_permissions = $this->roles_all()
                              ->pluck('permissions')->flatten()
                              ->unique()->toArray();
        

        return $_permissions;
    }

    public function canRead($field, $model) {
        return \App\Models\Field::checkReadPermission($field, $model);
    }

    public function canWrite($field, $model) {
        return \App\Models\Field::checkWritePermission($field, $model);
    }

    // public function getColor() {
    //     if(!$this->color) {
    //         // $color1 = sprintf("#%06x", rand(0, 16777215));
    //         // $color2 = sprintf("#%06x", rand(0, 16777215));
    //         // $angle = rand(1, 360);
    //         // $gradient = "linear-gradient({$angle}deg, {$color1}, {$color2})";
    //         $this->color = $this->colors[array_rand($this->colors)];
    //         $this->saveQuietly();
    //     }

    //     return $this->color;
    // }

    public function getAvatarAttribute($value)
    {
        return $value;
    }

    public function getSidebar()
    {
        //\App\Models\Settings::clear_cache();
        \App\Models\SidebarItem::fixTree();
        $s = app('settings');

        if(!$s)
            $s = \App\Models\Settings::update();
        $sidebar_items2 = $s['settings']['sidebar_items'];
        $sidebar_items = [];
        $seen_slugs = [];
        foreach($sidebar_items2 as $item) {
            // Дедуп по slug: в sidebar_items могли появиться дубли (например две
            // строки companies). Без дедупа пункт задваивается в меню и в списке
            // «Сущности», а близнец с другим id мешает сохранению порядка/скрытия
            // (8591, 8588). Оставляем первый по порядку дерева.
            $slug = $item['slug'] ?? null;
            if($slug !== null && $slug !== '' && isset($seen_slugs[$slug])) {
                continue;
            }
            if($slug !== null && $slug !== '') {
                $seen_slugs[$slug] = true;
            }
            $sidebar_items[$item['id']] = $item;
        }
        $user_id = $this->id;
        //$item = cache('sidebar-'.$user_id);
        $cache_name = tenant('id').':sidebarmenu-'.$user_id;
        $item = cache()->getMemcached()->get($cache_name);
        if($this->role)
            $permissions = $this->role->permissions->keyBy('entity_id');
        if(!$item) {
            // $item = cache()->rememberForever('sidebar-'.$user_id, function() use ($user_id)
            // {
                $item = \DB::table('settings')->where([
                    'type' => 'sidebar',
                    'user_id' => $user_id
                ])->first();
                cache()->getMemcached()->add($cache_name, $item);
                //return $data;
            //});
        }
        $tariff = Tariff::current();
        $blocked_pages = [];
        if($tariff->restrictions) {
            $restrictions_tariff = json_decode($tariff->restrictions,true);
            if(isset($restrictions_tariff['blocked_pages'])) {
                $blocked_pages = $restrictions_tariff['blocked_pages'];
            }
        }
        $sidebar_ids = array();

        if(!$item) {
            if($this->role_id) {
                $menu = $this->role->sidebar;
            }
            if(!isset($menu) || !$menu) {
                $all_menu = \DB::table('settings')->where([
                    'type' => 'sidebar',
                    'user_id' => 1
                ])->first();
                if($all_menu)
                    $menu = $all_menu->value;
                else
                    $menu = json_encode($sidebar_items, JSON_UNESCAPED_UNICODE);
            }
            
            \DB::table('settings')->insert([
                'key' => 'sidebar',
                'display_name' => 'Sidemenu',
                'value' => $menu,
                'type' => 'sidebar',
                'user_id' => $this->id
            ]);
            $item = \DB::table('settings')->where([
                'type' => 'sidebar',
                'user_id' => $this->id
            ])->first();
            $menu = json_decode($item->value, true);
        } else {
            $need_create = false;
            $menu = json_decode($item->value, true);
            $max_id = 0;
            $ids = array();
            if(is_array($menu))
                foreach($menu as $k => $menu_item) {
                    if(isset($menu_item['id']))
                        $ids[] = $menu_item['id'];
                }
            else
                $menu = array();
            foreach($sidebar_items as $sidebar_item) {
                $sidebar_ids[] = $sidebar_item['id'];
                if(!in_array($sidebar_item['id'], $ids)) {
                    $need_create = true;
                    $menu[] = $sidebar_item;
                }
            }
            if($need_create) {
                \DB::table('settings')->where('id', $item->id)->update(['value' => json_encode($menu)]);
            }
        }

        foreach($menu as $k => $item) {

            if(isset($sidebar_items[$item['id']])) {
                $menu[$k]['name'] = $sidebar_items[$item['id']]['name'];
                if(array_key_exists('link', $sidebar_items[$item['id']]))
                    $menu[$k]['link'] = $sidebar_items[$item['id']]['link'];
                $item['name'] = $menu[$k]['name'];
            }

            if(isset($item['children'])) {
                foreach($item['children'] as $i => $child) {
                    if(isset($sidebar_items[$child['id']])) {
                        $menu[$k]['children'][$i]['name'] = $sidebar_items[$child['id']]['name'];
                        if(array_key_exists('link', $sidebar_items[$child['id']]))
                            $menu[$k]['children'][$i]['link'] = $sidebar_items[$child['id']]['link'];
                    }
                    if(!in_array($child['id'], $sidebar_ids) || in_array($child['slug'], $blocked_pages)) {
                        unset($menu[$k]['children'][$i]);
                    }
                    if(isset($sidebar_items[$child['id']]) && $sidebar_items[$child['id']]['enabled'] && isset($item['children'][$i])) {
                        //$menu[$k]['children'][$i]['enabled'] = 1;
                    }
                    
                    if(isset($child['slug']) && isset($s['models'][$child['slug']]) && 
                        isset($permissions[$s['models'][$child['slug']]->id]) && 
                        $permissions[$s['models'][$child['slug']]->id]->read_p == 'N'
                    ) {
                        unset($menu[$k]['children'][$i]);
                    }
                    if(isset($child['slug']) && ($child['slug'] == 'settings' || $child['slug'] == 'modules' || $child['slug'] == 'trash' || $child['slug'] == 'roles' || $child['slug'] == 'tariffs') && !$this->isAdmin()) {
                        unset($menu[$k]['children'][$i]);
                    }
                }
            }
            if(isset($item['is_group']) && $item['is_group']) {
                $menu[$k]['children'] = array_values($menu[$k]['children']);
                if(!count($menu[$k]['children']))
                    unset($menu[$k]);
                continue;
            }
            if(!in_array($item['id'], $sidebar_ids) || in_array($item['slug'], $blocked_pages)) {
                if($item['slug'] == 'trash') {
                    // info('$sidebar_ids');
                    // info($sidebar_ids);
                    // info('$blocked_pages');
                    // info($blocked_pages);
                }
                unset($menu[$k]);
            }
            if(isset($sidebar_items[$item['id']]) && $sidebar_items[$item['id']]['enabled'] && isset($menu[$k])) {
                //$menu[$k]['enabled'] = 1;
            }
            
            if(isset($item['slug']) && isset($s['models'][$item['slug']]) && 
                isset($permissions[$s['models'][$item['slug']]->id]) && 
                $permissions[$s['models'][$item['slug']]->id]->read_p == 'N'
            ) {
                unset($menu[$k]);
            }
        }
        if(!$this->isAdmin())
            foreach($menu as $k => $item) {
                if(isset($item['slug']) && ($item['slug'] == 'settings' || $item['slug'] == 'modules' || $item['slug'] == 'trash' || $item['slug'] == 'roles' || $item['slug'] == 'tariffs')) {
                    unset($menu[$k]);
                }
            }

        return array_values($menu);
    }

    /**
     * Get all of the messages for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chat_messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }


    public function tabs()
    {
        return $this->belongsToMany(Tab::class, 'tab_users');
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('users', array(['id' => $this->id, $field => $new_value]), false);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetNotification($token));
    }
}
