<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use FieldValue, ModelActions, SoftDeletes, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];
}
