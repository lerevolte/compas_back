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
    
    public const TYPE_FIELD_TITLE = 'Тип компании';

    public static function addType($companyIds, string $label): int
    {
        $ids = is_array($companyIds) ? $companyIds : [$companyIds];
        if (is_string($companyIds) && is_array($decoded = json_decode($companyIds, true))) {
            $ids = $decoded;
        }
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
        if (!count($ids)) {
            return 0;
        }
        try {
            $typeId = \DB::table('data_types')->where('slug', 'companies')->value('id');
            $row = $typeId ? \DB::table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('type', 'select_dropdown')
                ->where('title', self::TYPE_FIELD_TITLE)
                ->where('is_remove', 0)
                ->first() : null;
            if (!$row || !\Schema::hasColumn('companies', $row->field)) {
                return 0;
            }
            $details = json_decode((string) $row->details, true);
            $optionValue = null;
            foreach ((is_array($details) ? ($details['options'] ?? []) : []) as $option) {
                $text = is_array($option) ? ($option['label'] ?? '') : '';
                $text = is_array($text) ? ($text['text'] ?? '') : $text;
                if (mb_strtolower(trim((string) $text)) === mb_strtolower(trim($label))) {
                    $optionValue = $option['value'];
                    break;
                }
            }
            if ($optionValue === null) {
                return 0;
            }
            $updated = 0;
            foreach (\DB::table('companies')->whereIn('id', $ids)->get(['id', $row->field]) as $company) {
                $raw = $company->{$row->field};
                $current = is_string($raw) && is_array($decoded = json_decode($raw, true)) ? $decoded : ($raw === null || $raw === '' ? [] : [$raw]);
                $current = array_values(array_filter($current, fn ($v) => $v !== null && $v !== ''));
                if (in_array((string) $optionValue, array_map('strval', $current), true)) {
                    continue;
                }
                $current[] = $optionValue;
                $stored = $row->is_plural ? json_encode(array_values($current)) : (string) $optionValue;
                try {
                    History::saveForObject('companies', [['id' => $company->id, $row->field => $row->is_plural ? array_values($current) : (string) $optionValue]], true, [], [], true);
                } catch (\Throwable $e) {
                }
                \DB::table('companies')->where('id', $company->id)->update([$row->field => $stored, 'updated_at' => now()]);
                $updated++;
            }
            return $updated;
        } catch (\Throwable $e) {
            \Log::warning('company type auto-tag failed', ['label' => $label, 'error' => $e->getMessage()]);
            return 0;
        }
    }

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
                \Log::channel('bitrix24')->warning('company push failed', ['company_id' => $model->id, 'error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
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
