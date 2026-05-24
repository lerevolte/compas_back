<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use YooKassa\Client;
use App\Models\Tariff;
use Mail;
use App\Mail\InvoiceCreated;
use App\Services\CrudService;
use Illuminate\Support\Facades\Log;

class Balance extends Model
{
    public static function boot()
    {
        parent::boot();
        static::updating(function($model)
        {
            info('balance updated '.$model->sum);
            // $crudService = new CrudService;
            // $tenant = tenant('id');

            // tenancy()->central(function () use ($tenant, $model) {
            //     $account = Account::where('tenant_id', $tenant)->first();

            //     $data = [
            //         'id' => $account->id,
            //         'balance' => $model->sum
            //     ];
            //     info($account->id.' '.$model->sum);
            //     $result = $crudService->batch('accounts', [$data]);
            // });
        });
    }


	public function getInfo(Request $request)
    {
        $limit = $request->per_page ? $request->per_page : 25;
        $page = $request->page ? $request->page : 1;
        $sort_field = $request->sort_field ? $request->sort_field : 'id';
        $sort_order = $request->sort_order ? $request->sort_order : 'desc';

        $balance = $this;
        if ($request->date_start) {
            $date_start = $request->date_start;
            $date_end = $request->date_end;
        } else {
            $date = date('d.m.Y');
            $date_start = date('01.m.Y', strtotime($date));
            $date_end = date('t.m.Y', strtotime($date));
        }

        $operations = BalanceOperation::orderBy($sort_field, $sort_order)->where('balance_id', $balance->id);
        

        if($date_start != $date_end) {
            $from = $date_start;
            $to = $date_end;
            if ($from != $to) {
                $operations->whereBetween('created_at', [date("Y-m-d", strtotime($from)), date("Y-m-d", strtotime("+1 day", strtotime($to)))]);
            } else {
                $operations->whereDate('created_at',date("Y-m-d", strtotime($date_start)));
            }
        } else {
            $operations->whereDate('created_at', date("Y-m-d", strtotime($date_start)));
        }
        $operations = $operations_sum = $operations->orderBy('date', 'desc');
        
        $total_sum = 0;
        foreach ($operations->orderBy('date', 'desc')->get() as $operation) {
            if($operation->type == 'пополнение')
                $total_sum+= $operation->sum;
            elseif($operation->type == 'списание')
                $total_sum-= $operation->sum;
        }
        $operations = $operations->paginate($limit);
        $tariff = \DB::table('settings')->where('key', 'tariff')->first();
        if(!$tariff) {
            \DB::table('settings')->insert([
                'key' => 'tariff',
                'type' => 'tariff',
                'value' => 1
            ]);
            $tariff = \DB::table('settings')->where('key', 'tariff')->first();
        }
        
        $tariff = $tariff->value;
        $requisites = \App\Models\Requisite::select(['id', 'name'])->orderBy('choosed_at', 'desc')->get();
        $payers = array();
        foreach($requisites as $req) {
            $payers[] = array(
                'label' => $req->name,
                'value' => $req->id
            );
        }
        $payers[] = array(
                'label' => 'Банковской картой',
                'value' => 0
            );
        $data = array(
            'tariffs' => \App\Models\Tariff::list(),
            'payers' => $payers,
            'current_tariff' => $tariff,
            'balance' => $balance->sum,
            'data' => array(
                'count' => $operations->count(),
                'current_page' => $operations->currentPage(),
                'last_page' => $operations->lastPage(),
                'per_page' => $operations->perPage(),
                'total' => $operations->total(),
                'from' => $operations->firstItem(),
                'to' => $operations->lastItem(),
                'data' => $operations->items()
            ),
            'total_sum' => $total_sum,
            'date_start' => $date_start,
            'date_end' => $date_end
        );

		return $data;
	}

