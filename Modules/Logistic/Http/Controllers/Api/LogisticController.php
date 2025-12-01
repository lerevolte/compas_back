<?php

namespace Modules\Logistic\Http\Controllers\Api;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Wialon\Entities\Config;
use Modules\Logistic\Entities\Car;

class LogisticController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function get_settings()
    {
        $item = \DB::table('settings')->where([
            'entity' => 'logistic',
            'type' => 'logistic',
            'user_id' => \Auth::user()->id
        ])->first();
        if(!$item) {
            $item = \DB::table('settings')->where([
                'entity' => 'logistic',
                'type' => 'logistic',
                //'user_id' => \Auth::user()->id
            ])->first();
            \DB::table('settings')->insert([
                'key' => $item->key,
                'title' => $item->title,
                'value' => $item->value,
                'entity' => 'logistic',
                'type' => 'logistic',
                'user_id' => \Auth::user()->id
            ]);
            $item = \DB::table('settings')->where([
                'entity' => 'logistic',
                'type' => 'logistic',
                'user_id' => \Auth::user()->id
            ])->first();
        }
        $data = json_decode($item->value, true);

        return response()->json($data);
        
    }

    public function set_settings(Request $request)
    {
        $data = json_encode($request->config, JSON_UNESCAPED_UNICODE);
        \DB::table('settings')->where([
            'entity' => 'logistic',
            'type' => 'logistic',
            'user_id' => \Auth::user()->id
        ])->update(['value' => $data]);


        return response()->json($request->menu);
    }

    public function day_statistic(Request $request)
    {
        if($request->date)
            $routes = \App\Models\Route::where('date', $request->date)->get();
        else
            $routes = \App\Models\Route::get();

        $mileage = $time = $delivery_price = $reserve_for_delivery = $weight = 0;
        foreach ($routes as $route) {
            $mileage+= $route->mileage;
            $time+= $route->time;
            $delivery_price+= $route->delivery_price;
            $reserve_for_delivery+= $route->reserve_for_delivery;
            $weight+= $route->weight;
        }

        return response()->json([
            'count_cars' => $routes->count(),
            'mileage' => $mileage,
            'time' => $time,
            'delivery_price' => $delivery_price,
            'reserve_for_delivery' => $reserve_for_delivery,
            'weight' => $weight
        ]);
    }
    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function companies()
    {
        $companies = \DB::table('companies')->select('id', 'name')->get();

        return response()->json($companies);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('logistic::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('logistic::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
