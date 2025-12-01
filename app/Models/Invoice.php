<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CrudService;

class Invoice extends Model
{
    use FieldValue, ModelActions, SoftDeletes, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static function boot()
    {
        parent::boot();
        static::updating(function($model)
        {   
        });
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'invoice_id');
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('invoices', array(['id' => $this->id, $field => $new_value]), false);
    }
}
