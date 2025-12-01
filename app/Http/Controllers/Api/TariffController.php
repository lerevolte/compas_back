<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Tariff;

class TariffController extends Controller
{
    public function list(Request $request) 
    {
        $data = Tariff::list();

        return response()->json($data);
    }

    public function set($id) 
    {
        $data = Tariff::set($id);

        return response()->json($data);
    }
}