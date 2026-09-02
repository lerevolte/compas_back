<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Address extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $guarded = ['id'];

    public static function boot()
    {
       parent::boot();
       static::creating(function($model)
       {
            $user = Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
       });
    }

    public function setProducts(array $products)
    {
        $this->products = json_encode($products);
        if (\Schema::hasColumn($this->getTable(), 'weight')) {
            $totalWeight = 0;
            foreach ($products as $product) {
                $count = isset($product['count']) ? (float) $product['count'] : 0;
                $w = isset($product['weight']) ? (float) $product['weight'] : 0;
                $totalWeight += $count * $w;
            }
            $this->weight = $totalWeight;
        }

        $row = ['id' => $this->id, 'products' => $this->products];
        if (\Schema::hasColumn($this->getTable(), 'weight')) {
            $row['weight'] = $this->weight;
        }
        $objects = History::saveForObject($this->getTable(), array($row));
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
