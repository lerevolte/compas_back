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

    public function fines()
    {
        $roles = json_decode($this->role_id, true);
        $fines = Fine::get();
        $fine_ids = array();
        foreach ($fines as $fine) {
            if(strstr($fine->role_id, '"'.$this->id.'"'))
                $fine_ids[] = $fine->id;
            
        }
        return Fine::whereIn('id', $fine_ids)->orderBy('name')->get();
    }

    public function users()
    {

        return $this->belongsToMany(User::class, 'user_roles')->orderBy('name');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function permissions_tables()
    {
        return $this->belongsToMany(Permission::class)->where('area', 'entity');
    }

    public static function list()
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

        return $data;
    }

    // public function permissions_fields()
    // {
    //     return $this->belongsToMany(Permission::class)->where('area', 'field');
    // }

}
