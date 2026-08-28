<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SabyOrder extends Model
{
    protected $table = 'saby_orders';
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'error' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
