<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Balance extends Model
{
	public function get() {
		return $this->sum;
	}
    public function plus($sum) {
    	$operation = new BalanceOperation;
    	$operation->sum = $sum;
    	$operation->type = 'plus';
    	$operation->balance_id = $this->id;
        $operation->comment = 'Пополнение баланса';
        $operation->date = date("d.m.Y");
    	$operation->save();
    	$this->sum = $this->sum + $sum;
    	$this->save();
    }

    public function minus($sum) {
    	$operation = new BalanceOperation;
    	$operation->sum = $sum;
    	$operation->type = 'minus';
    	$operation->balance_id = $this->id;
        $operation->date = date("d.m.Y");
    	$operation->save();
    	$this->sum = $this->sum - $sum;
    	$this->save();
    }

    public function operations()
    {
        return $this->hasMany(BalanceOperation::class);
    }

    public function calculateMinuses($date)
    {
        if (!strstr($date, '-')) {
            $date_end = $date;
        } else {
            $d = explode('-', $date);
            $date = $d[0];
            $date_end = $d[1];
        }

        if($date != $date_end) {
            $from = $date;
            $to = $date_end;
            if ($from != $to) {
                $routes_by_date = Route::whereBetween('date_format', [date("Y-m-d", strtotime($from)), date("Y-m-d", strtotime($to))])->orderBy('id', 'desc')->get()->groupBy('date_format');
            } else {
                $routes_by_date = Route::whereDate('date_format',date("Y-m-d", strtotime($date)))->get()->groupBy('date_format');
            }
        } else {
            $routes_by_date = Route::whereDate('date_format',date("Y-m-d", strtotime($date)))->get()->groupBy('date_format');
        }

        foreach ($routes_by_date as $date => $routes) {
            $mobile_count = 0;
            foreach($routes as $route) {
                if(optional($route->driver)->user_id) {
                    $mobile_count++;
                }
            }
            echo 'date :'.$mobile_count.'<br>';
            $operation = new BalanceOperation;
            $operation->sum = count($routes)*30 + $mobile_count*5;
            $operation->type = 'minus';
            $operation->balance_id = $this->id;
            $operation->count_mobile = $mobile_count;
            $operation->comment = 'Расход за день';
            $operation->count_routes = count($routes);
            $operation->date = date("d.m.Y", strtotime($date));
            $operation->save();
            $this->sum = $this->sum - $operation->sum;
            $this->save();
            
        }
    }
}
