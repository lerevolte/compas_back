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
use Modules\Salary\Entities\Salary;
use Modules\EmergencyFund\Entities\FundRecord;


class Employee extends Model
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
            // \DB::table('cars')->where('car_id', $model->id)->update(['car_id' => null]);
            // if($model->car_id) {
            //     $cars = json_decode($model->car_id, true);
            //     $cars = Car::whereIntegerInRaw('id', $cars)->get();
            //     foreach ($cars as $car) {
            //         $car->car_id = $model->id;
            //         $car->saveQuietly();
            //     }
            // }
            if($model->getOriginal('car_id') != $model->car_id) {
                if($model->getOriginal('car_id')) {
                    if(is_array($model->getOriginal('car_id')))
                        $car_ids = $model->getOriginal('car_id');
                    else
                        $car_ids = json_decode($model->getOriginal('car_id'), true);
                    $cars = Car::whereIntegerInRaw('id', $car_ids)->get();
                    if(count($cars)) {
                        foreach ($cars as $car) {
                            if(is_array($car->employee_id))
                                $car_employees = $car->employee_id;
                            else
                                $car_employees = json_decode($car->employee_id, true);
                            
                            if(is_array($car_employees)) {
                                $k = array_search($model->id, $car_employees);
                                unset($car_employees[$k]);
                                $car->saveRelations('employee_id', $car_employees);
                                $car->employee_id = json_encode($car_employees);
                                $car->saveQuietly();
                            }
                        }
                    }
                }

                if($model->car_id) {
                    if(is_array($model->car_id))
                        $car_ids = $model->car_id;
                    else
                        $car_ids = json_decode($model->car_id, true);
                    if(is_array($car_ids)) {
                        $cars = Car::whereIntegerInRaw('id', $car_ids)->get();
                        if(count($cars)) {
                            foreach ($cars as $car) {
                                $car_employees = array();
                                if($car->employee_id) {
                                    if(is_array($car->employee_id))
                                        $car_employees = $car->employee_id;
                                    else
                                        $car_employees = json_decode($car->employee_id, true);
                                }
                                if(!in_array($model->id, $car_employees)) {
                                    $car_employees[] = $model->id;
                                    $car->saveRelations('employee_id', $car_employees);
                                    $car->employee_id = json_encode($car_employees);
                                    $car->saveQuietly();
                                }
                            }
                        }
                    }
                }
            }

            if($model->getOriginal('company_id') != $model->company_id) {
                if($model->getOriginal('company_id')) {
                    $company = Company::find($model->getOriginal('company_id'));
                    if(is_array($company->employee_id))
                        $company_employees = $company->employee_id;
                    else
                        $company_employees = json_decode($company->employee_id, true);
                    if(is_array($company_employees)) {
                        $k = array_search($model->id, $company_employees);
                        unset($company_employees[$k]);
                        $company->saveRelations('employee_id', $company_employees);
                        $company->employee_id = json_encode($company_employees);
                        $company->saveQuietly();
                    }
                }

                if($model->company_id) {
                    $company = $model->company;
                    if(is_array($company->employee_id))
                        $company_employees = $company->employee_id;
                    else
                        $company_employees = json_decode($company->employee_id, true);
                    if(is_array($company_employees)) {
                        if(!in_array($model->id, $company_employees)) {
                            $company_employees[] = $model->id;
                            $company->saveRelations('employee_id', $company_employees);
                            $company->employee_id = json_encode($company_employees);
                            $company->saveQuietly();
                        }
                    } else {
                        $company->saveRelations('employee_id', [$model->id]);
                        $company->employee_id = json_encode([$model->id]);
                        $company->saveQuietly();
                    }
                }
            }
            if($model->getOriginal('related_user_id') != $model->related_user_id) {
                if($model->getOriginal('related_user_id')) {
                    
                    $user = User::find($model->getOriginal('related_user_id'));
                    $user->saveRelations('employee_id', null);
                    $user->employee_id = null;
                    $user->saveQuietly();
                    
                }

                if($model->related_user_id) {
                    
                    $user = User::find($model->related_user_id);
                    $user->saveRelations('employee_id', $model->id);
                    $user->employee_id = $model->id;
                    $user->saveQuietly();
                    
                }
            }
            // if($model->getOriginal('car_id') && $model->getOriginal('car_id') != $model->car_id && !$model->car_id) {
            //     $car_ids = json_decode($model->getOriginal('car_id'), true);
            //     info($car_ids);
            //     $cars = Car::whereIntegerInRaw('id', $car_ids)->get();
            //     if(count($cars)) {
            //         foreach ($cars as $car) {
            //             $car_employees = json_decode($car->employee_id, true);
            //             info('$car_employees');
            //             info($car_employees);
                        
            //             if(is_array($car_employees)) {
            //                 $k = array_search($model->id, $car_employees);
            //                 info('$k');
            //                 info($k);
            //                 unset($car_employees[$k]);
            //                 info($car_employees);
            //                 $car->employee_id = json_encode($car_employees);
            //                 $car->saveQuietly();
            //             }
            //         }
            //     }
            // } elseif($model->car_id) {
            //     //$car = $model->car;//Company::find($model->company_id);
            //     $car_ids = json_decode($model->car_id, true);
            //     if(is_array($car_ids)) {
            //         $cars = Car::whereIntegerInRaw('id', $car_ids)->get();
            //         if(count($cars)) {
            //             foreach ($cars as $car) {
            //                 $car_employees = array();
            //                 if($car->employee_id)
            //                     $car_employees = json_decode($car->employee_id, true);
            //                 if(!in_array($model->id, $car_employees)) {
            //                     $car_employees[] = $model->id;
            //                     $car->employee_id = json_encode($car_employees);
            //                     $car->saveQuietly();
            //                 }
            //             }
            //         }
            //     }
            // }
            info('UPDATING DRIVER');
            if($model->driver_license && !$model->carsmonitoring_id) {
                $res = \Modules\Gibdd\Entities\Module::addDriver([
                    'licensenum' => $model->driver_license,
                    'title' => $model->name
                ]);
                info($res);
                if(isset($res['id'])) {
                    $model->carsmonitoring_id = $res['id'];
                }
            }
        });
        static::deleting(function($model){ 
            $model->cars()->sync([]);
            return true; // let the delete go through
        });
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // public function cars()
    // {
    //     return $this->hasMany(Car::class, 'car_id')->orderBy('id', 'asc');
    // }
    public function cars()
    {
        return $this->belongsToMany(Car::class, 'car_employee');
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class, 'car_id')->orderBy('id', 'asc');
    }

    public function emergency_fund_records()
    {
        return $this->hasMany(FundRecord::class, 'car_id')->orderBy('id', 'asc');
    }
    


}
