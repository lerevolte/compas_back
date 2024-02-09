<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tariff extends Model
{
    protected $guarded = ['id'];
    
    public static function list() 
    {
        $data = array();
        $items = Tariff::get();
        foreach ($items as $item) {
            $data[] = array(
                'id' => $item->id,
                'name' => $item->name,
                'sort' => $item->sort,
                'prices' => json_decode($item->prices, true)
            );
        }

        return $data;
    }
    
}
