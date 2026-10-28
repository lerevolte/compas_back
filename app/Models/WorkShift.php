<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator, App\Traits\HasRelationFields;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\WarehouseLogisticService;
use Auth;

class WorkShift extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes, HasRelationFields;

    protected $table = 'work_shifts';

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
            foreach (WarehouseLogisticService::SHIFT_DEFAULTS as $field => $value) {
                if (!$model->{$field}) {
                    $model->{$field} = $value;
                }
            }
        });

        static::saving(function ($model) {
            $name = trim((string) $model->name);
            if ($name === '' || preg_match('/^Рабочая смена #\d+$/u', $name) || ($model->isDirty(['shift_date', 'employee_id']) && $name === WarehouseLogisticService::shiftName((object) $model->getOriginal()))) {
                $model->name = WarehouseLogisticService::shiftName($model);
            }
        });

        static::saved(function ($model) {
            if (!$model->wasRecentlyCreated && $model->wasChanged('shift_date')) {
                WarehouseLogisticService::syncTaskDates((int) $model->id, $model->shift_date);
            }
        });
    }
}
