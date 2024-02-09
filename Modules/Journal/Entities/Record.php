<?php

namespace Modules\Journal\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Logistic\Entities\Car;
use Modules\Journal\Entities\EntityRelation;


class Record extends Model
{

    use FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];
    protected $table = 'journal_records';
    
    public static function boot()
    {
        parent::boot();
        static::created(function($model)
        {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
            if($model->car_id) {
                $relation_car = new EntityRelation;
                $relation_car->name = 'Автопарк';
                $relation_car->entity_name = $model->car->name;
                $relation_car->entity = 'cars';
                $relation_car->entity_id = $model->car_id;
                $relation_car->user_id = $model->user_id;
                $relation_car->save();

                $relation = new EntityRelation;
                $relation->name = 'Ремонт и тех обслуживание';
                $relation->entity_name = $model->name;
                $relation->entity = 'journal_records';
                $relation->entity_id = $model->id;
                $relation->parent_id = $relation_car->id;
                $relation->user_id = $model->user_id;
                $relation->save();

                \Modules\Journal\Entities\EntityRelation::fixTree();
            }
        });
        static::updating(function($model)
        {
            if($model->car_id && !EntityRelation::where(['entity_id' => $model->car_id])->exists()) {
                $relation_car = new EntityRelation;
                $relation_car->name = 'Автопарк';
                $relation_car->entity_name = $model->car->name;
                $relation_car->entity = 'cars';
                $relation_car->entity_id = $model->car_id;
                $relation_car->user_id = $model->user_id;
                $relation_car->save();

                $relation = new EntityRelation;
                $relation->name = 'Ремонт и тех обслуживание';
                $relation->entity_name = $model->name;
                $relation->entity = 'journal_records';
                $relation->entity_id = $model->id;
                $relation->parent_id = $relation_car->id;
                $relation->user_id = $model->user_id;
                $relation->save();
            }
        });
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
    
}
