<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class ProductReturn extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $table = 'product_returns';

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

        static::saved(function ($model) {
            if (array_key_exists('products', $model->getChanges())) {
                self::recalcParentShipments((int) $model->id);
            }
        });

        static::deleted(function ($model) {
            self::recalcParentShipments((int) $model->id);
        });

        static::restored(function ($model) {
            self::recalcParentShipments((int) $model->id);
        });
    }

    public static function recalcParentShipments(int $id): void
    {
        try {
            $parent = \App\Services\ShipmentService::parentOf('product_returns', $id);
            if ($parent && \App\Services\ShipmentService::isSource($parent[0])) {
                \App\Services\ShipmentService::recalcForSource($parent[0], (int) $parent[1]);
            }
        } catch (\Throwable $e) {
        }
    }

    public function setProducts(array $products)
    {
        $this->products = json_encode($products);
        $total = 0.0;
        foreach ($products as $product) {
            $count = isset($product['count']) ? (float) $product['count'] : 0;
            $price = isset($product['price']) ? (float) $product['price'] : 0;
            $total += $count * $price;
        }
        if ($total > 0 && !$this->sum) {
            $this->sum = rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.');
        }
        $this->saveQuietly();
        self::recalcParentShipments((int) $this->id);
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
