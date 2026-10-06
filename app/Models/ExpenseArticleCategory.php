<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class ExpenseArticleCategory extends Model
{
    use NodeTrait, FieldValue, ModelActions, SoftDeletes;

    protected $table = 'expense_article_categories';

    protected $guarded = ['id'];

    protected $hidden = [
        'created_at',
        'updated_at',
        '_lft',
        '_rgt',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = \Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
        });
    }
}
