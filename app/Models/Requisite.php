<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CrudService;

class Requisite extends Model
{
    use FieldValue, ModelActions, SoftDeletes, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static function boot()
    {
        parent::boot();
        static::created(function($model){
            $tenant = tenant('id');

            if($tenant) {
                tenancy()->central(function () use ($tenant, $model) {
                    $crudService = new CrudService;

                    $account = Account::where('tenant_id', $tenant)->first();
                    $data = [
                        'id' => 0,
                        'name' => $model->name,
                        'inn' => $model->inn,
                        'kpp' => $model->kpp,
                        'address' => $model->address,
                        'fact_address' => $model->fact_address,
                        'user_id' => 1,
                        'account_id' => $account->id,
                        'inner_id' => $model->id
                    ];

                    $result = $crudService->batch('requisites', [$data]);
                });
            }
        });
        static::updated(function($model){
            $tenant = tenant('id');

            if($tenant) {
                tenancy()->central(function () use ($tenant, $model) {
                    $crudService = new CrudService;

                    $account = Account::where('tenant_id', $tenant)->first();
                    $requisite = Requisite::where('inner_id', $model->id)->first();
                    if($requisite) {
                        $data = [
                            'id' => $requisite->id,
                            'name' => $model->name,
                            'inn' => $model->inn,
                            'kpp' => $model->kpp,
                            'address' => $model->address,
                            'fact_address' => $model->fact_address
                        ];

                        $result = $crudService->batch('requisites', [$data]);
                    }
                    
                });
            }
        });


        static::saved(function($model){
            if (count(array_intersect(array_keys($model->getChanges()), \App\Services\SaleDocumentService::ORG_FIELDS))) {
                try {
                    \App\Services\SaleDocumentService::queueForOrganization();
                } catch (\Throwable $e) {
                }
            }
        });

        static::deleted(function($model){
            try {
                \App\Services\SaleDocumentService::queueForOrganization();
            } catch (\Throwable $e) {
            }
        });
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'requisite_id')->orderBy('id', 'desc');
    }
}
