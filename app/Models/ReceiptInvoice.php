<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator, App\Traits\HasRelationFields;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class ReceiptInvoice extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes, HasRelationFields;

    protected $table = 'receipt_invoices';

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
            \App\Services\OneC\OneCExportService::assignStatus($model);
        });

        static::created(function ($model) {
            \App\Services\OneC\OneCExportService::assignNumber($model);
        });

        static::saving(function ($model) {
            foreach (['contact_id', 'company_id'] as $col) {
                if (is_array($model->{$col})) {
                    $ids = array_values(array_filter($model->{$col}, 'is_numeric'));
                    $model->{$col} = $col === 'company_id' && !self::receiverPlural() ? ($ids[0] ?? null) : json_encode($ids);
                }
            }
        });

        static::saved(function ($model) {
            if (array_key_exists('products', $model->getChanges())) {
                self::recalcParentShipments((int) $model->id);
            }
            if ($model->wasRecentlyCreated || array_key_exists('products', $model->getChanges())) {
                \App\Services\ProductPriceService::recalcFromChange($model->products, $model->wasRecentlyCreated ? null : $model->getOriginal('products'), 'purchase_price');
            }
            if (\App\Services\OneC\OneCExportService::touched($model)) {
                \App\Services\OneC\OneCExportService::markChanged($model);
            }
        });

        static::deleted(function ($model) {
            self::recalcParentShipments((int) $model->id);
            \App\Services\ProductPriceService::recalcFromChange($model->products, null, 'purchase_price');
        });

        static::restored(function ($model) {
            self::recalcParentShipments((int) $model->id);
            \App\Services\ProductPriceService::recalcFromChange($model->products, null, 'purchase_price');
        });
    }

    private static array $receiverPlural = [];

    public static function receiverPlural(): bool
    {
        $key = (string) (function_exists('tenant') ? tenant('id') : '');
        if (!array_key_exists($key, self::$receiverPlural)) {
            $typeId = \DB::table('data_types')->where('slug', 'receipt_invoices')->value('id');
            self::$receiverPlural[$key] = (bool) \DB::table('data_rows')->where('data_type_id', $typeId)->where('field', 'company_id')->value('is_plural');
        }

        return self::$receiverPlural[$key];
    }

    public static function recalcParentShipments(int $id): void
    {
        try {
            foreach (\App\Services\ShipmentService::parentsOf('receipt_invoices', $id) as $parent) {
                if (\App\Services\ShipmentService::isSource($parent[0])) {
                    \App\Services\ShipmentService::recalcForSource($parent[0], (int) $parent[1]);
                } elseif ($parent[0] === \App\Services\ShipmentService::SUPPLIER) {
                    \App\Services\ShipmentService::recalcSupplierReceived((int) $parent[1]);
                }
            }
        } catch (\Throwable $e) {
        }
    }

    public function setProducts(array $products, $sum = null)
    {
        $this->products = json_encode($products);
        $total = 0.0;
        foreach ($products as $product) {
            if (is_array($product)) {
                $total += \App\Services\ShipmentService::lineTotal($product, \App\Services\ShipmentService::linePriceKey($this->getTable(), $product));
            }
        }
        if ($sum !== null && (float) $sum > 0) {
            $this->sum = rtrim(rtrim(number_format((float) $sum, 2, '.', ''), '0'), '.');
        } else {
            $this->sum = $total > 0 ? rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.') : null;
        }
        History::saveForObject(
            $this->getTable(),
            [[
                'id' => $this->id,
                'products' => $this->products,
                'sum' => $this->sum,
            ]],
            true,
            [],
            [],
            true
        );
        $this->saveQuietly();
        \App\Services\OneC\OneCExportService::markChanged($this);
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
