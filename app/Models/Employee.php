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
            
            // if($model->driver_license && !$model->carsmonitoring_id) {
            //     $res = \Modules\Gibdd\Entities\Module::addDriver([
            //         'licensenum' => $model->driver_license,
            //         'title' => $model->name,
            //         'id' => $model->id
            //     ]);
            //     info($res);
            //     if(isset($res['id'])) {
            //         $model->carsmonitoring_id = $res['id'];
            //     }
            // }
        });
        static::deleting(function($model){ 
            //$model->cars()->sync([]);
            if($model->carsmonitoring_id) {

                \Modules\Gibdd\Entities\Module::deleteDriver($model->carsmonitoring_id);
                $model->carsmonitoring_id = null;
                $model->saveQuietly();
            }
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

    public function fines()
    {
        return $this->hasMany(GibddFine::class, 'employee_id');
    }

    public function fines_gibdd()
    {
        return $this->hasMany(GibddFine::class, 'employee_id');
    }

    public function cars()
    {
        return $this->belongsToMany(Car::class, 'car_employee');
    }

    public function routes()
    {
        return $this->belongsToMany(Route::class, 'route_employee');
    }

    public function logistic_tasks()
    {
        return $this->belongsToMany(Task::class, 'logistic_task_employee', 'employee_id', 'logistic_task_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'employee_id');
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class, 'car_id')->orderBy('id', 'asc');
    }

    public function emergency_fund_records()
    {
        return $this->hasMany(FundRecord::class, 'car_id')->orderBy('id', 'asc');
    }
    
    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('employees', array(['id' => $this->id, $field => $new_value]), false);
    }

}
