<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator, App\Traits\HasRelationFields;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class SupplierOrder extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes, HasRelationFields;

    protected $table = 'supplier_orders';

    protected $guarded = ['id'];

    public const PRODUCT_FIELD = 'supplier_order_id';
    public const COMPANY_FIELD = 'supplier_order_id';
    public const COMPANY_TYPE = 'Поставщик';

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
            if (empty($model->date) && \Schema::hasColumn($model->getTable(), 'date')) {
                $model->date = date('Y-m-d');
            }
            if (empty($model->number) && \Schema::hasColumn($model->getTable(), 'number')) {
                $model->number = \App\Services\DocumentNumber::next();
            }
        });

        static::saving(function ($model) {
            foreach (['contact_id', 'company_id'] as $col) {
                if (is_array($model->{$col})) {
                    $model->{$col} = json_encode(array_values(array_filter($model->{$col}, 'is_numeric')));
                }
            }
        });

        static::saved(function ($model) {
            $changes = array_keys($model->getChanges());
            if ((in_array('company_id', $changes, true) || $model->wasRecentlyCreated) && $model->company_id) {
                try {
                    Company::addType($model->company_id, self::COMPANY_TYPE);
                } catch (\Throwable $e) {
                }
            }
            if (in_array('company_id', $changes, true) || $model->wasRecentlyCreated) {
                \App\Services\ReverseLinkService::sync(
                    'companies',
                    self::COMPANY_FIELD,
                    (int) $model->id,
                    $model->wasRecentlyCreated ? [] : \App\Services\ReverseLinkService::ids($model->getOriginal('company_id')),
                    \App\Services\ReverseLinkService::ids($model->company_id)
                );
            }
            if (in_array('products', $changes, true) || ($model->wasRecentlyCreated && $model->products)) {
                self::syncProductLinks(
                    (int) $model->id,
                    $model->wasRecentlyCreated ? null : $model->getOriginal('products'),
                    $model->products
                );
            }
        });

        static::deleted(function ($model) {
            self::syncProductLinks((int) $model->id, $model->products, null);
            \App\Services\ReverseLinkService::sync('companies', self::COMPANY_FIELD, (int) $model->id, \App\Services\ReverseLinkService::ids($model->company_id), []);
        });

        static::restored(function ($model) {
            self::syncProductLinks((int) $model->id, null, $model->products);
            \App\Services\ReverseLinkService::sync('companies', self::COMPANY_FIELD, (int) $model->id, [], \App\Services\ReverseLinkService::ids($model->company_id));
        });
    }

    public static function productIds($products): array
    {
        $ids = [];
        foreach (\App\Services\ShipmentService::decode($products) as $product) {
            if (is_array($product) && !empty($product['id']) && is_numeric($product['id'])) {
                $ids[] = (int) $product['id'];
            }
        }

        return array_values(array_unique($ids));
    }

    public static function syncProductLinks(int $orderId, $oldProducts, $newProducts): void
    {
        try {
            if (!$orderId || !\Schema::hasTable('products') || !\Schema::hasColumn('products', self::PRODUCT_FIELD)) {
                return;
            }
            $old = self::productIds($oldProducts);
            $new = self::productIds($newProducts);
            $removed = array_diff($old, $new);
            $added = array_diff($new, $old);
            $touched = array_values(array_unique(array_merge($removed, $added)));
            if (!count($touched)) {
                return;
            }
            foreach (\DB::table('products')->whereIn('id', $touched)->get(['id', self::PRODUCT_FIELD]) as $product) {
                $current = \App\Services\RelationFieldsService::ids($product->{self::PRODUCT_FIELD});
                $next = in_array((int) $product->id, $added, true)
                    ? array_values(array_unique(array_merge($current, [$orderId])))
                    : array_values(array_filter($current, fn ($v) => $v !== $orderId));
                if ($next !== $current) {
                    \DB::table('products')->where('id', $product->id)->update([self::PRODUCT_FIELD => json_encode($next)]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('supplier orders: не удалось обновить связь товаров', ['order' => $orderId, 'error' => $e->getMessage()]);
        }
    }

    public function setProducts(array $products, $sum = null)
    {
        $this->products = json_encode($products);
        $totalWeight = 0;
        $totalVolume = 0;
        $total = 0.0;
        foreach ($products as $product) {
            $count = isset($product['count']) ? (float) $product['count'] : 0;
            $w = isset($product['weight']) ? (float) $product['weight'] : 0;
            $v = isset($product['volume']) ? (float) $product['volume'] : 0;
            $totalWeight += $count * $w;
            $totalVolume += $count * $v;
            $total += $count * (isset($product['price']) ? (float) $product['price'] : 0);
        }
        if (\Schema::hasColumn($this->getTable(), 'weight')) {
            $this->weight = $totalWeight;
        }
        if (\Schema::hasColumn($this->getTable(), 'volume')) {
            $this->volume = $totalVolume;
        }
        if ($sum !== null && (float) $sum > 0) {
            $this->sum = rtrim(rtrim(number_format((float) $sum, 2, '.', ''), '0'), '.');
        } else {
            $this->sum = $total > 0 ? rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.') : null;
        }

        $objects = History::saveForObject(
            $this->getTable(),
            [[
                'id' => $this->id,
                'products' => $this->products,
                'sum' => $this->sum,
            ]]
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
