<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;


class SidebarItem extends Model
{
    use NodeTrait;
    protected $hidden = [
        'created_at',
        'updated_at',
        //'code',
        //'sort',
        '_lft',
        '_rgt',
        'parent_id'
    ];
}
