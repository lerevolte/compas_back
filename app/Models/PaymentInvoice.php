<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class PaymentInvoice extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $table = 'payment_invoices';

    protected $guarded = ['id'];

    public const REGENERATE_FIELDS = ['name', 'number', 'company_id', 'shipment_company_id', 'sum', 'products', 'bank_requisite_id', 'created_at'];

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
            if (is_array($model->bank_requisite_id)) {
                $model->bank_requisite_id = json_encode(array_values(array_filter($model->bank_requisite_id, 'is_numeric')));
            }
            if (is_array($model->shipment_company_id)) {
                $model->shipment_company_id = array_values(array_filter($model->shipment_company_id, 'is_numeric'))[0] ?? null;
            }
        });

        static::saved(function ($model) {
            if ($model->wasRecentlyCreated || count(array_intersect(array_keys($model->getChanges()), self::REGENERATE_FIELDS))) {
                $model->regeneratePdf();
            }
        });
    }

    public function regeneratePdf(): bool
    {
        return \App\Services\SaleDocumentService::regenerate('payment_invoices', (int) $this->id);
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
        $this->regeneratePdf();
    }
}
