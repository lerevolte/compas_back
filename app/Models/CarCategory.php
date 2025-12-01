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


class CarCategory extends Model
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
        });
    }

    public function models()
    {
        return $this->hasMany(CarModel::class, 'category_id')->orderBy('choosed_at');
    }


    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('car_categories', array(['id' => $this->id, $field => $new_value]), false);
    }

}
