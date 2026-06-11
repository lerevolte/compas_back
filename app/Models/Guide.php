<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Auth;

class Guide extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    protected $table = 'guides';
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
            $text = is_array($model->detail_text) ? json_encode($model->detail_text) : $model->detail_text;
            $words = count(preg_split('/\s+/', strip_tags($text)));
            $readingTime = ceil($words / 200);
            if($readingTime != $model->reading_time || !$model->slug) {
                $model->timestamps = false;
                $model->reading_time = $readingTime;
                if(!$model->slug) {
                    $name = $model->name;
                    if(is_array($name))
                        $name = $name['value'];
                    $model->slug = Str::slug($name);
                }
                $model->saveQuietly();
            }
            cache()->getMemcached()->delete('guide-fields');
       });
        // sync([]) при удалении убран: записи удаляются мягко, связи сохраняются,
        // чтобы восстановление из корзины возвращало запись вместе со связями.
    }

    public function guide_categories()
    {
        return $this->belongsToMany(GuideCategory::class, 'guide_category', 'guide_id', 'category_id');
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('guides', array(['id' => $this->id, $field => $new_value]), false);
    }

}
