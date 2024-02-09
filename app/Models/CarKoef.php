<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CarKoef extends Model
{
	protected $fillable = ['category_id', 'mark_id', 'model_id', 'koef'];

    public function category() 
    {
        return $this->belongsTo(\App\Models\CarCategory::class);
    }

    public function model() 
    {
        return $this->belongsTo(\App\Models\CarModel::class);
    }

    public function mark() 
    {
        return $this->belongsTo(\App\Models\CarMark::class);
    }

    public static function getKoef($model_id = 0)
    {
        if(!$model_id) {
            $koef_categories = \App\Models\CarKoef::whereNotNull('category_id')->where('is_calculate', 1)->whereNull('mark_id')->whereNull('model_id')->get();
            $sum = $count = 0;
            foreach($koef_categories as $k) {
                $sum+=$k->koef;
                $count++;
            }
            return round($sum/$count, 2);
        }
        $koef_model = \App\Models\CarKoef::where('model_id', $model_id)->first();
        $k = $koef_model->koef;
        if(!$k) {
            $koef_mark = \App\Models\CarKoef::where('category_id', $koef_model->category_id)->where('mark_id', $koef_model->mark_id)->whereNull('model_id')->first();
            $k = $koef_mark->koef;
            if(!$koef_mark->koef) {
                $koef_category = \App\Models\CarKoef::where('category_id', $koef_model->category_id)->whereNull('mark_id')->whereNull('model_id')->first();
                $k = $koef_category->koef;
            }
        }

        if(isset($koef_category) && $k) {
            $koef_mark->koef = $k;
            $koef_mark->saveQuietly();
            
        }
        if(isset($koef_mark) && $k) {
            $koef_model->koef = $k;
            $koef_model->saveQuietly();
        }



        return $k;
    }

    public static function calculateKoefs()
    {
        $car_ids = \App\Models\Route::where('date', date('d.m.Y', strtotime('-1 day')))->pluck('car_id')->toArray();
        $routes = \App\Models\Route::whereIn('car_id', $car_ids)->where('date', date('d.m.Y', strtotime('-1 day')))->orderBy('id', 'desc')->get();
        $koefs = array();
        $cars_koef = array();
        foreach ($routes as $route) {
            if(!isset($koefs[$route->car->car_model])) {
                $koefs[$route->car->car_model] = array('old_koef' => 0, 'sum_k' => 0, 'count_routes' => 0);
            }

            $orders = $route->orders;
            echo $route->car_id.' : '.$route->date.'<br>';

            if(count($orders)) {
                
                $sum = 0;
                $count = 0;
                foreach($orders as $order) {
                    if($order->fakticeskii_563 && $route->koef*5 > $order->fakticeskii_563 && $order->fakticeskii_563 > 0.4) {
                        echo '+'.$order->fakticeskii_563;
                        $sum+= $order->fakticeskii_563;
                        $count++;
                    }
                    
                };
                if($count) {
                    echo '<br>count - '.$count.'<br>';
                    $koefs[$route->car->car_model] = round($sum/$count, 1);
                    $cars_koef[] = $route->car_id;
                }
                
            }
        }
        //dd($koefs);
        $cars = \App\Models\Car::whereIn('id', $cars_koef)->get();
        $marks = array();
        $categories = array();
        //print_r($koefs);
        $is_calculate = array();
        foreach($koefs as $k=>$koef) {
            if($koef == 0)
                unset($koefs[$k]);
        }
        echo '<pre>';
        print_r($koefs);
        echo '</pre>';
        die();
        foreach($cars as $car) {
            if($car->car_model && $koefs[$car->car_model]) {
                $koef_model = \App\Models\CarKoef::where('model_id', $car->car_model)->first();
                if(!in_array($car->car_model, $is_calculate)) {
                    $is_calculate[] = $car->car_model;
                    echo 'KOEF: '.$koefs[$car->car_model]['old_koef'].' * '.round($koefs[$car->car_model]['sum_k']/$koefs[$car->car_model]['count_routes'], 2).'<br>';
                    $koef_model->koef = round($koefs[$car->car_model]['old_koef']/$koefs[$car->car_model]['count_routes'], 2) * round($koefs[$car->car_model]['sum_k']/$koefs[$car->car_model]['count_routes'], 2);
                    $koef_model->is_calculate = 1;
                    echo 'KOEF11: '.$koef_model->model_id.' - '.$koef_model->koef.'<br>';
                    $koef_model->save();
                }
                
                $car->car_koef = $koef_model->koef;
                $car->saveQuietly();
                $data[$car->car_model] = array(
                    'mark_id' => $koef_model->mark_id,
                    'category_id' => $koef_model->category_id
                );
                
            }
            
        }

        foreach($data as $k => $item) {
            $mark_id = $item['mark_id'];
            $category_id = $item['category_id'];
            $koefs_model = \App\Models\CarKoef::where('category_id', $category_id)->where('mark_id', $mark_id)->whereNotNull('model_id')->where('is_calculate', 1)->get();
            $k = 0;
            $count = 0;
            echo 'Категория '.$category_id.' Марка '.$mark_id.' Моделей '.count($koefs_model).'<br>';
            foreach($koefs_model as $koef_model) {
                if($koef_model->koef) {
                    echo $koef_model->model_id.': '.$koef_model->koef.'<br>';
                    $k+= $koef_model->koef;
                    $count++;
                }
            }
            echo 'K '.$k.'<br>';
            if($count) {
                $koef_objects = \App\Models\CarKoef::where('category_id', $category_id)->where('mark_id', $mark_id)->whereNull('model_id')->get();
                foreach($koef_objects as $koef_o) {
                    $koef_o->koef = round($k/$count, 1);
                    $koef_o->is_calculate = 1;
                    $koef_o->save();
                }
                //\DB::table('car_koefs')->where('category_id', $category_id)->where('mark_id', $mark_id)->whereNull('model_id')->update(['koef' => round($k/$count, 1)]);
            }


            $koefs_mark = \App\Models\CarKoef::where([
                ['category_id', $category_id],
                ['mark_id', '!=', null],
                ['model_id', null],
                ['is_calculate', '=', 1],
            ])->get();
            $k = 0;
            $count = 0;
            foreach($koefs_mark as $koef_mark) {
                if($koef_mark->koef) {
                    $k+= $koef_mark->koef;
                    $count++;
                }
            }
            echo 'Категория '.$category_id.' '.$k.' '.$count.'<br>';
            if($count) {
                $koef_objects = \App\Models\CarKoef::where('category_id', $category_id)->whereNull('mark_id')->whereNull('model_id')->get();
                foreach($koef_objects as $koef_o) {
                    $koef_o->koef = round($k/$count, 1);
                    $koef_o->is_calculate = 1;
                    $koef_o->save();
                }
                //\DB::table('car_koefs')->where('category_id', $category_id)->whereNull('mark_id')->whereNull('model_id')->update(['koef' => round($k/$count, 1)]);
            }
        }

        $cars = \App\Models\Car::whereNull('car_model')->get();
        foreach($cars as $car) {
            $car->car_koef = \App\Models\CarKoef::getKoef($car->car_model);
            $car->saveQuietly();
            echo $car->car_koef.'<br>';
        }





        // echo date('d.m.Y', strtotime('-1 day')).'<br>';
        // $car_ids = \App\Models\Route::whereBetween('date_format', [date("Y-m-d", strtotime('-3 days')), date("Y-m-d")])->pluck('car_id')->toArray();
        // //$routes = \App\Models\Route::whereIn('car_id', $car_ids)->where('date', date('d.m.Y', strtotime('-1 day')))/*->whereBetween('date_format', [date("Y-m-d", strtotime('01.11.2022')), date("Y-m-d", strtotime('06.11.2022'))])*/->orderBy('id', 'desc')->get();
        // $routes = \App\Models\Route::whereIn('car_id', $car_ids)->whereBetween('date_format', [date("Y-m-d", strtotime('-3 days')), date("Y-m-d")])->orderBy('id', 'desc')->get();
        // $koefs = array();
        // $cars_koef = array();
        // foreach ($routes as $route) {
        //     if($route->date == '07.11.2022' || $route->date == '08.11.2022')
        //         continue;
        //     if(!isset($koefs[$route->car->car_model])) {
        //         $koefs[$route->car->car_model] = array('old_koef' => 0, 'sum_k' => 0, 'count_routes' => 0);
        //     }
        //     // if($koefs[$route->car->car_model]['count_routes'] > 1)
        //     //     continue;
        //     $orders = $route->orders;
        //     echo $route->car_id.' : '.$route->date.'<br>';

        //     if(count($orders)) {
                
        //         $sum = 0;
        //         $count = 0;
        //         foreach($orders as $order) {
        //             if($order->fakticeskii_563 && $route->koef*5 > $order->fakticeskii_563 && $order->fakticeskii_563 > 0.4) {
        //                 echo '+'.$order->fakticeskii_563;
        //                 $sum+= $order->fakticeskii_563;
        //                 $count++;
        //             }
                    
        //         };
        //         if($count) {
        //             echo '<br>count - '.$count.'<br>';
        //             $koefs[$route->car->car_model] = array(
        //                 'old_koef' => $koefs[$route->car->car_model]['old_koef'] + $route->koef,
        //                 'sum_k' => $koefs[$route->car->car_model]['sum_k'] + round($sum/$count, 1),
        //                 'count_routes' => $koefs[$route->car->car_model]['count_routes'] + 1
        //             );
        //             $cars_koef[] = $route->car_id;
        //         }
        //         if(!$route->koef && !$route->blocked_koef && $count) {
        //             //$route->koef = round($sum/$count, 1);
        //             //$route->blocked_koef = 1;
        //             //$route->saveQuietly();
        //         }
                
        //     }
        //     echo 'ROUTE KOEF '.$route->koef.'<br>';
        // }
        // //dd($koefs);
        // $cars = \App\Models\Car::whereIn('id', $cars_koef)->get();
        // $marks = array();
        // $categories = array();
        // //print_r($koefs);
        // $is_calculate = array();
        // foreach($koefs as $k=>$koef) {
        //     if($koef['sum_k'] == 0)
        //         unset($koefs[$k]);
        // }
        // echo '<pre>';
        // print_r($koefs);
        // echo '</pre>';
        // foreach($cars as $car) {
        //     if($car->car_model && $koefs[$car->car_model]['count_routes']) {
        //         $koef_model = \App\Models\CarKoef::where('model_id', $car->car_model)->first();
        //         if(!in_array($car->car_model, $is_calculate)) {
        //             $is_calculate[] = $car->car_model;
        //             echo 'KOEF: '.$koefs[$car->car_model]['old_koef'].' * '.round($koefs[$car->car_model]['sum_k']/$koefs[$car->car_model]['count_routes'], 2).'<br>';
        //             $koef_model->koef = round($koefs[$car->car_model]['old_koef']/$koefs[$car->car_model]['count_routes'], 2) * round($koefs[$car->car_model]['sum_k']/$koefs[$car->car_model]['count_routes'], 2);
        //             $koef_model->is_calculate = 1;
        //             echo 'KOEF11: '.$koef_model->model_id.' - '.$koef_model->koef.'<br>';
        //             $koef_model->save();
        //         }
                
        //         $car->car_koef = $koef_model->koef;
        //         $car->saveQuietly();
        //         $data[$car->car_model] = array(
        //             'mark_id' => $koef_model->mark_id,
        //             'category_id' => $koef_model->category_id
        //         );
                
        //     }
            
        // }

        // foreach($data as $k => $item) {
        //     $mark_id = $item['mark_id'];
        //     $category_id = $item['category_id'];
        //     $koefs_model = \App\Models\CarKoef::where('category_id', $category_id)->where('mark_id', $mark_id)->whereNotNull('model_id')->where('is_calculate', 1)->get();
        //     $k = 0;
        //     $count = 0;
        //     echo 'Категория '.$category_id.' Марка '.$mark_id.' Моделей '.count($koefs_model).'<br>';
        //     foreach($koefs_model as $koef_model) {
        //         if($koef_model->koef) {
        //             echo $koef_model->model_id.': '.$koef_model->koef.'<br>';
        //             $k+= $koef_model->koef;
        //             $count++;
        //         }
        //     }
        //     echo 'K '.$k.'<br>';
        //     if($count) {
        //         $koef_objects = \App\Models\CarKoef::where('category_id', $category_id)->where('mark_id', $mark_id)->whereNull('model_id')->get();
        //         foreach($koef_objects as $koef_o) {
        //             $koef_o->koef = round($k/$count, 1);
        //             $koef_o->is_calculate = 1;
        //             $koef_o->save();
        //         }
        //         //\DB::table('car_koefs')->where('category_id', $category_id)->where('mark_id', $mark_id)->whereNull('model_id')->update(['koef' => round($k/$count, 1)]);
        //     }


        //     $koefs_mark = \App\Models\CarKoef::where([
        //         ['category_id', $category_id],
        //         ['mark_id', '!=', null],
        //         ['model_id', null],
        //         ['is_calculate', '=', 1],
        //     ])->get();
        //     $k = 0;
        //     $count = 0;
        //     foreach($koefs_mark as $koef_mark) {
        //         if($koef_mark->koef) {
        //             $k+= $koef_mark->koef;
        //             $count++;
        //         }
        //     }
        //     echo 'Категория '.$category_id.' '.$k.' '.$count.'<br>';
        //     if($count) {
        //         $koef_objects = \App\Models\CarKoef::where('category_id', $category_id)->whereNull('mark_id')->whereNull('model_id')->get();
        //         foreach($koef_objects as $koef_o) {
        //             $koef_o->koef = round($k/$count, 1);
        //             $koef_o->is_calculate = 1;
        //             $koef_o->save();
        //         }
        //         //\DB::table('car_koefs')->where('category_id', $category_id)->whereNull('mark_id')->whereNull('model_id')->update(['koef' => round($k/$count, 1)]);
        //     }
        // }

        // $cars = \App\Models\Car::whereNull('car_model')->get();
        // foreach($cars as $car) {
        //     $car->car_koef = \App\Models\CarKoef::getKoef($car->car_model);
        //     $car->saveQuietly();
        //     echo $car->car_koef.'<br>';
        // }

    }
}
