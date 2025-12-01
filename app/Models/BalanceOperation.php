<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\CrudService;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;

class BalanceOperation extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

	protected $fillable = [
		'sum',
		'type',
		'balance_id',
		'comment',
		'date'
	];

    public static function boot()
    {
       	parent::boot();
        static::created(function($model)
        {
        	$tenant = tenant('id');
        	$balance = Balance::first();
            if(!$model->invoice_id) {
            	if($model->type == 'списание') {
                    //if(property_exists($model, 'balance_before')) {
                        $model->balance_before = $balance->sum;
                        //$model->saveQuietly();
                    //}
            		$balance->sum = $balance->sum - $model->sum;
                    $balance->saveQuietly();
            	} else {
            		$balance->sum = $balance->sum + $model->sum;
            	}
            } else {
                if($model->type == 'списание') {
                    info('balance1 '.$balance->sum);
                    info('balance2 '.$model->sum);
                    //if(property_exists($model, 'balance_before')) {
                        $model->balance_before = $balance->sum + $model->sum;
                        //$model->saveQuietly();
                    //}
                    info('balance before '.$model->balance_before);
                }
            }
        	
            $model->saveQuietly();
            tenancy()->central(function () use ($tenant, $model, $balance) {
            	$crudService = new CrudService;
            	$account = Account::where('tenant_id', $tenant)->first();
                
            	$data = [
                    'id' => 0,
                    'name' => $model->comment,
                    'sum' => $model->sum,
                    'account_id' => $account->id,
                    'user_id' => 1
                ];

                $type = $model->type == 'списание' ? 'expenses' : 'admissions';

                if($type == 'admissions') {
                    $document = Document::where('inner_id', $model->document_id)->first();
                    $data['document_id'] = $document ? $document->id : null;
                }

                if($model->invoice_id && $type == 'expenses')
                    $data['invoice_id'] = $model->invoice_id;
                
                $result = $crudService->batch($type, [$data]);


                $sum = $balance->sum;
                $account = Account::where('tenant_id', $tenant)->first();

                $data = [
                    'id' => $account->id,
                    'balance' => $sum
                ];
                $result = $crudService->batch('accounts', [$data]);
            });
        });

        // static::updated(function($model)
        // {
        //     $tenant = tenant('id');
        //     $balance = Balance::first();

        //     if(!$model->getOriginal('invoice_id') && $model->invoice_id) {
        //         tenancy()->central(function () use ($tenant, $model, $balance) {
        //             $crudService = new CrudService;
        //             $account = Account::where('tenant_id', $tenant)->first();
        //             $document = Document::where('inner_id', $model->document_id)->first();
        //             $data = [
        //                 'id' => 0,
        //                 'name' => $model->comment,
        //                 'sum' => $model->sum,
        //                 'account_id' => $account->id,
        //                 'user_id' => 1
        //             ];

        //             $type = 'expenses';

        //             $data['invoice_id'] = $model->invoice_id;
        //             $result = $crudService->batch($type, [$data]);


        //             $sum = $balance->sum;
        //             $account = Account::where('tenant_id', $tenant)->first();

        //             $data = [
        //                 'id' => $account->id,
        //                 'balance' => $sum
        //             ];
        //             $result = $crudService->batch('accounts', [$data]);
        //         });
        //     }

            
                    
        // });
    }
}