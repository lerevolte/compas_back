<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use TCG\Voyager\Traits\VoyagerUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Support\Str;

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
        'bitrix24_config'
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

    // public function role()
    // {
    //     return $this->hasOne(Role::class);
    // }

    public static function boot()
    {
        parent::boot();
        static::creating(function($model)
        {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
            if($model->role_id) {
                $role = Role::find($model->role_id);
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
                $settings = \DB::table('settings')->where('key', 'tables')->first();
                if($settings) {
                    $tables = $settings->value;
                    $model->tables = $tables;
                }

                $settings = \DB::table('settings')->where([
                    'type' => 'sidebar',
                    'user_id' => null
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
                    'user_id' => null
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
            if($model->getOriginal('employee_id') != $model->employee_id) {
                if($model->getOriginal('employee_id')) {
                    $employee = Employee::find($model->getOriginal('employee_id'));
                    $employee->saveRelations('related_user_id', null);
                    $employee->related_user_id = null;
                    $employee->saveQuietly();
                    
                }

                if($model->employee_id) {
                    $employee = Employee::find($model->employee_id);
                    $employee->saveRelations('related_user_id', $model->id);
                    $employee->related_user_id = $model->id;
                    $employee->saveQuietly();
                }
            }

            if($model->getOriginal('role_id') != $model->role_id) {
                if($model->role_id && $role = Role::find($model->role_id)) {
                    $model->tables = $role->tables;

                    $user_sidebar = \DB::table('settings')->where([
                        'type' => 'sidebar',
                        'user_id' => $model->id
                    ])->first();
                    
                    if($user_sidebar)
                        \DB::table('settings')->where([
                            'type' => 'sidebar',
                            'user_id' => $model->id
                        ])->update(['value' => $role->sidebar]);
                    else
                        \DB::table('settings')->insert([
                            'key' => 'sidebar',
                            'type' => 'sidebar',
                            'user_id' => $model->id,
                            'value' => $role->sidebar
                        ]);

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
    }

    public function generateToken()
    {
        $this->api_token = Str::random(60);
        $this->save();

        return $this->api_token;
    }

    public function isAdmin()
    {
        $this->loadPermissionsRelations();

        $roles = $this->roles_all();
        foreach ($roles as $role) {
            if(is_object($role) && $role->is_admin)
                return true;
        }

        return false;
    }

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
        $s = get_settings();
        $sidebar_items = $s['settings']['sidebar_items'];
        $user_id = $this->id;
        //$item = cache('sidebar-'.$user_id);
        $cache_name = tenant('id').':sidebar-'.$user_id;
        $item = cache()->getMemcached()->get($cache_name);
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

        if(!$item) {
            \DB::table('settings')->insert([
                'key' => 'sidebar',
                'display_name' => 'Sidemenu',
                'value' => json_encode($sidebar_items),
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
                    $ids[] = $menu_item['id'];
                }
            else
                $menu = array();
            foreach($sidebar_items as $sidebar_item) {
                if(!in_array($sidebar_item['id'], $ids)) {
                    $need_create = true;
                    $menu[] = $sidebar_item;
                }
            }
            if($need_create) {
                \DB::table('settings')->where('id', $item->id)->update(['value' => json_encode($menu)]);
            }
        }

        return $menu;
    }

}
