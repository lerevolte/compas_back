<?php

namespace Modules\Journal\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Logistic\Entities\Car;


class Mileage extends Model
{

    use FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];
    protected $table = 'mileages';
    
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

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
    
}
