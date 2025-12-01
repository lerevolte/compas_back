<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Models\Balance;
use App\Models\File;
use App\Models\BalanceOperation;
use App\Models\Table;
use YooKassa\Client;


class BalanceController extends Controller
{
    public function index(Request $request)
    {
        if(!\Auth::user()->is_admin)
            return response()->json([
                'message' => 'Доступ ограничен'
            ], 403);
        
        $balance = Balance::first();

        $data = $balance->getInfo($request);

        return response()->json($data);
    }

    public function operations(Request $request)
    {
        $balance = Balance::first();

        $list = $balance->operations($request->all());
        $table = Table::balance();

        $data = array(
            'table' => $table,
            'list' => $list,
            'expense' => $list['expense']
        );

        return response()->json($data);
    }

    public function payment(Request $request)
    {
        $balance = Balance::first();
        
        $data = $balance->payment($request->sum, $request->payer);

        return response()->json($data);
    }

}