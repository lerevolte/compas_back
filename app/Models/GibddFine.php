<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;


class GibddFine extends Model
{

    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $guarded = ['id'];
    protected $table = 'fines_gibdd';
    
    public static function boot()
    {
        parent::boot();
        static::creating(function($model)
        {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
        });
        static::updating(function($model)
        {
            if($model->getOriginal('car_id') != $model->car_id) {
                if($model->getOriginal('car_id')) {
                    $car = Car::find($model->getOriginal('car_id'));
                    $car_fines = is_array($car->fine_id) ? $car->fine_id : json_decode($car->fine_id, true);
                    if(is_array($car_fines)) {
                        $k = array_search($model->id, $car_fines);
                        unset($car_fines[$k]);
                        $car->saveRelations('car_id', $car_fines);
                        $car->fine_id = json_encode($car_fines);
                        $car->saveQuietly();
                    }
                }

                if($model->car_id) {
                    $car = $model->car;
                    $car_fines = is_array($car->fine_id) ? $car->fine_id : json_decode($car->fine_id, true);
                    if(is_array($car_fines)) {
                        if(!in_array($model->id, $car_fines)) {
                            $car_fines[] = $model->id;
                            $car->saveRelations('fine_id', $car_fines);
                            $car->fine_id = json_encode($car_fines);
                            $car->saveQuietly();
                        }
                    } else {
                        $car->saveRelations('fine_id', [$model->id]);
                        $car->fine_id = json_encode([$model->id]);
                        $car->saveQuietly();
                    }
                }
            }
        });
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
    
}
