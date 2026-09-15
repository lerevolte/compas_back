<?php

namespace App\Traits;

use App\Services\RelationFieldsService;

trait HasRelationFields
{
    public static function bootHasRelationFields(): void
    {
        static::saving(function ($model) {
            foreach (RelationFieldsService::managedFieldsFor($model->getTable()) as $target => $field) {
                if (RelationFieldsService::isSingle($model->getTable(), $target)) {
                    $model->{$field} = RelationFieldsService::singleValue($model->{$field});
                } elseif (is_array($model->{$field})) {
                    $model->{$field} = json_encode(RelationFieldsService::ids($model->{$field}));
                }
            }
        });

        static::saved(function ($model) {
            $changes = $model->getChanges();
            foreach (RelationFieldsService::managedFieldsFor($model->getTable()) as $target => $field) {
                $created = $model->wasRecentlyCreated && count(RelationFieldsService::ids($model->{$field}));
                if (!$created && !array_key_exists($field, $changes)) {
                    continue;
                }
                try {
                    RelationFieldsService::apply(
                        $model->getTable(),
                        (int) $model->id,
                        $target,
                        $created ? null : $model->getOriginal($field),
                        $model->{$field}
                    );
                } catch (\Throwable $e) {
                    \Log::warning('relations: не удалось применить связь из поля', ['object' => $model->getTable() . '#' . $model->id, 'field' => $field, 'error' => $e->getMessage()]);
                }
            }
        });
    }
}
