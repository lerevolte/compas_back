<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\User;

class UserController extends Controller
{
    public function list(Request $request)
    {
        $data = array();
        $users = User::get();

        foreach ($users as $user) {
            //$user->getPermissions();
            $roles = $user->roles_all();
            foreach($roles as $k => $role) {
                if(!$role)
                    unset($roles[$k]);
            }
            $data[] = array(
                'id' => $user->id,
                'name' => implode(' ', array($user->name, $user->last_name)),
                'isAdmin' => $user->isAdmin(),
                'roles' => $roles,
                 //             'permissions' => $user->getPermissions()
            );
        }

        return response()->json($data);
    }

    public function permissions()
    {
        $data = get_settings();

        return response()->json($data['entities']);
    }

    public function roles(User $user, Request $request)
    {

        $roles = $user->roles_all();
        foreach($roles as $k => $role) {
            if(!$role)
                unset($roles[$k]);
        }
        

        return response()->json($roles);
    }
};