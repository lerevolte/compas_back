<?php

namespace Modules\Journal\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Auth;
use Kalnoy\Nestedset\NodeTrait;

class EntityRelation extends Model
{
    use FieldValue, ModelActions, NodeTrait;


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
    }

}
