<?php

namespace Modules\Products\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Product extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];

    public static function boot()
    {
       parent::boot();
       static::creating(function($model)
       {
            info('model');
            info($model->id);
            $user = Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;

       });
       static::updated(function($model)
       {
            info('remnants');
            info($model->remnants->count());
            info($model->quantity);
            if(!$model->remnants->count() && $model->quantity) {
                info('remnants1');
                $remnants = array();
                for ($i=0; $i < $model->quantity; $i++) { 
                    $remnants[] = array(
                        'name' => $model->name,
                        'price' => $model->price,
                        'product_id' => $model->id
                    );
                }
                $res = Remnant::insert($remnants);
            }
       });
    }

    public function remnants()
    {
        return $this->hasMany(Remnant::class);
    }

}
