<?php

namespace App\Models\Osago;

use Illuminate\Database\Eloquent\Model;
use LaravelAdminPanel\Traits\Cropper;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InImage;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Car;


class OsagoPolis extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    //protected $fillable = ['name','carrier_id','phone'];
    protected $guarded = ['id'];
    protected $table = 'osago_polises';
    
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
            
            // \Modules\Osago\Entities\Module::getPolis($model);
            // \Modules\Osago\Entities\Module::findOffers($model);
        });
        static::updating(function($model)
        {
            try {
                if($model->date_start && $model->date_end) {
                    \Modules\Osago\Entities\Module::getPolis($model);
                }
            } catch (Exception $e) {
                return true;
            }
            
        });
        static::updated(function($model)
        {
            if($model->date_start && $model->date_end)
                \Modules\Osago\Entities\Module::findOffers($model);
        });
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function company()
    {
        return $this->belongsTo(OsagoCompany::class, 'company_id');
    }

   
    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('osago_polises', array(['id' => $this->id, $field => $new_value]), false);
    }

}
