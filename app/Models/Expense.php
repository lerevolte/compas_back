<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Auth;

class Expense extends Model
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
        
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('expenses', array(['id' => $this->id, $field => $new_value]), false);
    }

}
