<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Specification extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $table = 'specifications';

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
    }

    public function setProducts(array $products, $sum = null)
    {
        $this->products = json_encode($products);
        History::saveForObject(
            $this->getTable(),
            [[
                'id' => $this->id,
                'products' => $this->products,
            ]],
            true,
            [],
            [],
            true
        );
        $this->saveQuietly();
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
                $count = $product['count'] ?? 0;
                $count = is_numeric($count) ? rtrim(rtrim(number_format((float) $count, 3, '.', ''), '0'), '.') : $count;
                $html .= (is_array($product['name'] ?? null) ? $product['name'][0] : ($product['name'] ?? '')) . ' <b>' . $count . '</b><br>';
            }
        }

        return $html;
    }
}
