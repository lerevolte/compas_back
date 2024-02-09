<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelAdminPanel\Traits\Cropper;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InImage;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;


class Company extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    //protected $fillable = ['name','carrier_id','phone'];
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

        static::updating(function($model)
        {   
            if($model->getOriginal('car_id') != $model->car_id) {
                if($model->getOriginal('car_id')) {
                    if(is_array($model->getOriginal('car_id')))
                        $car_ids = $model->getOriginal('car_id');
                    else
                        $car_ids = json_decode($model->getOriginal('car_id'), true);
                    $cars = Car::whereIntegerInRaw('id', $car_ids)->get();
                    if(count($cars)) {
                        foreach ($cars as $car) {
                            $car->saveRelations('company_id', null);
                            $car->company_id = null;
                            $car->saveQuietly();
                        }
                    }
                }

                if($model->car_id) {
                    if(is_array($car_ids))
                        $car_ids = $model->car_id;
                    else
                        $car_ids = json_decode($model->car_id, true);
                    if(is_array($car_ids)) {
                        $cars = Car::whereIntegerInRaw('id', $car_ids)->get();
                        if(count($cars)) {
                            foreach ($cars as $car) {
                                $car->saveRelations('company_id', $model->id);
                                $car->company_id = $model->id;
                                $car->saveQuietly();
                            }
                        }
                    }
                }
            }

            if($model->getOriginal('employee_id') != $model->employee_id) {
                if($model->getOriginal('employee_id')) {
                    if(is_array($model->getOriginal('employee_id')))
                        $employee_ids = $model->getOriginal('employee_id');
                    else
                        $employee_ids = json_decode($model->getOriginal('employee_id'), true);
                    $employees = Employee::whereIntegerInRaw('id', $employee_ids)->get();
                    if(count($employees)) {
                        foreach ($employees as $employee) {
                            $employee->saveRelations('company_id', null);
                            $employee->company_id = null;
                            $employee->saveQuietly();
                        }
                    }
                }

                if($model->employee_id) {
                    if(is_array($model->employee_id))
                        $employee_ids = $model->employee_id;
                    else
                        $employee_ids = json_decode($model->employee_id, true);
                    if(is_array($employee_ids)) {
                        $employees = Employee::whereIntegerInRaw('id', $employee_ids)->get();
                        if(count($employees)) {
                            foreach ($employees as $employee) {
                                $employee->saveRelations('company_id', $model->id);
                                $employee->company_id = $model->id;
                                $employee->saveQuietly();
                            }
                        }
                    }
                }
            }
        });
    }

    

}
