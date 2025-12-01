<?php

namespace Modules\Osago\Http\Controllers\Api;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OsagoController extends Controller
{
    public function link(Request $request, int $id)
    {
        $polis = \App\Models\Osago\OsagoPolis::find($id);
        $link = \Modules\Osago\Entities\Module::getPaymentLink($polis, $request->all());

        return response()->json($link);
    }

    public function offers(int $id)
    {
        $polis = \App\Models\Osago\OsagoPolis::find($id);
        $offers = \Modules\Osago\Entities\Module::getOffers($polis);

        return response()->json($offers);
    }

    
}
