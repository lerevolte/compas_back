<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Remnant extends Model
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
            if(!$model->name)
                $model->name = class_exists(\Modules\Bitrix24\Services\B24ProductSync::class)
                    ? \Modules\Bitrix24\Services\B24ProductSync::nameText($model->product->name)
                    : $model->product->name;
        });

        self::created(function($model){
            $product = $model->product;
            $product->quantity = $product->remnants->count();
            $product->save();
        });

        self::updating(function($model){
            // ... code here
        });

        self::updated(function($model){
            $product = $model->product;
            $product->quantity = $product->remnants->count();
            $product->save();
        });

        self::deleting(function($model){
            $product = $model->product;
            $product->quantity = $product->remnants->count();
            $product->save();
        });

        self::deleted(function($model){
            // ... code here
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
