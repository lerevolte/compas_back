<?php

namespace Modules\Instructions\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;


class Category extends Model
{

    use NodeTrait, FieldValue, ModelActions, SoftDeletes;

    protected $table = 'instruction_groups';

    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
        'code',
        //'sort',
        '_lft',
        '_rgt',
        'parent_id'
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
    }

    public function instructions()
    {
        return $this->hasMany(Instruction::class, 'category_id');
    }

}
