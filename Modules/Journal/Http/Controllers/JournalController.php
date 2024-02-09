<?php

namespace Modules\Journal\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Journal\Entities\Salary;
use Modules\Journal\Entities\Mileage;
use Modules\Journal\Entities\FundRecord;
use Modules\Logistic\Entities\Driver;
use Modules\Logistic\Entities\Car;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('journal::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('journal::create');
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
        return view('journal::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('journal::edit');
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

    public function calculate()
    {
        // $drivers = Driver::get();
        // foreach ($drivers as $driver) {
        //     if($driver->rate) {
        //         if(!Salary::where(['driver_id' => $driver->id, 'date' => date('Y-m-d')])->exists()) {
        //             $salary = new Salary;
        //             $salary->save();
        //             $salary->name = 'Зарплата #'.$salary->id;
        //             $salary->driver_id = $driver->id;
        //             $salary->rate = $driver->rate;
        //             $salary->date = date('Y-m-d');
        //             $salary->save();
        //             //$salary->timework
        //             //$salary->increase
        //         } else {
        //             $salary = Salary::where(['driver_id' => $driver->id, 'date' => date('Y-m-d')])->first();
        //         }

        //         if(!FundRecord::where(['driver_id' => $driver->id, 'date' => date('Y-m-d')])->exists()) {
        //             echo 'fund_record';
        //             $prev_fund_record = FundRecord::where(['driver_id' => $driver->id, 'date' => date('Y-m-d', strtotime('-1 day'))])->first();
        //             $fund_record = new FundRecord;
        //             $fund_record->save();
        //             $fund_record->name = 'Аварийный фонд #'.$salary->id;
        //             $fund_record->driver_id = $driver->id;
        //             if($prev_fund_record)
        //                 $fund_record->emergency_fund_start_day = $prev_fund_record->emergency_fund_end_day;
        //             else
        //                 $fund_record->emergency_fund_start_day = 0;
        //             if($driver->fund_calculate_type == 1) {
        //                 $fund_record->emergency_fund_end_day = $driver->fund_percent/100 * $salary->rate;
        //             } elseif($driver->fund_calculate_type == 2) {
        //                 $fund_record->emergency_fund_end_day = $driver->fund_percent/100 * (int)$salary->award;
        //             } elseif($driver->fund_calculate_type == 3) {
        //                 $fund_record->emergency_fund_end_day = $driver->fund_percent/100 * ($salary->rate + $salary->increase + $salary->award);
        //             } else {
        //                 $fund_record->emergency_fund_end_day = $fund_record->emergency_fund_start_day;
        //             }
                    
        //             $fund_record->emergency_fund_day = $fund_record->emergency_fund_end_day - $fund_record->emergency_fund_start_day;
        //             $fund_record->date = date('Y-m-d');
        //             $fund_record->save();
        //         }
        //     }
        // }

        $cars = Car::get();
        foreach ($cars as $car) {
            if(!Mileage::where(['car_id' => $car->id, 'date' => date('Y-m-d')])->exists()) {
                $prev_mileage = Mileage::where(['car_id' => $car->id, 'date' => date('Y-m-d', strtotime('-1 day'))])->first();
                $mileage = new Mileage;
                $mileage->save();
                $mileage->name = 'Пробег #'.$mileage->id;
                $mileage->car_id = $car->id;
                $mileage->date = date('Y-m-d');
                if($prev_mileage) {
                    $mileage->mileage_start_day = $prev_mileage->mileage_end_day;
                    if(false) {//ROUTE MILEAGE

                    } else {
                        $mileage->mileage_end_day = $prev_mileage->mileage_end_day + $prev_mileage->auto_day_mileage;
                    };
                    $mileage->auto_day_mileage = $prev_mileage->auto_day_mileage;

                    $mileage->engine_hours_start_day = $prev_mileage->engine_hours_end_day;
                    if(false) {//ROUTE MILEAGE

                    } else {
                        $mileage->engine_hours_end_day = $prev_mileage->engine_hours_end_day + $prev_mileage->auto_day_engine_hours;
                    };
                    $mileage->auto_day_engine_hours = $prev_mileage->auto_day_engine_hours;
                } else {
                    $mileage->mileage_start_day = 0;
                    if(false) {//ROUTE MILEAGE

                    } else {
                        $mileage->mileage_end_day = 0;
                    };
                    $mileage->auto_day_mileage = 0;

                    $mileage->engine_hours_start_day = 0;
                    if(false) {//ROUTE MILEAGE

                    } else {
                        $mileage->engine_hours_end_day = 0;
                    };
                    $mileage->auto_day_engine_hours = 0;
                };

                $mileage->mileage_day = $mileage->mileage_end_day - $mileage->mileage_start_day;
                $mileage->engine_hours_day = $mileage->engine_hours_end_day - $mileage->engine_hours_start_day;
                $mileage->save();
            }
        }
    }
}
