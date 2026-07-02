<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Journal\Entities\Record;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\CarModel;
use App\Models\CarMark;


class Car extends Model
{

    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $guarded = ['id'];
    
    public static function boot()
    {
        parent::boot();
        static::creating(function($model)
        {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
        });

        static::saved(function($model)
        {
            if ($model->wasChanged('price_per_km')) {
                foreach ($model->routes()->cursor() as $route) {
                    $route->mileage_cost = $route->computeMileageCost();
                    $route->saveQuietly();
                }
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }



    // public function carrier()
    // {
    //     return $this->belongsTo(\App\Models\Carrier::class);
    // }

    public function fines()
    {
        return $this->hasMany(GibddFine::class, 'car_id');
    }
    
    public function fines_gibdd()
    {
        return $this->hasMany(GibddFine::class, 'car_id');
    }

    public function autodor()
    {
        return $this->hasMany(Autodor::class, 'car_id');
    }
    
    public function journal_records()
    {
        return $this->hasMany(Record::class, 'car_id');
    }

    public function mileages()
    {
        return $this->hasMany(Mileage::class, 'car_id');
    }

    public function routes()
    {
        return $this->hasMany(Route::class, 'car_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'car_employee');
    }

    public function model()
    {
        return $this->belongsTo(CarModel::class, 'osago_model');
    }

    public function mark()
    {
        return $this->belongsTo(CarMark::class, 'osago_mark');
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('cars', array(['id' => $this->id, $field => $new_value]), false);
    }

}
