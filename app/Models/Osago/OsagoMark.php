<?php

namespace App\Models\Osago;

use Illuminate\Database\Eloquent\Model;
use LaravelAdminPanel\Traits\Cropper;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InImage;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Car;


class OsagoMark extends Model
{
    use FieldValue, ModelActions, SoftDeletes;


    protected $guarded = ['id'];
    protected $table = 'osago_marks';

    public static function boot()
    {
        parent::boot();
        static::updating(function($model)
        {
        });
    }



    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('osago_marks', array(['id' => $this->id, $field => $new_value]), false);
    }
}