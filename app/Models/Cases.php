<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Auth;

class Cases extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    protected $table = 'cases';

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

       });
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('cases', array(['id' => $this->id, $field => $new_value]), false);
    }

}
