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
        // sync([]) при удалении убран: записи удаляются мягко, связи сохраняются,
        // чтобы восстановление из корзины возвращало запись вместе со связями.
    }

    public function remnants()
    {
        return $this->hasMany(Remnant::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

}
