<?php

namespace App\Models;

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
            $user = Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;

       });
       static::updated(function($model)
       {
            if(!$model->remnants->count() && $model->quantity) {
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
        // sync([]) при удалении убран: записи удаляются мягко, связи сохраняются,
        // чтобы восстановление из корзины возвращало запись вместе со связями.
    }

    public function remnants()
    {
        return $this->hasMany(Remnant::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

}
