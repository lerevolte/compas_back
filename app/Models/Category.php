<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait, FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
        //'sort',
        '_lft',
        '_rgt',
    ];

    public static function boot()
    {
       parent::boot();
       static::creating(function($model)
       {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;

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
                \Modules\Bitrix24\Services\B24ProductSync::PUSH_CATEGORY_FIELDS
            );
            $isCreate = !$model->id_b24;
            if ($isCreate && !\Auth::user()) {
                return;
            }
            if (!$isCreate && !count($changed)) {
                return;
            }
            try {
                \Modules\Bitrix24\Services\B24ProductSync::make()?->pushCategory($model, $changed);
            } catch (\Throwable $e) {
                \Log::channel('bitrix24')->warning('category push failed', ['category_id' => $model->id, 'error' => $e->getMessage()]);
            }
       });
        // sync([]) при удалении убран: записи удаляются мягко, связи сохраняются,
        // чтобы восстановление из корзины возвращало запись вместе со связями.
        // У неудалённых записей удалённые скрыты SoftDeletes-scope'ом отношений.
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }


}
