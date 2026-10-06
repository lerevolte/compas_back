<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrderMaterials extends Model
{
    use SoftDeletes;

    protected $table = 'production_orders';

    protected $guarded = ['id'];

    public function getProductsAttribute()
    {
        return $this->attributes[\App\Services\ProductionService::MATERIALS_FIELD] ?? null;
    }

    public function setProductsAttribute($value)
    {
        $this->attributes[\App\Services\ProductionService::MATERIALS_FIELD] = $value;
    }

    public function setProducts(array $products, $sum = null)
    {
        $owner = ProductionOrder::find($this->id);
        if ($owner) {
            $owner->setMaterials($products);
        }
    }
}
