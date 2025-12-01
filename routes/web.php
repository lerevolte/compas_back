<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Services\CrudService;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\NotificationEventType;

Route::group(['middleware' => ['web']], function() {
    Route::match(['get', 'post'],'/set_fcm_token', function(Request $request) {
        if($request->userId) {
            info('account '.$request->accountId);
            $tenant = \App\Models\Tenant::find($request->accountId);
            info('tenant '.$tenant->id);
            $tenant->run(function () use ($request) {
                info('token tenant ');

                $user = \App\Models\User::find($request->userId);
                $devices = [];
                info($user->device_id);
                if($user->device_id)
                    $devices = json_decode($user->device_id, true);
                info($devices);
                if(!in_array($request->deviceId, $devices)) {
                    info('add device');
                    $devices[] = $request->deviceId;
                }
                info($devices);
                $tokens = [];
                if($user->token)
                    $tokens = json_decode($user->token, true);
                if(!in_array($request->token, $tokens))
                    $tokens[] = $request->token;

                    $user->token = json_encode($tokens);
                    $user->device_id = json_encode($devices);

                    $user->app_version = $request->version;
                    // $user->permission_geo = $request->permissionLocations;
                    // $user->permission_notification = $request->permissionNotifications;
                    $user->save();
            });
        }
        
        
    });
    Route::match(['get','post'], '/payment/notification_check', function(Request $request) {
         info('moneta check');
    });
    Route::match(['get','post'], '/payment/processing', function(Request $request) {
        echo 'Ваш платеж обрабатывается...';
    });
    Route::match(['get','post'], '/payment/notification2', function(Request $request) {
        $tenants = \App\Models\Tenant::get();
        foreach($tenants as $tenant) {
            $tenant->run(function () use ($tenant) {
                $service = new \App\Services\TenantService(new App\Services\CrudService);
                $service->syncDatabase2($tenant);
            });
        }
        
        // $tenant = \App\Models\Tenant::findOrFail('opt6');
        // $service = new \App\Services\TenantService(new App\Services\CrudService);
        // $service->syncDatabase($tenant);
        die();
    });
    Route::match(['get','post'], '/payment/notification', function(Request $request) {
       
        $method = $request->paymentSystem_unitId;
        if($request->paymentSystem_unitId == '12299232')
            $comission = (float)$request->MNT_AMOUNT*0.01;
        else
            $comission = (float)$request->MNT_AMOUNT*0.027;
        $payment = \DB::table('payments')->where([
            'transaction_id' => $request->MNT_TRANSACTION_ID,
            'status' => 'success'
        ])->first();
        if($payment) {
            info('just success');
            return 'SUCCESS';
        }
        $payment = \DB::table('payments')->where([
            'transaction_id' => $request->MNT_TRANSACTION_ID,
            'status' => 'processing'
        ])->first();
        if($payment) {
            $amount_wo_moneta = $payment->amount;
            $transaction_id = $payment->transaction_id.time();
            \DB::table('payments')->where([
                'transaction_id' => $request->MNT_TRANSACTION_ID,
            ])->update([
                'status' => 'success',
                'amount' => $request->MNT_AMOUNT,
                'operation_id' => $request->MNT_OPERATION_ID,
                'moneta_comission' => $comission
            ]);
            $payment->operation_id = $request->MNT_OPERATION_ID;
            $payment->amount = $request->MNT_AMOUNT;
            if($payment->account_id && !$payment->async_id) {
                $tenant = \App\Models\Tenant::find($payment->account_id);
                $sum = (float)$request->MNT_AMOUNT;
                $s = $tenant->run(function ($tenant) use ($payment, $comission, $amount_wo_moneta){
                    $fine = \App\Models\GibddFine::find($payment->fine_id);
                    
                    $field_payment = json_decode($fine->payment, true);
                    $field_payment['value'] = $amount_wo_moneta;
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

                    $settings = \App\Models\Settings::get(true);
                    $data = $fine->getData(array(), $settings);
                    \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);

                    \Modules\Gibdd\Entities\Module::paymentRequest($payment, $fine, $comission);

                    return 'SUCCESS';
                });

                return 'SUCCESS';
            } elseif($payment->fine_data && !$payment->async_id) {
                $fine = json_decode($payment->fine_data);
                \Modules\Gibdd\Entities\Module::paymentRequest($payment, $fine, $comission);

                return 'SUCCESS';
            }
            
        } else {
            return response()->json(['ok' => $request->MNT_TRANSACTION_ID]);
        }

        
    });
    Route::match(['get','post'], '/balance/notification', function(Request $request) {
        if($request->MNT_CUSTOM1 && $tenant = \App\Models\Tenant::find($request->MNT_CUSTOM1)) {
            $sum = $request->MNT_CUSTOM2;
            $s = $tenant->run(function ($tenant) use ($sum){
                info('пополняем баланс аккаунту '.$sum);
                $balance = \App\Models\Balance::first();
                $balance->plus($sum, 'Пополнение баланса для оплаты услуг');
            });

            return 'SUCCESS';
        } else {
            return 'FAIL';
        }
        
    });
    Route::get('/404', function(Request $request) {
        return view('errors.404');
    });
    Route::get('/sitemap_articles.xml', function () {
        $articles = \App\Models\Article::get();
        $output = '';
        $output_articles = '';
        foreach($articles as $article) {
            $slug = json_decode($article->slug, true);
            if(isset($slug['value']))
                $slug = $slug['value'];
            else
                $slug = $article->slug;
            $datetime = new \DateTime($article->updated_at);
            $lastmod = $datetime->format('Y-m-d\TH:i:sP');

            $output_articles.='<url>
                <loc>https://compas.pro/articles/'.$slug.'</loc>
                <lastmod>'.$lastmod.'</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.7</priority>
              </url>';
        }
        $output = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">";
        $output.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
              '.$output_articles.'
            </urlset>';
        return response($output, 200, [
            'Content-Type' => 'application/xml'
        ]);

        \File::put(public_path().'/sitemap_articles.xml', $output);

        return Illuminate\Support\Facades\Response::make($output, 200);//->header('Content-Type', 'application/xml');
      

    });
    Route::get('/sitemap_faq.xml', function () {
        $articles = \App\Models\Faq::get();
        $output = '';
        $output_articles = '';
        foreach($articles as $article) {
            $slug = json_decode($article->slug, true);
            if(isset($slug['value']))
                $slug = $slug['value'];
            else
                $slug = $article->slug;
            $datetime = new \DateTime($article->updated_at);
            $lastmod = $datetime->format('Y-m-d\TH:i:sP');

            $output_articles.='<url>
                <loc>https://compas.pro/questions/'.$slug.'</loc>
                <lastmod>'.$lastmod.'</lastmod>
                <changefreq>monthly</changefreq>
                <priority>0.5</priority>
              </url>';
        }
        $output = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">";
        $output.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
              '.$output_articles.'
            </urlset>';

        return response($output, 200, [
            'Content-Type' => 'application/xml'
        ]);

        \File::put(public_path().'/sitemap_faq.xml', $output);

        return Illuminate\Support\Facades\Response::make($output, 200);
        
      

    });

    Route::get('/sitemap_knowledge.xml', function () {
        $articles = \App\Models\Knowledge::get();
        $output = '';
        $output_articles = '';
        foreach($articles as $article) {
            $slug = json_decode($article->slug, true);
            if(isset($slug['value']))
                $slug = $slug['value'];
            else
                $slug = $article->slug;
            $datetime = new \DateTime($article->updated_at);
            $lastmod = $datetime->format('Y-m-d\TH:i:sP');

            $output_articles.='<url>
                <loc>https://compas.pro/knowledge/'.$slug.'</loc>
                <lastmod>'.$lastmod.'</lastmod>
                <changefreq>monthly</changefreq>
                <priority>0.5</priority>
              </url>';
        }
        $output = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">";
        $output.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
              '.$output_articles.'
            </urlset>';

        return response($output, 200, [
            'Content-Type' => 'application/xml'
        ]);

        \File::put(public_path().'/sitemap_knowledge.xml', $output);

        return Illuminate\Support\Facades\Response::make($output, 200);
        
      

    });
    Route::get('/sitemap_guides.xml', function () {
        $articles = \App\Models\Guide::get();
        $output = '';
        $output_articles = '';
        foreach($articles as $article) {
            $slug = json_decode($article->slug, true);
            if(isset($slug['value']))
                $slug = $slug['value'];
            else
                $slug = $article->slug;
            $datetime = new \DateTime($article->updated_at);
            $lastmod = $datetime->format('Y-m-d\TH:i:sP');

            $output_articles.='<url>
                <loc>https://compas.pro/guides/'.$slug.'</loc>
                <lastmod>'.$lastmod.'</lastmod>
                <changefreq>monthly</changefreq>
                <priority>0.5</priority>
              </url>';
        }
        $output = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">";
        $output.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
              '.$output_articles.'
            </urlset>';

        return response($output, 200, [
            'Content-Type' => 'application/xml'
        ]);

        \File::put(public_path().'/sitemap_guides.xml', $output);

        return Illuminate\Support\Facades\Response::make($output, 200);

    });
    Route::get('{path}', App\Http\Controllers\SpaController::class)->where('path', '^(?!api).*$');  
});