    public function payment(int $sum, ?int $payer = 0)
    {
        $tenant = tenant('id');
        if($payer) {
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
            $payer = \App\Models\Requisite::find($payer);
            if(!$payer) {
                return array(
                    'code' => 404,
                    'error' => 'Плательщик не найден',
                );
            }
            $payer->choosed_at = \DB::raw('now()');
            $payer->saveQuietly();
            info('create document');
            $invoice = new Document;
            $invoice->save();
            $invoice->name = 'Счет на оплату №'.$invoice->id.' от '.$date;
            $invoice->requisite_id = $payer->id;
            $invoice->sum = $sum;

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', array(
                'services' => array(
                    array(
                        'name' => 'Пополнение баланса для оплаты услуг',//'Пополнение баланса',
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
            $pdf->save($disk->path('invoices/'.$time.$invoice->id.'/invoice.pdf'));
            $document = new File();
            $document->name = 'invoice.pdf';
            $document->path = 'invoices/'.$time.$invoice->id.'/invoice.pdf';
            $document->save();
            $doc_photo = [
                'id' => $document->id,
                'name' => $document->name,
                'url' => 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path,
                'file' => 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path,
                'extension' => 'pdf',
                'sort' => 0
            ];
            $invoice->photo = json_encode($doc_photo);
            $invoice->save();

            $docs_email = \DB::table('settings')->where([
                'key' => 'docs_email',
                'type' => 'account'
            ])->first();
            if($docs_email && $docs_email->value) {
                try {
                    Mail::to($docs_email->value)->send(new InvoiceCreated(
                        filepath: storage_path('app/public/' . $document->path)
                    ));
                } catch (\Exception $e) {
                    // Логируем ошибку, чтобы не потерять информацию
                    Log::error('Не удалось отправить письмо с инвойсом', [
                        'document_id' => $document->id ?? null,
                        'email'       => $docs_email->value,
                        'error'       => $e->getMessage(),
                        'trace'       => $e->getTraceAsString(),
                    ]);

                    // Можно дополнительно уведомить разработчиков через Slack и т.п.
                    // report($e); // если хочешь, чтобы Laravel сам отправил в Sentry/Bugsnag и т.д.

                    // Приложение продолжает работать — пользователь даже может не узнать об ошибке
                }
            }
            tenancy()->central(function () use ($tenant, $payer, $invoice, $doc_photo) {
                $crudService = new \App\Services\CrudService;
                $account = Account::where('tenant_id', $tenant)->first();
                $req = Requisite::where('inner_id', $payer->id)->first();
                if(!$account) {
                    info('not found');
                    return array(
                        'code' => 404,
                        'error' => 'Аккаунт не найден',
                    );
                }
                $result = $crudService->batch('documents', [[
                    'id' => 0,
                    'inner_id' => $invoice->id,
                    'photo' => [$doc_photo],
                    'account_id' => $account->id,
                    'name' => $invoice->name,
                    'requisite_id' => $req->id,
                    'sum' => $invoice->sum,
                    'user_id' => 1
                ]]);
            });



            return array(
                'url' => 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/invoices/'.$time.$invoice->id.'/invoice.pdf'
            );
        } else {
            // \DB::table('requisites')->whereNotNull('choosed_at')->update(['choosed_at' => null]);
            // $client = new Client();
            // $client->setAuth(460551, 'test_E_ZE8tcpfRHXfUtrL5u6chOiuqG6MEahkfqMNA54kgM');
            // //$client->setAuth(env('YOOKASSA_ID'), env('YOOKASSA_KEY'));
            // $idempotenceKey = uniqid('', true);

            // $response = $client->createPayment(
            //     array(
            //         'amount' => array(
            //             'value' => $sum,
            //             'currency' => 'RUB',
            //         ),
            //         'payment_method_data' => array(
            //             'type' => 'bank_card',
            //         ),
            //         'metadata' => array(
            //             'tenant'=> $tenant,
            //             //'payer_id' => $payer,
            //             'sum' => $sum
            //         ),
            //         'capture' => true,
            //         'confirmation' => array(
            //             'type' => 'redirect',
            //             'return_url' => 'https://'.$tenant.'.compas.pro/settings/?tab=tariffs',
            //         ),
            //         'description' => 'Пополнение баланса',
            //     ),
            //     $idempotenceKey
            // );
            // $confirmationUrl = $response->getConfirmation()->getConfirmationUrl();

            // return array(
            //     'url' => $confirmationUrl
            // );
        }
    }

    public function plus($sum, $comment = 'Пополнение баланса для оплаты услуг', $document_id = null)
    {
    	$operation = new BalanceOperation;
    	$operation->sum = $sum;
    	$operation->type = 'пополнение';
    	$operation->balance_id = $this->id;
        $operation->comment = $comment;
        $operation->date = date("Y-m-d");
        if($document_id)
            $operation->document_id = $document_id;
    	$operation->save();
    	$this->sum = $this->sum + $sum;
    	$this->save();

        return $operation;
    }

    public function minus($sum, $comment = 'Списание по тарифу', $document_id = null, $invoice_id = null)
    {
    	$operation = new BalanceOperation;
    	$operation->sum = $sum;
    	$operation->type = 'списание';
    	$operation->balance_id = $this->id;
        $operation->comment = $comment;
        $operation->date = date("Y-m-d");
        if($document_id)
            $operation->document_id = $document_id;
        if($invoice_id)
            $operation->invoice_id = $invoice_id;
    	$operation->save();
        if(!$invoice_id)
    	   $this->sum = $this->sum - $sum;
    	$this->save();

        return $operation;
    }

    public function operations($params)
    {
        $limit = isset($params['per_page']) ? $params['per_page'] : 25;
        $page = isset($params['page']) ? $params['page'] : 1;
        $sort_field = isset($params['sort_field']) ? $params['sort_field'] : 'id';
        $sort_order = isset($params['sort_order']) ? $params['sort_order'] : 'desc';

        $balance = $this;
        if (isset($params['date_start'])) {
            $date_start = $params['date_start'];
            $date_end = $params['date_end'];
        } else {
            $date = date('d.m.Y');
            $date_start = date('01.01.2000');//date('01.m.Y', strtotime($date));
            $date_end = date('t.m.Y', strtotime($date));
        }
        
        $operations = BalanceOperation::orderBy($sort_field, $sort_order)->where('balance_id', $balance->id);

        if($date_start != $date_end) {
            $from = $date_start;
            $to = $date_end;
            if ($from != $to) {
                $operations->whereBetween('created_at', [date("Y-m-d", strtotime($from)), date("Y-m-d", strtotime("+1 day", strtotime($to)))]);
            } else {
                $operations->whereDate('created_at',date("Y-m-d", strtotime($date_start)));
            }

        } else {
            $operations->whereDate('created_at', date("Y-m-d", strtotime($date_start)));
        }
        $operations = $operations->orderBy('date', 'desc');
        $operations_expense = clone $operations;
        $expense = $operations_expense->where('type', 'списание')->sum('sum');
        //$expense = BalanceOperation::orderBy($sort_field, $sort_order)->where('balance_id', $balance->id)->where('type', 'списание')->sum('sum');
        $operations = $operations->paginate($limit);
        

        $data = array(
            'count' => $operations->count(),
            'current_page' => $operations->currentPage(),
            'last_page' => $operations->lastPage(),
            'per_page' => $operations->perPage(),
            'total' => $operations->total(),
            'from' => $operations->firstItem(),
            'to' => $operations->lastItem(),
            'data' => $operations->items(),
            'expense' => $expense
        );

        return $data;
        //return $this->hasMany(BalanceOperation::class);
    }

    public function calculateMinuses($date)
    {
        if (!strstr($date, '-')) {
            $date_end = $date;
        } else {
            $d = explode('-', $date);
            $date = $d[0];
            $date_end = $d[1];
        }

        if($date != $date_end) {
            $from = $date;
            $to = $date_end;
            if ($from != $to) {
                $routes_by_date = Route::whereBetween('date_format', [date("Y-m-d", strtotime($from)), date("Y-m-d", strtotime($to))])->orderBy('id', 'desc')->get()->groupBy('date_format');
            } else {
                $routes_by_date = Route::whereDate('date_format',date("Y-m-d", strtotime($date)))->get()->groupBy('date_format');
            }
        } else {
            $routes_by_date = Route::whereDate('date_format',date("Y-m-d", strtotime($date)))->get()->groupBy('date_format');
        }

        foreach ($routes_by_date as $date => $routes) {
            $mobile_count = 0;
            foreach($routes as $route) {
                if(optional($route->driver)->user_id) {
                    $mobile_count++;
                }
            }
            $operation = new BalanceOperation;
            $operation->sum = count($routes)*30 + $mobile_count*5;
            $operation->type = 'minus';
            $operation->balance_id = $this->id;
            $operation->count_mobile = $mobile_count;
            $operation->comment = 'Расход за день';
            $operation->count_routes = count($routes);
            $operation->date = date("Y-m-d", strtotime($date));
            $operation->save();
            // $this->sum = $this->sum - $operation->sum;
            // $this->save();
            
        }
    }

    public function calculateDaily()
    {
    
        //$tenant->run(function () use ($balance) {
            $date = date('Y-m-d');
            $operation = BalanceOperation::where('date', $date)->where('comment', 'Списание по тарифу')->first();
            if(!$operation) {
                $tariff = Tariff::current();
                
                if($tariff && $tariff->price_per_day) {
                    $operation = BalanceOperation::create([
                        'sum' => $tariff->price_per_day,
                        'type' => 'списание',
                        'balance_id' => $this->id,
                        'comment' => 'Списание по тарифу',
                        'date' => $date
                    ]);
                    // $this->sum = $this->sum - $operation->sum;
                    // $this->save();

                    
                }
            }
        //});
    }
}
