<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Auth;

class Analytic extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];

}
