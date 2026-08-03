<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait, FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
        //'sort',
        '_lft',
        '_rgt',
    ];

    public static function boot()
    {
       parent::boot();
       static::creating(function($model)
       {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;

       });
        // sync([]) при удалении убран: записи удаляются мягко, связи сохраняются,
        // чтобы восстановление из корзины возвращало запись вместе со связями.
        // У неудалённых записей удалённые скрыты SoftDeletes-scope'ом отношений.
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }


}
