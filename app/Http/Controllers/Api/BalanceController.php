<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Models\Balance;
use App\Models\File;
use App\Models\BalanceOperation;
use YooKassa\Client;


class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $data = array();
        $limit = $request->per_page ? $request->per_page : 25;
        $page = $request->page ? $request->page : 1;
        $sort_field = $request->sort_field ? $request->sort_field : 'id';
        $sort_order = $request->sort_order ? $request->sort_order : 'desc';

        $balance = Balance::first();
        if ($request->date_start) {
            $date_start = $request->date_start;
            $date_end = $request->date_end;
        } else {
            $date = date('d.m.Y');
            $date_start = date('01.m.Y', strtotime($date));
            $date_end = date('t.m.Y', strtotime($date));
        }
        // if (!strstr($date, '-')) {
        //     $date_end = $date;
        // } else {
        //     $d = explode('-', $date);
        //     $date = $d[0];
        //     $date_end = $d[1];
        // }
        // if ($date == $date_end)
        //     $full_date = $date;
        // else
        //     $full_date = $date.'-'.$date_end;


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
        $operations = $operations->paginate($limit);
        //$operations = $operations->get();
        $total_sum = 0;
        foreach ($operations->items() as $operation) {
            if($operation->type == 'plus')
                $total_sum+= $operation->sum;
            elseif($operation->type == 'minus')
                $total_sum-= $operation->sum;
            // code...
        }
        $tariff = \DB::table('settings')->where('key', 'tariff')->first();
        if(!$tariff) {
            \DB::table('settings')->insert([
                'key' => 'tariff',
                'type' => 'tariff',
                'value' => json_encode(['id' => 2, 'date' => null])
            ]);
            $tariff = \DB::table('settings')->where('key', 'tariff')->first();
        }
        $tariff_details = json_decode($tariff->value, true);
        $tariff = array(
            'id' => $tariff_details['id'],
            'date' => $tariff_details['date']
        );
        //$total_sum = $operations_sum->where('type', 'plus')->sum('sum') - $operations_sum->where('type', 'minus')->sum('sum');

        $data = array(
            'tariffs' => \App\Models\Tariff::list(),
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
            'date_end' => $date_end,
        );

        return response()->json($data);
    }

    public function change(Request $request)
    {
        $balance = Balance::first();
        if($request->type == 'minus' && $request->sum)
            $balance->minus($request->sum);
        elseif($request->type == 'plus' && $request->sum)
            $balance->plus($request->sum);
        else
            return response()->json(
                array(
                    'code' => 400,
                    'error' => 'Не указан тип или сумма операции',
                )
            );

        return response()->json(
            array(
                'code' => 200
            )
        );
    }

    public function payment(Request $request)
    {
        if($request->payer) {
            $tenant = tenant('id');
            $dt = new \DateTime;
            $formatter = new \IntlDateFormatter(
                'ru_RU',
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::LONG
            );
            $formatter->setPattern('d MMMM yyyy');
            $date = $formatter->format($dt);
            $payer = \App\Models\Requisite::find($request->payer);
            if(!$payer) {
                return response()->json(
                    array(
                        'code' => 404,
                        'error' => 'Плательщик не найден',
                    )
                );
            }
            $invoice = new \App\Models\Invoice;
            $invoice->save();
            $invoice->name = 'Счет на оплату №'.$invoice->id.' от '.$date;
            $invoice->requisite_id = $payer->id;
            $invoice->sum = $request->sum;
            //return save('/path-to/my_stored_file.pdf')->stream('download.pdf');
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', array(
                'services' => array(
                    array(
                        'name' => 'Пополнение баланса',
                        'count' => 1,
                        'price' => $invoice->sum
                    )
                ),
                'invoice' => $invoice,
                'payer' => $payer,
            ));
            $disk = \Storage::disk('public');
            $time = date('dmYHis');
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
            $invoice->photo = json_encode([
                "id" => $document->id,
                "name" => $document->name,
                "url" => 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path,
                "file" => 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path,
                "extension" => "pdf",
                "sort" => 0
            ]);
            $invoice->save();

            return response()->json([
                'url' => 'https://'.tenant('id').'.compas.pro/storage/tenant'.tenant('id').'/app/public/invoices/'.$time.$invoice->id.'/invoice.pdf'
            ]);

            
            //return $pdf->download('document.pdf');
        } else {
            $client = new Client();
            $client->setAuth('788648', 'test__OjPZTLN1HNxhvow7bwVTiXwF7oHu5AUQ_IrqbgKWp4');
            $idempotenceKey = uniqid('', true);
            
            $sum = $request->sum;

            $response = $client->createPayment(
              array(
                  'amount' => array(
                      'value' => $sum,
                      'currency' => 'RUB',
                  ),
                  'payment_method_data' => array(
                      'type' => 'bank_card',
                  ),
                  "metadata" => array(
                    "account_id"=> tenant('id'),
                    'payer_id' => $request->payer,
                    'sum' => $sum
                  ),
                  'capture' => true,
                  'confirmation' => array(
                      'type' => 'redirect',
                      'return_url' => 'http://compas.plusmario.beget.tech/settings',//'https://'.tenant('id').'.compas.pro.com/status-order?order='.$request->order.'&type='.$request->type,
                  ),
                  'description' => 'Пополнение баланса',
              ),
              $idempotenceKey
            );
            //https://opt6.ru/bitrix/tools/sale_ps_result.php
            // $balance = Balance::where('account_id', \Auth::user()->account_id)->first();
            // $balance->plus($request->sum);

            $confirmationUrl = $response->getConfirmation()->getConfirmationUrl();

            return response()->json(['url' => $confirmationUrl]);//redirect()->away($confirmationUrl);
        }
        
    }

}