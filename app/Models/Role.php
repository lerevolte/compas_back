<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\FieldValue, App\Traits\ModelActions;


class Role extends \TCG\Voyager\Models\Role
{
    use FieldValue, ModelActions;

    protected $guarded = [];

    public function users()
    {

        return $this->belongsToMany(User::class, 'user_roles')->orderBy('name');
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    public function permissions_tables()
    {
        return $this->belongsToMany(Permission::class)->where('area', 'entity');
    }

    public static function list()
    {
        $data = array();

        $items = Role::orderByRaw("(id = 1) desc, (name = 'external_link') desc")->orderBy('display_name', 'asc')->get();
        foreach($items as $item) {
            $data[] = array(
                'id' => $item->id,
                'label' => $item->display_name,
                'value' => $item->id,
                'is_admin' => $item->is_admin,
                'is_permanent' => $item->is_permanent
            );
        }

        return $data;
    }

    public function format()
    {
        $res = array(
            'id' => $this->id,
            'label' => $this->display_name,
            'value' => $this->id,
            'is_admin' => $this->is_admin,
            'is_permanent' => $this->is_permanent
        );

        return $res;
    }

    public function tabs()
    {
        return $this->belongsToMany(Tab::class, 'tab_roles');
    }

    // public function permissions_fields()
    // {
    //     return $this->belongsToMany(Permission::class)->where('area', 'field');
    // }

}
