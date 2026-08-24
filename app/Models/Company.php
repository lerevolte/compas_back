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

        static::saved(function($model)
        {
            if (!isset($model->getChanges()['name']) || !$model->b24_id) {
                return;
            }
            if (!class_exists(\Modules\Bitrix24\Services\B24EntitySync::class)
                || \Modules\Bitrix24\Services\B24EntitySync::$muted) {
                return;
            }
            try {
                \Modules\Bitrix24\Services\B24EntitySync::make()?->pushCompany($model, ['name']);
            } catch (\Throwable $e) {
                \Log::channel('bitrix24')->warning('company push failed', ['company_id' => $model->id, 'error' => $e->getMessage()]);
            }
        });

        static::updating(function($model)
        {   
            // if($model->getOriginal('car_id') != $model->car_id) {
            //     if($model->getOriginal('car_id')) {
            //         if(is_array($model->getOriginal('car_id')))
            //             $car_ids = $model->getOriginal('car_id');
            //         else
            //             $car_ids = json_decode($model->getOriginal('car_id'), true);

                    
            //         if(count($car_ids)) {
            //             \DB::table('cars')->whereIntegerInRaw('id',$car_ids)->update(['choosed_at' => null]);
            //             $new_car_ids = array();
            //             if($model->car_id) {
            //                 if(is_array($model->car_id))
            //                     $new_car_ids = $model->car_id;
            //                 else
            //                     $new_car_ids = json_decode($model->car_id, true);
            //             }
            //             foreach($car_ids as $car) {
            //                 if(!in_array($car, $new_car_ids))
            //                     \App\Models\History::saveForObject('cars', array(['id' => $car, 'company_id' => null]));
            //             }
            //         }
            //         $cars = Car::whereIntegerInRaw('id', $car_ids)->get();
            //         if(count($cars)) {
            //             foreach ($cars as $car) {
            //                 $car->saveRelations('company_id', null);
            //                 $car->company_id = null;
            //                 $car->saveQuietly();
            //             }
            //         }
            //     }

            //     if($model->car_id) {
            //         if(is_array($model->car_id))
            //             $car_ids = $model->car_id;
            //         else
            //             $car_ids = json_decode($model->car_id, true);

            //         if(count($car_ids)) {
            //             $old_car_ids = array();
            //             if($model->getOriginal('car_id')) {
            //                 if(is_array($model->getOriginal('car_id')))
            //                     $old_car_ids = $model->getOriginal('car_id');
            //                 else
            //                     $old_car_ids = json_decode($model->getOriginal('car_id'), true);
            //             }
            //             foreach($car_ids as $car) {
            //                 if(!in_array($car, $old_car_ids))
            //                     \App\Models\History::saveForObject('cars', array(['id' => $car, 'company_id' => $model->id]));
            //             }
            //         }
            //         if(is_array($car_ids)) {
            //             $cars = Car::whereIntegerInRaw('id', $car_ids)->get();
            //             if(count($cars)) {
            //                 foreach ($cars as $car) {
            //                     $car->saveRelations('company_id', $model->id);
            //                     $car->company_id = $model->id;
            //                     $car->saveQuietly();
            //                 }
            //             }
            //         }
            //     }
            // }
        });
    }

    public function cars()
    {
        return $this->hasMany(Car::class, 'company_id')->orderBy('choosed_at');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'company_id');
    }

    public function fines_gibdd()
    {
        return $this->hasMany(GibddFine::class, 'company_id');
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'company_contact');
    }

    public function bank_requisites()
    {
        return $this->hasMany(BankRequisite::class, 'company_id')->orderBy('id');
    }

    public function defaultBankRequisite(): ?BankRequisite
    {
        if (!\Schema::hasTable('bank_requisites')) {
            return null;
        }

        return $this->bank_requisites()->where('is_default', '1')->first()
            ?: $this->bank_requisites()->first();
    }

    public function deals()
    {
        return $this->belongsToMany(Deal::class, 'company_deal');
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('companies', array(['id' => $this->id, $field => $new_value]), false);
    }

}
