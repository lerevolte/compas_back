<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SabyWaybill extends Model
{
    protected $table = 'saby_waybills';
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'error' => 'array',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
