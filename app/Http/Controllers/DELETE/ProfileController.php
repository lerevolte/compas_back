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

class ProfileController extends Controller
{
    public function show()
    {
        $user = \Auth::user();
        
        return response()->json($user);
    }

};