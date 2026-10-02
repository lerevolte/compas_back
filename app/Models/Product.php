<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Product extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];

    public static function boot()
    {
       parent::boot();
       static::creating(function($model)
       {
            $user = Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;

       });
       static::saving(function($model)
       {
            $model->applyVolumeFromDimensions();
       });
       static::saved(function($model)
       {
            if (\App\Services\SiteProductSync::$muted || !\App\Services\SiteProductSync::ready()) {
                return;
            }
            $changed = array_intersect(array_keys($model->getChanges()), \App\Services\SiteProductSync::TRIGGER_FIELDS);
            if (!count($changed) || trim((string) $model->article) === '') {
                return;
            }
            try {
                \App\Jobs\PushProductToSite::dispatch((string) tenant('id'), (int) $model->id);
            } catch (\Throwable $e) {
                \Log::channel('site_sync')->warning('site-sync: не удалось поставить товар в очередь', ['product_id' => $model->id, 'error' => $e->getMessage()]);
            }
       });
       static::updated(function($model)
       {
            if(!$model->remnants->count() && $model->quantity) {
                $remnants = array();
                for ($i=0; $i < $model->quantity; $i++) {
                    $remnants[] = array(
                        'name' => \Modules\Bitrix24\Services\B24ProductSync::nameText($model->name),
                        'price' => $model->price,
                        'product_id' => $model->id
                    );
                }
                $res = Remnant::insert($remnants);
            }
       });
       static::saved(function($model)
       {
            if (!class_exists(\Modules\Bitrix24\Services\B24ProductSync::class)
                || \Modules\Bitrix24\Services\B24ProductSync::$muted
                || \Modules\Bitrix24\Services\B24EntitySync::$muted) {
                return;
            }
            $changed = array_intersect(
                array_keys($model->getChanges()),
                \Modules\Bitrix24\Services\B24ProductSync::PUSH_PRODUCT_FIELDS
            );
            $isCreate = !$model->id_b24;
            if ($isCreate && !Auth::user()) {
                return;
            }
            if (!$isCreate && !count($changed)) {
                return;
            }
            try {
                \Modules\Bitrix24\Services\B24ProductSync::make()?->pushProduct($model, $changed);
            } catch (\Throwable $e) {
                \Log::channel('bitrix24')->warning('product push failed', ['product_id' => $model->id, 'error' => $e->getMessage()]);
            }
       });
       static::saved(function($model)
       {
            try {
                \App\Services\ProductKitService::onSaved($model);
            } catch (\Throwable $e) {
                \Log::warning('product kit mirror failed', ['product_id' => $model->id, 'error' => $e->getMessage()]);
            }
       });
    }

    public function remnants()
    {
        return $this->hasMany(Remnant::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }


    public static function unitValue($current, $fallback)
    {
        $value = is_string($current) ? str_replace(',', '.', trim($current)) : $current;
        if ($value !== null && $value !== '' && is_numeric($value) && (float) $value > 0) {
            return (float) $value == (int) $value ? (int) $value : (float) $value;
        }

        return $fallback;
    }

    public function applyVolumeFromDimensions(): void
    {
        foreach (['length', 'width', 'height', 'volume'] as $column) {
            if (!array_key_exists($column, $this->getAttributes()) && !\Schema::hasColumn($this->getTable(), $column)) {
                return;
            }
        }
        $dims = [];
        foreach (['length', 'width', 'height'] as $column) {
            $raw = $this->getAttribute($column);
            $raw = is_string($raw) ? str_replace(',', '.', trim($raw)) : $raw;
            if ($raw === null || $raw === '' || !is_numeric($raw) || (float) $raw <= 0) {
                return;
            }
            $dims[] = (float) $raw;
        }
        $liters = round($dims[0] * $dims[1] * $dims[2] / 1000, 3);
        $this->volume = rtrim(rtrim(number_format($liters, 3, '.', ''), '0'), '.');
    }
}
