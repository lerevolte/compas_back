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
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if($request->email) {
            $user = User::where('email', $request->email)->firstOrFail();
            if($user && $user->password == Hash::check($request->password, $user->password)) {
                //$token = $user->generateToken();
                $menu = $user->getSidebar();

                return response()->json(['code' => 200, 'token' => $user->api_token, 'url' => $menu[0]['link']]);
            }
        }

        return response()->json(['code' => 401, 'message' => 'Неверный логин или пароль']);
    }
}
