<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use App\Models\Balance;
use App\Models\BalanceOperation;
use YooKassa\Client;

class BalanceController extends Controller
{
    public function index()
    {
        $balance = Balance::where('account_id', \Auth::user()->account_id)->first();
        if (request()->date)
            $date = request()->date;
        else {
            $date = date('d.m.Y');
            $date = date('01.m.Y', strtotime($date)).'-'. date('t.m.Y', strtotime($date));
        }
        if (!strstr($date, '-')) {
            $date_end = $date;
        } else {
            $d = explode('-', $date);
            $date = $d[0];
            $date_end = $d[1];
        }
        //dd(date("Y-m-d", strtotime($date)));
        if ($date == $date_end)
            $full_date = $date;
        else
            $full_date = $date.'-'.$date_end;

        $operations = BalanceOperation::where('balance_id', $balance->id);

        if($date != $date_end) {
            $from = $date;
            $to = $date_end;
            if ($from != $to) {
                $operations->whereBetween('created_at', [date("Y-m-d", strtotime($from)), date("Y-m-d", strtotime("+1 day", strtotime($to)))]);
            } else {
                $operations->whereDate('created_at',date("Y-m-d", strtotime($date)));
            }
        } else {
            $operations->whereDate('created_at',date("Y-m-d", strtotime($date)));
        }
        $operations = $operations_sum = $operations->orderBy('date', 'desc');
        $operations = $operations->get();
        $total_sum = $operations_sum->where('type', 'minus')->sum('sum');

        return view('balance.index', compact('balance', 'operations', 'date', 'date_end', 'full_date', 'total_sum'));
    }

    public function payment(Request $request)
    {
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
                "account_id"=> tenant('id')
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
        
        // $balance = Balance::where('account_id', \Auth::user()->account_id)->first();
        // $balance->plus($request->sum);

        $confirmationUrl = $response->getConfirmation()->getConfirmationUrl();

        return redirect()->away($confirmationUrl);
    }

    public function plus(Request $request)
    {
        $balance = Balance::where('account_id', \Auth::user()->account_id)->first();
        $balance->plus($request->sum);
    }

    public function minus(Request $request)
    {
        $balance = Balance::where('account_id', \Auth::user()->account_id)->first();
        $balance->minus($request->sum);
    }
}