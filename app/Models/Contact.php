<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FieldValue, App\Traits\ModelActions;

class Contact extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    protected $table = 'contacts';
    protected $guarded = ['id'];

    public const B24_PUSH_FIELDS = ['name', 'emails', 'phones', 'contact_type'];

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
            foreach (['emails', 'phones', 'company_id', 'contact_type'] as $col) {
                if (is_array($model->{$col})) {
                    $model->{$col} = json_encode(array_values(array_filter(
                        $model->{$col},
                        fn ($v) => $v !== null && $v !== ''
                    )), JSON_UNESCAPED_UNICODE);
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
                \Modules\Bitrix24\Services\B24EntitySync::make()?->pushContact($model, $changed);
            } catch (\Throwable $e) {
                \Log::channel('bitrix24')->warning('contact push failed', ['contact_id' => $model->id, 'error' => $e->getMessage()]);
            }
        });
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_contact');
    }

    public function deals()
    {
        return $this->belongsToMany(Deal::class, 'contact_deal');
    }

    public function sync_history($field, $new_value)
    {
        \App\Models\History::saveForObject('contacts', [['id' => $this->id, $field => $new_value]], false);
    }
}
