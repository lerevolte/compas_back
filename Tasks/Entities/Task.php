<?php

namespace Modules\Tasks\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Task extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static function boot()
    {
       parent::boot();
       static::creating(function($model)
       {
            $user = Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
       });
       // static::updating(function($model)
       // {
       //     $user = Auth::user();
       //     $model->updated_by = $user->id;
       // });
   }

    public function status() {
        return \DB::table('field_values')->where('id', $this->point_status)->first();
    }
    public function generateLink() {
        $random_link = substr(md5(microtime()),rand(0,26),12);
        if (self::where('link', $random_link)->count() > 0) self::generateLink();
        $this->link = $random_link;
    }

}
