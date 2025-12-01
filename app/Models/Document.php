<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CrudService;

class Document extends Model
{
    use FieldValue, ModelActions, SoftDeletes, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static function boot()
    {
        parent::boot();
        static::updating(function($model)
        {   
            $tenant = tenant('id');
            // info('document payment');
            // info($model->getOriginal('status').' '.$model->status);
            // info($tenant);
            // info($model->inner_id);
            if($model->getOriginal('status') != $model->status && $model->status == 2 && !$tenant && $model->inner_id) {
                //info('document payment1');
                $account = Account::where('id', $model->account_id)->first();
                $requisite = Requisite::find($model->requisite_id);
                if($account) {
                    $tenant = Tenant::find($account->tenant_id);
                    $tenant->run(function ($tenant) use ($model, $requisite){
                        $disk = \Storage::disk('public');
                        $crudService = new CrudService;
                        $time = date('dmYHis');

                        $payer = Requisite::find($requisite->inner_id);
                        $invoice = Document::find($model->inner_id);

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.act', array(
                            'services' => array(
                                array(
                                    'name' => 'Услуги автомониторинга (штрафы, страховки, платные дороги, логистика)',//'Пополнение баланса',
                                    'count' => 1,
                                    'price' => $invoice->sum
                                )
                            ),
                            'invoice' => $invoice,
                            'payer' => $payer,
                        ));
                        
                        if (!\File::isDirectory($disk->path('invoices/'))) {
                            \File::makeDirectory($disk->path('invoices/'), 0755, true);
                        }
                        if (!\File::isDirectory($disk->path('invoices/'.$time.$invoice->id))) {
                            \File::makeDirectory($disk->path('invoices/'.$time.$invoice->id), 0755, true);
                        }
                        $pdf->save($disk->path('invoices/'.$time.$invoice->id.'/act.pdf'));
                        $document = new File();
                        $document->name = 'act.pdf';
                        $document->path = 'invoices/'.$time.$invoice->id.'/act.pdf';
                        $document->save();
                        $doc_photo = [
                            'id' => $document->id,
                            'name' => 'Акт по счету '.$document->id,
                            'url' => 'https://'.$tenant->id.'.compas.pro/storage/tenant'.$tenant->id.'/app/public/'.$document->path,
                            'file' => 'https://'.$tenant->id.'.compas.pro/storage/tenant'.$tenant->id.'/app/public/'.$document->path,
                            'extension' => 'pdf',
                            'sort' => 0
                        ];
                        // $invoice->photo = json_encode($doc_photo);
                        // $invoice->save();

                        $data = [
                            'id' => $model->inner_id,
                            'act' => $doc_photo
                        ];

                        $result = $crudService->batch('documents', [$data]);
                        \App\Models\Settings::clear_cache();


                        Balance::first()->plus($invoice->sum, 'Пополнение баланса', $model->inner_id);
                    });
                }
            };
        });
    }
}
