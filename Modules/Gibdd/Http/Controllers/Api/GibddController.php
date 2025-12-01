<?php

namespace Modules\Gibdd\Http\Controllers\Api;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Balance;
use App\Models\GibddFine;
use App\Services\CrudService;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\ApnsConfig;

class GibddController extends Controller
{
    private CrudService $crudService;

    public function __construct(CrudService $crudService)
    {
        $this->crudService = $crudService;
    }

    public function check()
    {
        $total = \Modules\Gibdd\Entities\Module::findFines();
        

        return response()->json(['total' => $total]);
        // $tenant = \App\Models\Tenant::find(tenant('id'));
        // \Modules\Gibdd\Entities\Module::update_fines($tenant);
    }

    public function check_autodor()
    {
        $total = \Modules\Gibdd\Entities\Module::findAutodor();
        

        return response()->json(['total' => $total]);
        // $tenant = \App\Models\Tenant::find(tenant('id'));
        // \Modules\Gibdd\Entities\Module::update_fines($tenant);
    }

    public function findByNum($num)
    {
        $data = \Modules\Gibdd\Entities\Module::findByNum($num);

        return response()->json($data);
    }

    public function check_by_req(Request $request)
    {
        $data = \Modules\Gibdd\Entities\Module::checkByReq($request);

        if(isset($data['error'])) {
            return response()->json([
                    'message' => $data['error']['message']
                ], $data['error']['code']);
        }
        $query_data = [];
        $query_data['id'] = 0;
        $query_data['name'] = $request->ip();
        if($request->sts_number) {
            $query_data['requisite'] = 'СТС';
            $query_data['value'] = $request->sts_number;
            $query_data['additional_value'] = $request->number;
        }
        if($request->driver_license) {
            $query_data['requisite'] = 'Водительское удостоверение';
            $query_data['value'] = $request->driver_license;
        }
        if($request->inn && $request->kpp) {
            $query_data['requisite'] = 'ИНН';
            $query_data['value'] = $request->inn;
            $query_data['additional_value'] = $request->kpp;
        }
        if($request->num_post) {
            $query_data['requisite'] = 'УИН';
            $query_data['value'] = $request->num_post;
        }
        $query_data['email'] = $request->email;
        $query_data['result'] = $data;

        $crudService = new CrudService;

        $result = $crudService->batch('gibdd_queries', [$query_data]);

        return response()->json($data);
    }

