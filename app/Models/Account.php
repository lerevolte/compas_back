<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Account extends Model
{
    protected $fillable = [
        'yandex_api_key',
        'map_city',
        'map_provider',
        'map_zone_radius',
        'map_zone_car_radius',
        'map_stop_car_radius',
        'map_stop_time',
        'map_latitude',
        'map_longitude'
    ];


    public function getPointField() {
        return $this->point_field;
    }
}
