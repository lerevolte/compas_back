<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SabyWaybill extends Model
{
    protected $table = 'saby_waybills';
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'error' => 'array',
    ];

    protected static function booted(): void
    {
        $sync = function ($model) {
            \App\Services\Saby\SabyOrderService::syncTaskColumn($model->task_id);
            $original = $model->getOriginal('task_id');
            if ($original && (int) $original !== (int) $model->task_id) {
                \App\Services\Saby\SabyOrderService::syncTaskColumn($original);
            }
        };
        static::saved($sync);
        static::deleted($sync);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
