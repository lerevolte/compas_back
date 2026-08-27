<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelAdminPanel\Traits\Cropper;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InImage;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;


class Company extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $guarded = ['id'];
    
    public static function boot()
    {
        parent::boot();
        static::creating(function($model)
        {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
        });

        static::saved(function($model)
        {
            if (count(array_intersect(array_keys($model->getChanges()), \App\Services\SaleDocumentService::COMPANY_FIELDS))) {
                try {
                    \App\Services\SaleDocumentService::queueForCompany((int) $model->id);
                } catch (\Throwable $e) {
                }
            }

            if (!isset($model->getChanges()['name']) || !$model->b24_id) {
                return;
            }
            if (!class_exists(\Modules\Bitrix24\Services\B24EntitySync::class)
                || \Modules\Bitrix24\Services\B24EntitySync::$muted) {
                return;
            }
            try {
                \Modules\Bitrix24\Services\B24EntitySync::make()?->pushCompany($model, ['name']);
            } catch (\Throwable $e) {
                \Log::channel('bitrix24')->warning('company push failed', ['company_id' => $model->id, 'error' => $e->getMessage()]);
            }
        });
    }

    public function cars()
    {
        return $this->hasMany(Car::class, 'company_id')->orderBy('choosed_at');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'company_id');
    }

    public function fines_gibdd()
    {
        return $this->hasMany(GibddFine::class, 'company_id');
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'company_contact');
    }

    public function bank_requisites()
    {
        return $this->hasMany(BankRequisite::class, 'company_id')->orderBy('id');
    }

    public function defaultBankRequisite(): ?BankRequisite
    {
        if (!\Schema::hasTable('bank_requisites')) {
            return null;
        }

        return $this->bank_requisites()->where('is_default', '1')->first()
            ?: $this->bank_requisites()->first();
    }

    public function deals()
    {
        return $this->belongsToMany(Deal::class, 'company_deal');
    }

    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('companies', array(['id' => $this->id, $field => $new_value]), false);
    }

}
