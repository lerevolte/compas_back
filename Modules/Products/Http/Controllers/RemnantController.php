<?php

namespace Modules\Products\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Products\Entities\Remnant;

class RemnantController extends Controller
{

    public function search(Request $request)
    {
        $products = collect();

        if($request->q && mb_strlen($request->q) > 3) {
            $products = Remnant::where('name', 'LIKE', '%'. $request->q.'%')->with('product')->get();
        }
        
        return response()->json($products);
    }
}
