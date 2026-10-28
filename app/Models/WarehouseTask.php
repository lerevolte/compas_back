<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator, App\Traits\HasRelationFields;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\WarehouseLogisticService;
use Auth;

class WarehouseTask extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes, HasRelationFields;

    protected $table = 'warehouse_tasks';

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
            if (!$model->status) {
                $model->status = WarehouseLogisticService::defaultTaskStatus();
            }
        });

        static::saving(function ($model) {
            WarehouseLogisticService::normalizeTask($model);
        });
    }
}
