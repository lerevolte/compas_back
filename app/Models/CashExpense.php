<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator, App\Traits\HasRelationFields;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class CashExpense extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes, HasRelationFields;

    protected $table = 'cash_expenses';

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
            static::{$event}(function ($model) use ($event) {
                \App\Services\CashDocumentService::syncOperation($model->getTable(), (int) $model->id);
                if ($event === 'saved' && ($model->wasRecentlyCreated || $model->wasChanged('sum'))) {
                    \App\Services\CashExpenseTotalService::recalcAround($model->getTable(), (int) $model->id);
                }
            });
        }
    }
}
