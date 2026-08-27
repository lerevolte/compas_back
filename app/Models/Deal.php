<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;

class Deal extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $table = 'deals';
    protected $guarded = ['id'];

    public const B24_PUSH_FIELDS = [
        'address', 'time', 'phone', 'delivery_price', 'comment',
        'pallets_count', 'delivery_date', 'contact', 'contact_id', 'company_id', 'bank_requisite_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = \Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
        });

        static::saving(function ($model) {
            foreach (['contact_id', 'company_id', 'car_requirements', 'employee_requirements', 'bank_requisite_id'] as $col) {
                if (is_array($model->{$col})) {
                    $model->{$col} = json_encode(array_values($model->{$col}));
                }
            }
            if (is_array($model->shipment_company_id)) {
                $model->shipment_company_id = array_values(array_filter($model->shipment_company_id, 'is_numeric'))[0] ?? null;
            }
        });

        static::saved(function ($model) {
            $changedKeys = array_keys($model->getChanges());
            if (count(array_intersect($changedKeys, ['products', 'sum']))) {
                try {
                    \App\Services\SaleDocumentService::syncFromDeal($model);
                } catch (\Throwable $e) {
                }
            } elseif (count(array_intersect($changedKeys, \App\Services\SaleDocumentService::DEAL_FIELDS))) {
                try {
                    \App\Services\SaleDocumentService::queueForDeal((int) $model->id);
                } catch (\Throwable $e) {
                }
            }

            $changed = array_intersect(array_keys($model->getChanges()), self::B24_PUSH_FIELDS);
            if (!count($changed) || !$model->b24_id) {
                return;
            }
            if (!class_exists(\Modules\Bitrix24\Services\B24EntitySync::class)) {
                return;
            }
            if (\Modules\Bitrix24\Services\B24EntitySync::$muted) {
                return;
            }
            try {
                \Modules\Bitrix24\Services\B24EntitySync::make()?->pushDeal($model, $changed);
            } catch (\Throwable $e) {
                \Log::channel('bitrix24')->warning('deal push failed', ['deal_id' => $model->id, 'error' => $e->getMessage()]);
            }
        });
    }

    public function setProducts(array $products)
    {
        $this->products = json_encode($products);
        $totalWeight = 0;
        $totalVolume = 0;
        $totalSum = 0.0;
        foreach ($products as $product) {
            $count = isset($product['count']) ? (float) $product['count'] : 0;
            $w = isset($product['weight']) ? (float) $product['weight'] : 0;
            $v = isset($product['volume']) ? (float) $product['volume'] : 0;
            $totalWeight += $count * $w;
            $totalVolume += $count * $v;
            $totalSum += $count * (isset($product['price']) ? (float) $product['price'] : 0);
        }
        $this->weight = $totalWeight;
        $this->volume = $totalVolume;
        if (\Schema::hasColumn('deals', 'sum')) {
            $this->sum = $totalSum > 0 ? rtrim(rtrim(number_format($totalSum, 2, '.', ''), '0'), '.') : null;
        }

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

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_deal');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_deal');
    }

    public function sync_history($field, $new_value)
    {
        \App\Models\History::saveForObject('deals', [['id' => $this->id, $field => $new_value]], false);
    }
}
