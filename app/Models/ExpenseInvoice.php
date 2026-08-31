<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class ExpenseInvoice extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $table = 'expense_invoices';

    protected $guarded = ['id'];

    public const REGENERATE_FIELDS = ['name', 'company_id', 'shipment_company_id', 'sum', 'products', 'created_at'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
        });

        static::saving(function ($model) {
            if (is_array($model->shipment_company_id)) {
                $model->shipment_company_id = array_values(array_filter($model->shipment_company_id, 'is_numeric'))[0] ?? null;
            }
        });

        static::saved(function ($model) {
            if ($model->wasRecentlyCreated || count(array_intersect(array_keys($model->getChanges()), self::REGENERATE_FIELDS))) {
                $model->regeneratePdf();
            }
            if ($model->wasRecentlyCreated || array_key_exists('products', $model->getChanges())) {
                \App\Services\ShipmentService::recalcForDocument((int) $model->id);
            }
        });

        static::deleted(function ($model) {
            \App\Services\ShipmentService::recalcForDocument((int) $model->id);
        });

        static::restored(function ($model) {
            \App\Services\ShipmentService::recalcForDocument((int) $model->id);
        });
    }

    public function regeneratePdf(): bool
    {
        return \App\Services\SaleDocumentService::regenerate('expense_invoices', (int) $this->id);
    }

    public function setProducts(array $products, $sum = null)
    {
        $this->products = json_encode($products);
        $total = 0.0;
        foreach ($products as $product) {
            $count = isset($product['count']) ? (float) $product['count'] : 0;
            $price = isset($product['price']) ? (float) $product['price'] : 0;
            $total += $count * $price;
        }
        if ($sum !== null && (float) $sum > 0) {
            $this->sum = rtrim(rtrim(number_format((float) $sum, 2, '.', ''), '0'), '.');
        } elseif ($total > 0) {
            $this->sum = rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.');
        }
        $this->saveQuietly();
        $this->regeneratePdf();
        \App\Services\ShipmentService::recalcForDocument((int) $this->id);
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
