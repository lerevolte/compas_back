<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Production extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $table = 'productions';

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
        });

        foreach (['deleted', 'restored'] as $event) {
            static::{$event}(function ($model) {
                \App\Services\ProductionService::recalcForDocument((int) $model->id);
            });
        }
    }

    public function setProducts(array $products, $sum = null)
    {
        foreach ($products as $i => $product) {
            if (is_array($product)) {
                unset($products[$i]['produced']);
            }
        }
        $this->writeComposition('products', array_values($products));
    }

    public function setMaterials(array $materials)
    {
        $this->writeComposition(\App\Services\ProductionService::MATERIALS_FIELD, $materials);
    }

    private function writeComposition(string $field, array $lines): void
    {
        $this->{$field} = json_encode($lines, JSON_UNESCAPED_UNICODE);
        $objects = History::saveForObject(
            $this->getTable(),
            [[
                'id' => $this->id,
                $field => $this->{$field},
            ]],
            true,
            [],
            [],
            true
        );
        $this->saveQuietly();
        try {
            \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $this->getData($objects['changed_fields'] ?? []));
        } catch (\Throwable $e) {
        }
    }

    public function getHtmlProducts()
    {
        return self::compositionHtml($this->products);
    }

    public function getHtmlMaterials()
    {
        return self::compositionHtml($this->{\App\Services\ProductionService::MATERIALS_FIELD});
    }

    public static function compositionHtml($raw): string
    {
        $html = '';
        foreach (\App\Services\ProductionService::decode($raw) as $product) {
            $count = $product['count'] ?? 0;
            $count = is_numeric($count) ? rtrim(rtrim(number_format((float) $count, 3, '.', ''), '0'), '.') : $count;
            $html .= (is_array($product['name'] ?? null) ? $product['name'][0] : ($product['name'] ?? '')) . ' <b>' . $count . '</b><br>';
        }

        return $html;
    }
}
