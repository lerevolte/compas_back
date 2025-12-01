<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    use NodeTrait, FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
        //'sort',
        '_lft',
        '_rgt',
        'parent_id',
        'article_id'
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
        static::created(function($model)
        {
            if(!$model->slug) {
                $name = json_decode($model->name, true);
                if(is_array($name))
                    $name = $name['value'];
                $model->slug = Str::slug($name);
                $model->saveQuietly();
            }
        });
        static::updated(function($model)
        {
            if(!$model->slug) {
                $name = $model->name;
                if(is_array($name))
                    $name = $name['value'];
                $model->slug = Str::slug($name);
                $model->saveQuietly();
            }
        });
        static::deleting(function($model){ 
            $model->articles()->sync([]);
            return true; // let the delete go through
        });
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_category', 'category_id', 'article_id');
    }


    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('blog_categories', array(['id' => $this->id, $field => $new_value]), false);
    }
}
