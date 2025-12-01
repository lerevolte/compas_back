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


class CarMark extends Model
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
            if(!tenant('id')) {
                $tenants = \App\Models\Tenant::get();  // Получаем всех тенантов

                foreach ($tenants as $tenant) {
                    tenancy()->initialize($tenant);
                    $tenantCarMark = \DB::table('car_marks')->where('id', $model->id)->first();

                    if ($tenantCarMark) {
                        // Если запись существует в базе тенанта, проверяем, изменились ли данные
                        if ($tenantCarMark != $model) {
                            // Если данные изменились, обновляем запись
                            \DB::table('car_marks')
                                ->where('id', $model->id)
                                ->update([
                                    'name' => $model->name,  // Пример обновления
                                    'created_at' => $model->updated_at,
                                    'updated_at' => $model->updated_at,
                                    'deleted_at' => $model->deleted_at,
                                    'name' => $model->name,
                                    'model_id' => $model->model_id,
                                    'osago_code' => $model->osago_code,
                                    'photo' => is_array($model->photo) ? json_encode($model->photo) : $model->photo
                                ]);
                        }
                    } else {
                        // Если записи нет в базе тенанта, добавляем новую
                        \DB::table('car_marks')->insert([
                            'id' => $model->id,
                            'name' => $model->name,  // Пример обновления
                            'created_at' => $model->updated_at,
                            'updated_at' => $model->updated_at,
                            'deleted_at' => $model->deleted_at,
                            'name' => $model->name,
                            'model_id' => $model->model_id,
                            'osago_code' => $model->osago_code,
                            'photo' => is_array($model->photo) ? json_encode($model->photo) : $model->photo
                        ]);
                    }
                }
            }
            
        });
    }

    public function car_models()
    {
        return $this->hasMany(CarModel::class, 'mark_id')->orderBy('choosed_at');
    }

    public function cars()
    {
        return $this->hasMany(Car::class, 'osago_mark')->orderBy('choosed_at');
    }


    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('car_marks', array(['id' => $this->id, $field => $new_value]), false);
    }

}
