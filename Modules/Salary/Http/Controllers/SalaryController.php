<?php

namespace Modules\Salary\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Logistic\Entities\Driver;
use Modules\Salary\Entities\Salary;
use Modules\EmergencyFund\Entities\FundRecord;
use Nwidart\Modules\Facades\Module;

class SalaryController extends Controller
{
    public function calculate()
    {
        $drivers = Driver::get();
        foreach ($drivers as $driver) {
            if($driver->rate) {
                if(!Salary::where(['driver_id' => $driver->id, 'date' => date('Y-m-d')])->exists()) {
                    $salary = new Salary;
                    $salary->save();
                    $salary->name = 'Зарплата #'.$salary->id;
                    $salary->driver_id = $driver->id;
                    $salary->rate = $driver->rate;
                    $salary->date = date('Y-m-d');
                    $salary->save();
                    //$salary->timework
                    //$salary->increase
                } else {
                    $salary = Salary::where(['driver_id' => $driver->id, 'date' => date('Y-m-d')])->first();
                }
                if(Module::isEnabled('EmergencyFund')) {
                    if(!FundRecord::where(['driver_id' => $driver->id, 'date' => date('Y-m-d')])->exists() && $salary) {
                        $prev_fund_record = FundRecord::where(['driver_id' => $driver->id, 'date' => date('Y-m-d', strtotime('-1 day'))])->first();
                        $fund_record = new FundRecord;
                        $fund_record->save();
                        $fund_record->name = 'Аварийный фонд #'.$fund_record->id;
                        $fund_record->driver_id = $driver->id;
                        if($prev_fund_record)
                            $fund_record->emergency_fund_start_day = $prev_fund_record->emergency_fund_end_day;
                        else
                            $fund_record->emergency_fund_start_day = 0;
                        if($driver->fund_calculate_type == 1) {
                            $fund_record->emergency_fund_end_day = $driver->fund_percent/100 * $salary->rate;
                        } elseif($driver->fund_calculate_type == 2) {
                            $fund_record->emergency_fund_end_day = $driver->fund_percent/100 * (int)$salary->award;
                        } elseif($driver->fund_calculate_type == 3) {
                            $fund_record->emergency_fund_end_day = $driver->fund_percent/100 * ($salary->rate + $salary->increase + $salary->award);
                        } else {
                            $fund_record->emergency_fund_end_day = $fund_record->emergency_fund_start_day;
                        }
                        
                        $fund_record->emergency_fund_day = $fund_record->emergency_fund_end_day - $fund_record->emergency_fund_start_day;
                        $fund_record->date = date('Y-m-d');
                        $fund_record->save();
                    }
                }
            }

            
        }
    }
}
