<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class BankRequisite extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $table = 'bank_requisites';

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

        static::saved(function ($model) {
            $model->syncDefault();
            if ($model->wasRecentlyCreated || count(array_intersect(array_keys($model->getChanges()), \App\Services\SaleDocumentService::BANK_FIELDS))) {
                try {
                    \App\Services\SaleDocumentService::queueForBank($model);
                } catch (\Throwable $e) {
                }
            }
        });

        static::deleted(function ($model) {
            $model->promoteSibling();
            try {
                \App\Services\SaleDocumentService::queueForBank($model);
            } catch (\Throwable $e) {
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function isDefault(): bool
    {
        return (int) $this->is_default === 1;
    }

    public function syncDefault(): void
    {
        $companyId = (int) $this->company_id;
        if (!$companyId) {
            return;
        }

        $siblings = \DB::table($this->getTable())
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('id', '!=', $this->id);

        if ($this->isDefault()) {
            (clone $siblings)->where('is_default', '1')->update(['is_default' => '0']);
            return;
        }

        if (!(clone $siblings)->where('is_default', '1')->exists()) {
            \DB::table($this->getTable())->where('id', $this->id)->update(['is_default' => '1']);
            $this->setAttribute('is_default', '1');
            $this->syncOriginalAttribute('is_default');
        }
    }

    public function promoteSibling(): void
    {
        $companyId = (int) $this->company_id;
        if (!$companyId || !$this->isDefault()) {
            return;
        }

        $next = \DB::table($this->getTable())
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('id', '!=', $this->id)
            ->orderBy('id')
            ->value('id');

        if ($next) {
            \DB::table($this->getTable())->where('id', $next)->update(['is_default' => '1']);
        }
    }
}
