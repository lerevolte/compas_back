<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionMaterials extends Model
{
    use SoftDeletes;

    protected $table = 'productions';

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
        $owner = Production::find($this->id);
        if ($owner) {
            $owner->setMaterials($products);
        }
    }
}
