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


class OsagoCompany extends Model
{
    use FieldValue, ModelActions, SoftDeletes;


    protected $guarded = ['id'];
    protected $table = 'osago_companies';

    public static function boot()
    {
        parent::boot();
        static::updating(function($model)
        {
        });
    }


    public function osago_polises()
    {
        return $this->hasMany(OsagoPolis::class, 'company_id')->orderBy('choosed_at');
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('osago_companies', array(['id' => $this->id, $field => $new_value]), false);
    }
}