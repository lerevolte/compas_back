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
        
        return response()->json($user);
    }

};