<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class CashIncome extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $table = 'cash_incomes';

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
        });

        static::saving(function ($model) {
            \App\Services\CashDocumentService::fillDate($model);
        });

        foreach (['saved', 'deleted', 'restored'] as $event) {
            static::{$event}(function ($model) {
                \App\Services\CashDocumentService::syncOperation($model->getTable(), (int) $model->id);
            });
        }
    }
}
