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
use App\Models\Balance;
use Carbon\Carbon;

class ProfileController extends Controller
{
    public function show()
    {
        $user = \Auth::user();

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => 'profile', 'user_id' => $user->id])->exists())
            \DB::table('local_cache')->where(['url' => 'profile', 'user_id' => $user->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'profile', 'user_id' => $user->id, 'created_at' => $now, 'updated_at' => $now]);

        //cache()->flush();
        $user->is_admin = $user->isAdmin();
        $name = json_decode($user->name, true);
        if(is_array($name) && array_key_exists('value', $name))
            $user->name = $name['value'];

        $last_name = json_decode($user->last_name, true);

        if(is_array($last_name) && array_key_exists('value', $last_name))
            $user->last_name = $last_name['value'];

        if(!$user->name && !$user->last_name)
            $user->name = $user->id;

        $balance = Balance::select('sum')->first();

        $tenant = tenant('id');
        if($tenant)
            $domain = "$tenant.compas.pro";
        else
            $domain = "compas.pro";
        
        
        return response()->json(array('user' => $user, 'balance' => $balance->sum, 'domain' => $domain));
    }

};