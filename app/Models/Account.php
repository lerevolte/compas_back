<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelAdminPanel\Traits\Cropper;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InImage;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Salary\Entities\Salary;
use Modules\EmergencyFund\Entities\FundRecord;


class Account extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;


    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();
        static::updating(function($model)
        {
            if(!$model->color) {
                $data = array(
                    'sort' => 0,
                    'file' => null,
                    'is_hidden' => 1,
                    'field_id' => 2844,
                    'color' => self::generateColor(),
                    'value' => '',
                );
                $field_value_id = \DB::table('field_values')->insertGetId($data);
                $model->color_id = $field_value_id;
                $model->saveQuietly();

                \App\Models\Settings::clear_cache();
            }
        });
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'account_id')->orderBy('id', 'desc');
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class, 'account_id')->orderBy('id', 'desc');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'account_id')->orderBy('id', 'desc');
    }

    public function requisites()
    {
        return $this->hasMany(Requisite::class, 'account_id')->orderBy('id', 'desc');
    }

    public static function generateColor()
    {
        return '#'.str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }
}