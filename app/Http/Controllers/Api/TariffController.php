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
        $data = array();
        $items = Tariff::get();
        foreach ($items as $item) {
            $data[] = array(
                'name' => $item->name,
                'sort' => $item->sort,
                'prices' => json_decode($item->prices, true)
            );
        }

        return response()->json($data);
    }
}