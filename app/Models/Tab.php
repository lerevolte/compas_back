<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tab extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'tab_roles');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tab_users');
    }
}