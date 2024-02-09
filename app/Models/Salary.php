<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salary extends Model
{

    use FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];
    protected $table = 'salaries';
    
    public static function boot()
    {
        parent::boot();
        static::creating(function($model)
        {

        });
        static::updating(function($model)
        {
            //if($model)
        });
    }
    
}
