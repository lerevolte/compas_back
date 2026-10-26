<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator, App\Traits\HasRelationFields;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Transfer extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes, HasRelationFields;

    protected $table = 'transfers';

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
            if (empty($model->{\App\Services\TransferService::DATE_FIELD})) {
                $model->{\App\Services\TransferService::DATE_FIELD} = date('Y-m-d');
            }
        });

        static::saving(function ($model) {
            foreach (['shipment_company_id', \App\Services\StorehouseService::WRITE_OFF, \App\Services\StorehouseService::RECEIPT] as $col) {
                if (is_array($model->{$col})) {
                    $model->{$col} = array_values(array_filter($model->{$col}, 'is_numeric'))[0] ?? null;
                }
            }
        });
        static::saved(function ($model) {
            if ($model->wasRecentlyCreated || array_key_exists('products', $model->getChanges())) {
                \App\Services\TransferService::recalcAround(\App\Services\TransferService::DOC, (int) $model->id);
            }
        });

        static::deleted(function ($model) {
            \App\Services\TransferService::recalcAround(\App\Services\TransferService::DOC, (int) $model->id);
        });

        static::restored(function ($model) {
            \App\Services\TransferService::recalcAround(\App\Services\TransferService::DOC, (int) $model->id);
        });
    }

    public function setProducts(array $products, $sum = null)
    {
        $this->products = json_encode($products);
        $totalWeight = 0;
        $totalVolume = 0;
        foreach ($products as $product) {
            $count = isset($product['count']) ? (float) $product['count'] : 0;
            $totalWeight += $count * (isset($product['weight']) ? (float) $product['weight'] : 0);
            $totalVolume += $count * (isset($product['volume']) ? (float) $product['volume'] : 0);
        }
        $this->weight = $totalWeight;
        $this->volume = $totalVolume;

        $objects = History::saveForObject(
            $this->getTable(),
            [[
                'id' => $this->id,
                'products' => $this->products,
                'weight' => $this->weight,
                'volume' => $this->volume,
            ]],
            true,
            [],
            [],
            true
        );
        $this->save();
        \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $this->getData($objects['changed_fields']));
    }

    public function getHtmlProducts()
    {
        $html = '';
        $products = $this->products ? json_decode($this->products, true) : [];
        foreach ((is_array($products) ? $products : []) as $product) {
            if (!is_array($product)) {
                continue;
            }
            $html .= (is_array($product['name'] ?? null) ? $product['name'][0] : ($product['name'] ?? '')) . ' <b>' . ($product['count'] ?? 0) . ' шт.</b><br>';
        }

        return $html;
    }
}
