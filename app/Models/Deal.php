<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FieldValue, App\Traits\ModelActions;

class Deal extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    protected $table = 'deals';
    protected $guarded = ['id'];

    public const B24_PUSH_FIELDS = [
        'address', 'time', 'phone', 'delivery_price', 'comment',
        'pallets_count', 'delivery_date', 'contact',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = \Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
        });

        static::saving(function ($model) {
            foreach (['contact_id', 'company_id', 'car_requirements', 'employee_requirements'] as $col) {
                if (is_array($model->{$col})) {
                    $model->{$col} = json_encode(array_values($model->{$col}));
                }
            }
        });

        static::saved(function ($model) {
            $changed = array_intersect(array_keys($model->getChanges()), self::B24_PUSH_FIELDS);
            if (!count($changed) || !$model->b24_id) {
                return;
            }
            if (!class_exists(\Modules\Bitrix24\Services\B24EntitySync::class)) {
                return;
            }
            if (\Modules\Bitrix24\Services\B24EntitySync::$muted) {
                return;
            }
            try {
                \Modules\Bitrix24\Services\B24EntitySync::make()?->pushDeal($model, $changed);
            } catch (\Throwable $e) {
                \Log::channel('bitrix24')->warning('deal push failed', ['deal_id' => $model->id, 'error' => $e->getMessage()]);
            }
        });
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_deal');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_deal');
    }

    public function sync_history($field, $new_value)
    {
        \App\Models\History::saveForObject('deals', [['id' => $this->id, $field => $new_value]], false);
    }
}
