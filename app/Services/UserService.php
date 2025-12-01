<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\User;

class UserService
{
    public function get($q = '')
    {
        if(mb_strlen($q) >= 0) {
            $q = mb_strtolower($q);
            $field_name = 'name';
            $q = str_replace(' ', '%', $q);
            $q = '%'.$q.'%';
            $items = User::where([
                ['name', 'LIKE', $q],
                ['deleted_at', null]
            ])->orWhere([
                ['id', (int)$q],
                ['deleted_at', null]
            ])->orWhere([
                ['last_name', $q],
                ['deleted_at', null]
            ])->limit(20)->get();
            
            if(!$items) {
                $items = User::whereNull('deleted_at')->limit(20)->get();
            }
            
        };

        $items = User::whereNull('deleted_at')->limit(20)->get();

        return $items;
    }
}