<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;


class Autodor extends Model
{

    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $guarded = ['id'];
    protected $table = 'autodor';
    
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

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('autodor', array(['id' => $this->id, $field => $new_value]), false);
    }
    
}
