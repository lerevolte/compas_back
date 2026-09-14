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

    public function geoposition(User $user)
    {
        $me = Auth::user();
        if (!$me->is_admin && $me->id != $user->id) {
            $entityId = \DB::table('data_types')->where('slug', 'users')->value('id');
            $readP = $me->role_id && $entityId
                ? \DB::table('permissions')->where('role_id', $me->role_id)->where('entity_id', $entityId)->value('read_p')
                : null;
            if ($readP == 'N') {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }
        if (!\Schema::hasColumn('users', 'geoposition')) {
            return response()->json(['geoposition' => null]);
        }
        $raw = \DB::table('users')->where('id', $user->id)->value('geoposition');
        $decoded = $raw ? json_decode($raw, true) : null;

        return response()->json(['geoposition' => is_array($decoded) ? $decoded : null]);
    }
};