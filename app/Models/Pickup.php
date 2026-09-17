<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator, App\Traits\HasRelationFields;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Pickup extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes, HasRelationFields;

    protected $table = 'pickups';

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
            if (empty($model->delivery_date)) {
                $model->delivery_date = date('Y-m-d');
            }
            if (empty($model->number) && \Schema::hasColumn($model->getTable(), 'number')) {
                $model->number = \App\Services\DocumentNumber::next();
            }
        });

        static::saving(function ($model) {
            if (\Schema::hasColumn($model->getTable(), 'deal_id')) {
                $model->deal_id = \App\Services\ShipmentService::normalizeDealId($model->deal_id);
            }
            foreach (['contact_id', 'car_requirements', 'employee_requirements'] as $col) {
                if (is_array($model->{$col})) {
                    $model->{$col} = json_encode(array_values($model->{$col}));
                }
            }
            if (is_array($model->shipment_company_id)) {
                $model->shipment_company_id = array_values(array_filter($model->shipment_company_id, 'is_numeric'))[0] ?? null;
            }
            if (is_array($model->company_id)) {
                $model->company_id = array_values(array_filter($model->company_id, 'is_numeric'))[0] ?? null;
            }
        });

        static::saved(function ($model) {
            $changes = $model->getChanges();
            if (array_key_exists('deal_id', $changes) || ($model->wasRecentlyCreated && $model->deal_id)) {
                try {
                    \App\Services\ShipmentService::syncDealLink($model->getTable(), (int) $model->id, $model->deal_id, $model->wasRecentlyCreated ? null : $model->getOriginal('deal_id'));
                } catch (\Throwable $e) {
                }
            }
            if (array_key_exists(\App\Services\ShipmentService::ACTION_FIELD, $changes) && !$model->wasRecentlyCreated) {
                \App\Services\ShipmentService::forgetLoading($model->getTable(), (int) $model->id);
                try {
                    \App\Services\ShipmentService::recalcForSource($model->getTable(), (int) $model->id);
                } catch (\Throwable $e) {
                }
            }
            if (array_key_exists('products', $model->getChanges()) || ($model->wasRecentlyCreated && $model->products)) {
                try {
                    \App\Services\ShipmentService::recalcForSource('pickups', (int) $model->id);
                } catch (\Throwable $e) {
                }
                \App\Services\ProductPriceService::recalcFromChange($model->products, $model->wasRecentlyCreated ? null : $model->getOriginal('products'));
            }
        });
    }

    public function setProducts(array $products)
    {
        $this->products = json_encode($products);
        $totalWeight = 0;
        $totalVolume = 0;
        foreach ($products as $product) {
            $count = isset($product['count']) ? (float) $product['count'] : 0;
            $w = isset($product['weight']) ? (float) $product['weight'] : 0;
            $v = isset($product['volume']) ? (float) $product['volume'] : 0;
            $totalWeight += $count * $w;
            $totalVolume += $count * $v;
        }
        $this->weight = $totalWeight;
        $this->volume = $totalVolume;

        $objects = History::saveForObject(
            $this->getTable(),
            array(
                array(
                    'id' => $this->id,
                    'products' => $this->products,
                    'weight' => $this->weight,
                    'volume' => $this->volume,
                )
            )
        );
        $this->save();
        $data = $this->getData($objects['changed_fields']);
        \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);
    }

    public function getHtmlProducts()
    {
        $html = '';
        if ($this->products) {
            $products = json_decode($this->products, true);
            foreach ((is_array($products) ? $products : []) as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $html .= (is_array($product['name'] ?? null) ? $product['name'][0] : ($product['name'] ?? '')) . ' <b>' . ($product['count'] ?? 0) . ' шт.</b><br>';
            }
        }

        return $html;
    }
}