    public function pay($id)
    {
        $balance = Balance::first();
        $fine = GibddFine::find($id);
        if($fine->sale_finish > date('Y-m-d'))
            $sum = $fine->discount_sum;//добавить комиссию
        else
            $sum = $fine->sum;//добавить комиссию

        $fact_sum = $sum;
        $sum = ceil($sum / (100 - \Modules\Gibdd\Entities\Module::getPriceKoef()) * 100);

        $field_payment = json_decode($fine->payment, true);

        if($balance->sum >= $sum && (!isset($field_payment['state']) || !$field_payment['state'])) {
            $balance->sum = $balance->sum - $sum;
            $balance->saveQuietly();
            $field_payment['value'] = $sum;
            $field_payment['state'] = 1;
            $fine->payment = json_encode($field_payment);
            $fine->save();
            $history_text = 'Штраф оплачен';
            $history_data = array(
                'entity' => 'fines_gibdd', 
                'entity_id' => $fine->id, 
                'user_id' => 1,
                'text' => $history_text,
                'event' => 'FINE_PAID',
                'color' => '#23704B',
                'show_title' => 1
            );
            $history = new \App\Models\History($history_data);

            $history->saveQuietly();
            $history_data = \App\Models\History::getDataList([$history]);
            $history_response_events = array($history_data);
            \App\Events\ObjectUpdated::dispatch('HistoryUpdated', $history_data);

            $settings = app('settings');//\App\Models\Settings::get(true);
            $data = $fine->getData(array(), $settings);
            \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);
            $tenant = tenant('id');

            $invoice_id = tenancy()->central(function () use ($tenant, $fact_sum, $fine) {
                $account = \App\Models\Account::where('tenant_id', $tenant)->first();
                $fine_central = GibddFine::where('inner_id', $fine->id)->where('account_id', $account->id)->first();
                // info('fine cetnral '.$fine_central->id);
                //if($fine_central) {
                    $disk = \Storage::disk('public');
                    $time = date('dmYHis');
                    $dt = new \DateTime;
                    $formatter = new \IntlDateFormatter(
                        'ru_RU',
                        \IntlDateFormatter::LONG,
                        \IntlDateFormatter::LONG
                    );
                    $formatter->setPattern('d MMMM yyyy');
                    $date = $formatter->format($dt);
                    $account = \App\Models\Account::where('name', $tenant)->first();
                    if(!$account)
                        $account = \App\Models\Account::whereJsonContains('name->value', $tenant)->first();
                    if(!$account) {
                        return array(
                            'code' => 404,
                            'error' => 'Аккаунт не найден',
                        );
                    }

                    $invoice_first = \DB::table('invoices')->orderBy('id', 'desc')->first();
                    $last_id = 1;
                    if($invoice_first)
                        $last_id = $invoice_first->id + 1;


                    $result = $this->crudService->batch('invoices', [[
                        'id' => 0,
                        'name' => 'Счет на оплату штрафа №'.$fine->id.' от '.$date.' аккаунт "'.$tenant.'"',
                        'account_id' => $account->id,
                        'sum' => $fact_sum,
                        'name_of_payment' => $fine->name_of_payment,
                        'kbk' => $fine->kbk,
                        'inn' => $fine->inn,
                        'kpp' => $fine->kpp,
                        'bank' => $fine->bank,
                        'invoice' => $fine->invoice,
                        'corr_invoice' => $fine->corr_invoice,
                        'bik' => $fine->bik,
                        'oktmo' => $fine->oktmo,
                        'number_doc' => $fine->number_doc,
                        'user_id' => 1,
                        'fine_id' => $fine->id
                    ]]);
                    

                    /*$invoice = \App\Models\Document::find($result['id']);

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.admin.invoice', array(
                        'services' => array(
                            array(
                                'name' => 'Оплата штрафа '.$fine->id,
                                'count' => 1,
                                'price' => $invoice->sum
                            )
                        ),
                        'invoice' => $invoice,
                    ));
                    
                    if (!\File::isDirectory($disk->path('invoices/'))) {
                        \File::makeDirectory($disk->path('invoices/'), 0755, true);
                    }
                    if (!\File::isDirectory($disk->path('invoices/'.$time.$invoice->id))) {
                        \File::makeDirectory($disk->path('invoices/'.$time.$invoice->id), 0755, true);
                    }
                    $pdf->save($disk->path('invoices/'.$time.$invoice->id.'/invoice.pdf'));
                    $document = new \App\Models\File();
                    $document->name = 'invoice.pdf';
                    $document->path = 'invoices/'.$time.$invoice->id.'/invoice.pdf';
                    $document->save();

                    $media = $document->addMediaFromUrl('https://compas.pro/storage/app/public/'.$document->path)->toMediaCollection();

                    $thumbnail = str_replace('/home/admin/web/compas.pro/public_html', 'https://compas.pro', $media->getPath('thumb'));


                    $result = $this->crudService->batch('documents', [[
                        'id' => $invoice->id,
                        'photo' => [[
                            'id' => $document->id,
                            'name' => $document->name,
                            'url' => $thumbnail,
                            'file' => 'https://compas.pro/storage/app/public/'.$document->path,
                            'extension' => 'pdf',
                            'sort' => 0
                        ]]
                    ]]);*/
                    \App\Models\Settings::clear_cache();

                    return $result['id'];
                // } else {
                //     return null;
                // }
            });

    
            $balance->minus($sum, "Оплата штрафа $fine->id", null, $invoice_id);

            return response()->json(['success' => 1, 'details' => $data['viewDetail'], 'history_events' => $history_response_events]);
        } elseif($field_payment['state']) {
            return response()->json(['error' => 'Штраф оплачен']);
        } else {
            return response()->json(['error' => 'Недостаточно средств на балансе']);
        }

    }

    public function moneta_pay(Request $request, $id = null)
    {
        return response()->json(['success' => true]);
        $tenant = tenant('id') ?? null;
        $fine = $id ? \App\Models\GibddFine::find($id) : null;

        tenancy()->central(function () use ($id, $tenant, $request, $fine) {
            $number_doc = null;
            if(isset($request->fine['number_doc'])) {
                $number_doc = $request->fine['number_doc'];
                if($request->fine['sale_finish'] >= date('Y-m-d') && isset($request->fine['discount_sum']))
                    $sum = $request->fine['discount_sum'];
                else
                    $sum = $request->fine['sum'];
            } elseif($fine) {
                $payment = json_decode($fine->payment, true);
                if($fine->sale_finish >= date('Y-m-d'))
                    $sum = $fine->discount_sum;
                else
                    $sum = $fine->sum;
                
                $number_doc = $fine->number_doc;
            }
            $o = \DB::table('payments')->where([
                'transaction_id' => $request->transaction_id,
                'provider' => 'moneta',
                'mnt_id' => '70116321',
                //'status' => 'processing',
                'account_id' => $tenant,
                'fine_id' => $id
            ])->first();
            if(!$o) 
                \DB::table('payments')->insert([
                    'transaction_id' => $request->transaction_id,
                    'provider' => 'moneta',
                    'mnt_id' => '70116321',
                    'amount' => (float)$request->amount,
                    'status' => 'processing',
                    'account_id' => $tenant,
                    'fine_id' => $id,
                    'fine_data' => $request->fine ? json_encode($request->fine) : null,
                    'number_doc' => $number_doc,
                    'sum' => isset($sum) ? $sum : null
                ]);
            else {
                if($o->status == 'success')
                    return response()->json(['error' => 'Штраф оплачен']);
                \DB::table('payments')->where([
                    'transaction_id' => $request->transaction_id,
                    'provider' => 'moneta',
                    'mnt_id' => '70116321',
                    'status' => 'processing',
                    'account_id' => $tenant,
                    'fine_id' => $id,
                    'fine_data' => $request->fine ? json_encode($request->fine) : null,
                    'number_doc' => $number_doc,
                    'sum' => isset($sum) ? $sum : null
                ])->update(['amount' => (float)$request->amount]);
            }
        });
        
        return response()->json(['success' => true]);
    }
    
    
}
