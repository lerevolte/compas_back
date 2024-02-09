<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Providers\CRestCurrent;
use App\Helpers\ValueHelper;
use App\Models\Field;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \YooKassa\Model\Notification\NotificationSucceeded;
use \YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\Notification\NotificationEventType;


if(!function_exists('get_settings')) {
    function get_settings() {
        return \App\Models\Settings::get();
    };
};
if(!function_exists('get_validators')) {
    function get_validators() {
        return \App\Models\Settings::validators();
    };
};
if(!function_exists('array_sort_by_column')) {
    function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key => $row) {
            $sort_col[$key] = $row[$col];
        }

        array_multisort($sort_col, $dir, $arr);
    }
}
Route::group(['middleware' => ['web']], function() {
    Route::get('/', function() {
        
        return view('home');
    });
    Route::get(
        'pdf/{document}', 
        [App\Http\Controllers\PdfController::class, 'index']
    );
    Route::match(['get', 'post'],
        'create_payment', function(Request $request) {
            $source = file_get_contents('php://input');
            $requestBody = json_decode($source, true);
            try {
              $notification = ($requestBody['event'] === NotificationEventType::PAYMENT_SUCCEEDED)
                ? new NotificationSucceeded($requestBody)
                : new NotificationWaitingForCapture($requestBody);
                $payment = $notification->getObject();
                if ($payment->status == 'succeeded') {
                    $sum = $payment->metadata->sum;
                    info($sum);
                    $tenant = \App\Models\Tenant::where('id', $payment->metadata->account_id)->first();
                    $tenant->run(function () use ($sum) {
                        $balance = \App\Models\Balance::first();
                        $balance->plus($sum);
                    });
                }
                // $payment->metadata->account_id
            } catch (Exception $e) {
                $payment = $notification->getObject();
            }
            
            // echo 1;

    });
    
    Route::get('/contacts', function() {
        
        return view('contacts');
    });
    Route::get('/prices', function() {
        
        return view('prices');
    });
    Route::get('/authentication', function() {
        
        return view('login');
    });
    Route::get('/registration', function() {
        
        return view('registration');
    });
    Route::get('/privacy', function() {
        return view('privacy');
    });
    Route::get('/get_order', function(Request $request) {
        $ch = curl_init();
        $url = 'http://srv.vaic.ru:11501/unf5012096553/hs/compas/getorder/?ref='.$request->barcode;
        info('barcode '.$request->barcode);
        curl_setopt($ch, CURLOPT_URL, $url ); // отправляем на
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "compas:bN3pacef");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
        curl_setopt($ch, CURLOPT_POST, 0); // использовать данные в post
        $data = curl_exec($ch);
        curl_close($ch);
        $products = array();
        $data = json_decode($data, true);

        return response()->json($data);
    });
});

Route::middleware([
    //'auth:api',
    'auth:api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    ])->prefix('api')->group(function () {
    Route::name('api.')->namespace('App\Http\Controllers\Api')->group(function () {
        Route::post('b24_config', function (Request $request) {
            $user = \Auth::user();
            $user->bitrix24_config = $request->config;
            $user->save();

            return response()->json($user);
        });
        Route::get('test', function () {
            return 'test';
        });

        Route::post(
            'auth', 
            [App\Http\Controllers\Api\AuthController::class, 'login']
        );

        Route::get(
            'sidebar/get', 
            [App\Http\Controllers\Api\SidebarController::class, 'get']
        );
        Route::post(
            'sidebar/set', 
            [App\Http\Controllers\Api\SidebarController::class, 'set']
        );
        Route::post(
            'sidebar/set/all', 
            [App\Http\Controllers\Api\SidebarController::class, 'set_all']
        );
        Route::post(
            'sidebar/set/{role_id}', 
            [App\Http\Controllers\Api\SidebarController::class, 'set_role']
        );

        Route::get(
            'sidebar/{id?}', 
            [App\Http\Controllers\Api\SidebarController::class, 'list']
        );
        Route::post('sidebar/change-sort', [App\Http\Controllers\Api\SidebarController::class, 'sort'])->name('sidebar.sort');
        Route::put(
            'sidebar/{id}', 
            [App\Http\Controllers\Api\SidebarController::class, 'update']
        );
        

        Route::get(
            'balance', 
            [App\Http\Controllers\Api\BalanceController::class, 'index']
        );
        Route::put('balance', [
            'uses' => 'App\Http\Controllers\BalanceController@change'
        ]);
        Route::post(
            'balance/payment', 
            [App\Http\Controllers\Api\BalanceController::class, 'payment']
        );

        Route::get(
            'roles', 
            [App\Http\Controllers\Api\RoleController::class, 'list']
        );
        Route::post(
            'roles', 
            [App\Http\Controllers\Api\RoleController::class, 'store']
        );
        Route::get(
            'roles/{id}', 
            [App\Http\Controllers\Api\RoleController::class, 'show']
        );
        Route::get(
            'roles/{id}/modules', 
            [App\Http\Controllers\Api\RoleController::class, 'show_modules']
        );
        Route::put(
            'roles/{id}', 
            [App\Http\Controllers\Api\RoleController::class, 'update']
        );
        Route::delete('roles/{id}', [App\Http\Controllers\Api\RoleController::class, 'destroy']);

        Route::get(
            'requisites/{company_id?}', 
            [App\Http\Controllers\Api\RequisiteController::class, 'list']
        );
        Route::post(
            'requisites', 
            [App\Http\Controllers\Api\RequisiteController::class, 'store']
        );
        Route::put(
            'requisites/{id}', 
            [App\Http\Controllers\Api\RequisiteController::class, 'update']
        );
        Route::delete('requisites/{id}', [App\Http\Controllers\Api\RequisiteController::class, 'destroy']);



        Route::get(
            'fields/{model}', 
            [App\Http\Controllers\Api\FieldController::class, 'list']
        )->name('fields.list');

        Route::get(
            'tables/order_products', 
            [App\Http\Controllers\Api\TableController::class, 'get_order_products']
        )->name('tables.get_order_products');
        Route::get(
            'tables/{model}', 
            [App\Http\Controllers\Api\TableController::class, 'get']
        )->name('tables.get');

        Route::post(
            'tables/{model}', 
            [App\Http\Controllers\Api\TableController::class, 'set']
        )->name('tables.set');

        Route::post(
            'tables/{model}/all', 
            [App\Http\Controllers\Api\TableController::class, 'set_all']
        )->name('tables.set');
        Route::post(
            'tables/{model}/role/{role_id}', 
            [App\Http\Controllers\Api\TableController::class, 'set_role']
        )->name('tables.set');

        

        Route::get('profile', [App\Http\Controllers\Api\ProfileController::class, 'show'])->name('profile.show');

        Route::get('users', [App\Http\Controllers\Api\UserController::class, 'list'])->name('users.list');
        Route::get('users/{user}/roles', [App\Http\Controllers\Api\UserController::class, 'list'])->name('user.roles');
        //Route::get('roles/', [App\Http\Controllers\Api\RoleController::class, 'list'])->name('roles.list');
        Route::get('permissions', [App\Http\Controllers\Api\UserController::class, 'permissions'])->name('users.permissions');
        Route::post('users/{user}/update_b24', [App\Http\Controllers\Api\UserController::class, 'update_b24'])->name('users.update_b24');

        Route::get(
            'filters/{model}', 
            [App\Http\Controllers\Api\FilterController::class, 'list']
        )->name('filters.list');
        Route::post('filters/{model}', [App\Http\Controllers\Api\FilterController::class, 'store'])->name('filters.store');
        Route::put('filters/{model}/{id}', [App\Http\Controllers\Api\FilterController::class, 'update'])->name('filters.update');
        Route::delete('filters/{model}/{id}', [App\Http\Controllers\Api\FilterController::class, 'delete'])->name('filters.delete');
        Route::post('filters/{slug}/change-sort', [App\Http\Controllers\Api\FilterController::class, 'sort'])->name('filters.sort');
        //Route::post('filters/{model}/change-sort/{filter}', [App\Http\Controllers\Api\FilterController::class, 'changeSort'])->name('filters.change_sort');
        Route::get(
            'tariffs', 
            [App\Http\Controllers\Api\TariffController::class, 'list']
        )->name('tariffs.list');

        Route::get(
            'objects/test', 
            [App\Http\Controllers\Api\ObjectController::class, 'test']
        )->name('objects.test');
        Route::get(
            'objects/{model}', 
            [App\Http\Controllers\Api\ObjectController::class, 'list']
        )->name('objects.list');
        Route::get(
            'objects/{model}/compose', 
            [App\Http\Controllers\Api\ObjectController::class, 'compose_list']
        )->name('objects.compose_list');
        Route::get(
            'objects/{model}/export', 
            [App\Http\Controllers\Api\ObjectController::class, 'export']
        )->name('objects.export');
        

        //Route::put('objects/{model}/show/{id}', [App\Http\Controllers\Api\ObjectController::class, 'show'])->name('objects.show');
        Route::match(['post','put'], 'objects/{model}/batch', [App\Http\Controllers\Api\ObjectController::class, 'batch'])->name('objects.batch');
        Route::post('objects/{model}', [App\Http\Controllers\Api\ObjectController::class, 'store'])->name('objects.store');

        Route::get('objects/{model}/search', [App\Http\Controllers\Api\ObjectController::class, 'search'])->name('objects.search');
        //Route::delete('objects/{model}/batch', [App\Http\Controllers\Api\ObjectController::class, 'delete'])->name('objects.delete_batch');
        Route::delete('objects/{model}', [App\Http\Controllers\Api\ObjectController::class, 'delete'])->name('objects.delete');
        Route::post('objects/{model}/restore', [App\Http\Controllers\Api\ObjectController::class, 'restore'])->name('objects.restore');
        Route::get('objects/{model}/{id}', [App\Http\Controllers\Api\ObjectController::class, 'show'])->name('objects.show');
        Route::get('objects/{model}/{id}/compose', [App\Http\Controllers\Api\ObjectController::class, 'compose_show'])->name('objects.compose_show');

        Route::put('objects/{model}/{id}', [App\Http\Controllers\Api\ObjectController::class, 'update'])->name('objects.update');
        Route::post('objects/{model}/{id}/copy', [App\Http\Controllers\Api\ObjectController::class, 'copy'])->name('objects.copy');
        Route::get('objects/{model}/{id}/{module}', [App\Http\Controllers\Api\ObjectController::class, 'show_module'])->name('objects.show_module');
        Route::get('objects/{model}/{id}/{module}/compose', [App\Http\Controllers\Api\ObjectController::class, 'compose_show_module'])->name('objects.compose_show_module');

        Route::get('history/{model}/{id}', [App\Http\Controllers\Api\HistoryController::class, 'index']);
        Route::get('history/{model}/{id}/{module?}', [App\Http\Controllers\Api\HistoryController::class, 'index']);
        Route::get('history/table/{model}/{id}', [App\Http\Controllers\Api\HistoryController::class, 'table']);

        Route::post('field_sections/change-sort', [App\Http\Controllers\Api\FieldSectionController::class, 'changeSort']);
        Route::post('field_sections/hide', [App\Http\Controllers\Api\FieldSectionController::class, 'hide']);
        Route::delete('field_sections/{id}', [App\Http\Controllers\Api\FieldSectionController::class, 'delete']);
        Route::post('field_sections', [App\Http\Controllers\Api\FieldSectionController::class, 'store']);
        Route::put('field_sections/{id}', [App\Http\Controllers\Api\FieldSectionController::class, 'update']);

        Route::get('field/{entity}/compare/{module}', [App\Http\Controllers\Api\FieldController::class, 'get_compare'])->name('objects.get_compare');
        Route::post('field/{entity}/compare/{module}', [App\Http\Controllers\Api\FieldController::class, 'set_compare'])->name('objects.set_compare');
        Route::get('field/{field}/modules', [App\Http\Controllers\Api\FieldController::class, 'get_field_modules'])->name('objects.get_field_modules');

        

        Route::post('field/change-sort', [App\Http\Controllers\Api\FieldController::class, 'changeSort']);
        Route::post('field/hide/{id}', [App\Http\Controllers\Api\FieldController::class, 'hide']);
        Route::post('field/hide_batch', [App\Http\Controllers\Api\FieldController::class, 'hide_batch']);
        Route::delete('field/{id}', [App\Http\Controllers\Api\FieldController::class, 'delete']);
        Route::post('field/status', [App\Http\Controllers\Api\FieldController::class, 'status_store']);
        Route::post('field', [App\Http\Controllers\Api\FieldController::class, 'store']);
        Route::put('field/{id}', [App\Http\Controllers\Api\FieldController::class, 'update']);

        Route::post('files/store', [App\Http\Controllers\Api\FileController::class, 'store']);

        Route::get('entities', [App\Http\Controllers\Api\EntityController::class, 'list']);
        Route::get('entities/{slug}/menu', [App\Http\Controllers\Api\EntityController::class, 'get_menu']);
        Route::put('entities/{slug}/menu', [App\Http\Controllers\Api\EntityController::class, 'set_menu']);
        Route::put('entities/{slug}/menu/all', [App\Http\Controllers\Api\EntityController::class, 'set_menu_all']);
        Route::put('entities/{slug}/menu/role/{role_id}', [App\Http\Controllers\Api\EntityController::class, 'set_menu_role']);


        Route::post('entities/enable', [App\Http\Controllers\Api\EntityController::class, 'enable']);
        Route::post('entities/disable', [App\Http\Controllers\Api\EntityController::class, 'disable']);

        Route::get('map/suggest', [App\Http\Controllers\Api\MapController::class, 'suggest']);
        Route::get('map/geocode', [App\Http\Controllers\Api\MapController::class, 'geocode']);
        
        
        Route::get('modules/categories', [App\Http\Controllers\Api\ModuleController::class, 'categories'])->name('modules.categories');
        Route::get('modules/installed', [App\Http\Controllers\Api\ModuleController::class, 'installed'])->name('modules.installed');
        Route::get('modules/{slug?}', [App\Http\Controllers\Api\ModuleController::class, 'list'])->name('modules.list');
        Route::put('modules/{slug}', [App\Http\Controllers\Api\ModuleController::class, 'update'])->name('modules.update');

        Route::get('modules/{slug}/show', [App\Http\Controllers\Api\ModuleController::class, 'show'])->name('modules.show');
        Route::post('modules/{slug}/install', [App\Http\Controllers\Api\ModuleController::class, 'install'])->name('modules.install');
        Route::post('modules/{slug}/uninstall', [App\Http\Controllers\Api\ModuleController::class, 'uninstall'])->name('modules.uninstall');
        Route::get('modules/{entity}/{id}/{module}/check', [App\Http\Controllers\Api\ModuleController::class, 'checkWork'])->name('modules.check_work');
        
        Route::get(
            'trash', 
            [App\Http\Controllers\Api\TrashController::class, 'index']
        );

        Route::get('routes', [App\Http\Controllers\Api\RouteController::class, 'list'])->name('routes.list');
        Route::post('routes', [App\Http\Controllers\Api\RouteController::class, 'store'])->name('routes.store');
        Route::put('routes/batch', [App\Http\Controllers\Api\RouteController::class, 'batch'])->name('routes.batch');
        Route::delete('routes', [App\Http\Controllers\Api\RouteController::class, 'delete']);
        Route::get('routes/{id}/tasks', [App\Http\Controllers\Api\RouteController::class, 'tasks'])->name('routes.tasks');
        Route::put('routes/{id}/tasks', [App\Http\Controllers\Api\RouteController::class, 'update_tasks'])->name('routes.update_tasks');
        Route::get('cache_changes', function(Request $request){
            if($request->time)
                $time = date('Y-m-d H:i:s', (int)$request->time);
            else
                $time = date('Y-m-d H:i:s');
            $urls = \DB::table('local_cache')->where('updated_at', '>=', $time)->where('user_id', \Auth::user()->id)->get();;
            return response()->json($urls);
        });
    });
});


// Route::middleware([
//     'api',
//     InitializeTenancyByDomain::class,
//     PreventAccessFromCentralDomains::class,
// ])->group(function () {

//     Route::get('test', function () {
//         echo 'test';
//        return 'test ';
//     });
    // Route::get(
    //     'objects/{model}', 
    //     [App\Http\Controllers\Api\ObjectController::class, 'list']
    // )->name('objects.list');

    // Route::get(
    //     'objects/{model}/edit', 
    //     [App\Http\Controllers\Api\ObjectController::class, 'edit_list']
    // )->name('edit_list');
// });
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
   
    Route::post(
        'api/auth', 
        [App\Http\Controllers\Api\AuthController::class, 'login']
    );
    Route::get('/get_count_funds', function(Request $request) {
        $model = Modules\EmergencyFund\Entities\FundRecord::find($request->id);

        $next_records = Modules\EmergencyFund\Entities\FundRecord::whereDate('date', '>', $model->date)->get();
        return count($next_records);
    });
    Route::match(['get', 'post'], '/b24', function(Illuminate\Http\Request $request) {
        //info('EVENT TEST');
        die();
        $user = \Auth::user();//\Auth::user();
        $config = json_decode($user->bitrix24_config, true);
        $deal_id = 131559;
        $response = Http::post($config['webhook'].'crm.deal.get', ['ID' => $deal_id])->collect();
        //info($response['result']['STAGE_ID'].' = '.$config['stage_id']);


        $compas_order_fields = array();
        $field_ids = array();
        foreach($config['order_fields'] as $field) {
            $field_ids[] = $field['value'];
        }
        $compas_order_fields = \DB::table('data_rows')->whereIn('id', $field_ids)->pluck('field', 'id')->toArray();
        $compas_order_fields_plural = \DB::table('data_rows')->whereIn('id', $field_ids)->pluck('is_plural', 'id')->toArray();


        $compas_product_fields = array();
        $field_ids = array();
        foreach($config['product_fields'] as $b24_code => $field) {
            $field_ids[] = $field;
        }
        $compas_product_fields = \DB::table('data_rows')->whereIn('id', $field_ids)->pluck('field', 'id')->toArray();


        $compas_client_fields = array();
        $field_ids = array();
        foreach($config['client_fields'] as $b24_code => $field) {
            $field_ids[] = $field['value'];
        }
        $compas_client_fields = \DB::table('data_rows')->whereIn('id', $field_ids)->pluck('field', 'id')->toArray();

            $deal = $response['result'];
            $data = \App\Models\Order::where('number', $deal_id)->first();
            //info($deal_id);
            if(!$data)
                $data = new \App\Models\Order;
            $data->number = $deal_id;
            $data->save();
            //clients
            $response = Http::post($config['webhook'].'crm.deal.contact.items.get', ['ID' => $deal_id])->collect();
            if(isset($response['result'])) {
                //info('contacts');
                $contacts_ids = array();
                foreach($response['result'] as $c) {
                    $contacts_ids[] = $c['CONTACT_ID'];
                    $response_contact = Http::post($config['webhook'].'crm.contact.get',
                        [
                            'id' => $c['CONTACT_ID']
                        ]
                    );
                    if(isset($response_contact['result'])) {
                        $contact = $response_contact['result'];
                        $client = \Modules\Clients\Entities\Client::where('id_b24', $contact['ID'])->first();
                        if(!$client) {
                            $client = new \Modules\Clients\Entities\Client();
                            //$prod->name = $products_data[$product['PRODUCT_ID']]['NAME'];
                        }
                        //info($compas_client_fields);
                        foreach($config['client_fields'] as $b24_code => $field) {
                            $values = null;
                            if(isset($field['field_values'])) {
                                $values = array();
                                foreach($field['field_values'] as $field_value) {
                                    $values[$field_value['name']] = $field_value['value'];
                                };
                            }
                            if($values && isset($values[$contact[$b24_code]])) {
                                $client->{$compas_client_fields[$field['value']]} = $values[$contact[$b24_code]];
                            } else {
                                // info($compas_client_fields);
                                // info($contact);
                                $client->{$compas_client_fields[$field['value']]} = isset($contact[$b24_code]) && is_array($contact[$b24_code]) ? $contact[$b24_code][0]['VALUE'] : $contact[$b24_code];
                            }
                        }
                        $client->id_b24 = $contact['ID'];
                        $client->save();
                        //info($contact['result']);
                    }
                }
                // $response_contacts = Http::post(
                //     $config['webhook'].'crm.contact.list',
                //     [
                //         'order' => ['ID' => 'ASC'],
                //         'filter' => [
                //             'ID' => $contacts_ids
                //         ],
                //         'select' => ['*', 'PROPERTY_*']
                //     ]
                // );

                // if(isset($response_contacts['result'])) {
                //     info($response_contacts['result']);
                // }
                //info($response['result']);
            }
            //clients

            $response_products = Http::post($config['webhook'].'crm.deal.productrows.get', [
                'id' => $deal['ID'],
            ])->collect();
            $delivery_price = 0;
            $all_weight = 0;
            $products = array();
            if(isset($response_products['result'])) {
                $arItems = array();
                $product_ids = array();
                foreach($response_products['result'] as $product) {
                    if ($product['PRODUCT_ID'] != 113 && $product['PRODUCT_ID'] != 111 && $product['PRODUCT_ID']) {
                        $product_ids[] = $product['PRODUCT_ID'];
                    }
                }
                $result_products = Http::post(
                    $config['webhook'].'crm.product.list',
                    [
                        'order' => ['ID' => 'ASC'],
                        'filter' => [
                            'ID' => $product_ids
                        ],
                        'select' => ['*', 'PROPERTY_*']
                    ]
                );
                $products_data = array();
                if(isset($result_products['result'])) {
                    foreach($result_products['result'] as $product) {
                        $products_data[$product['ID']] = $product;
                    }
                }
                foreach($response_products['result'] as $product) {
                    if ($product['PRODUCT_ID'] != 113 && $product['PRODUCT_ID'] != 111 && $product['PRODUCT_ID']) {
                        $weight = 0;
                        $prod = \Modules\Products\Entities\Product::where('id_b24', $product['PRODUCT_ID'])->first();
                        if(!$prod) {
                            $prod = new \Modules\Products\Entities\Product();
                            //$prod->name = $products_data[$product['PRODUCT_ID']]['NAME'];
                        }
                        $prod->id_b24 =  $product['PRODUCT_ID'];
                        foreach($config['product_fields'] as $b24_code => $field) {
                            $values = null;
                            // if(isset($field['field_values'])) {
                            //     $values = array();
                            //     foreach($field['field_values'] as $field_value) {
                            //         $values[$field_value['name']] = $field_value['value'];
                            //     };
                            // }
                            // if($values && isset($values[$deal[$b24_code]])) {

                            //     $data->{$compas_product_fields[$field['value']]} = $values[$deal[$b24_code]];
                            // } else {
                                $prod->{$compas_product_fields[$field]} = $products_data[$product['PRODUCT_ID']][$b24_code];
                            //}
                        }
                        $prod->save();
                        $all_weight = $all_weight + $prod->weight*$product['QUANTITY'];
                        
                        if(!array_key_exists($prod['name'], $arItems))
                            $arItems[$prod['name']] = $product['QUANTITY'];
                        else
                            $arItems[$prod['name'].'.'] = $product['QUANTITY'];
                        $products[] = array(
                            'name' => $prod['name'],
                            'price' => $product['PRICE'],
                            'count' => $product['QUANTITY'],
                            'weight' => $prod->weight,
                            'sum' => $product['PRICE']*$product['QUANTITY']
                        );
                    } else {
                        $delivery_price = $delivery_price + (float)$product['PRICE']*$product['QUANTITY'];
                    }
                    
                }
            }
            $data->products = json_encode($products, JSON_UNESCAPED_UNICODE);
            //print_r($data->products);
            
            foreach($config['order_fields'] as $b24_code => $field) {
                $values = null;
                if(isset($field['field_values'])) {
                    $values = array();
                    foreach($field['field_values'] as $field_value) {
                        $values[$field_value['name']] = $field_value['value'];
                    };
                }
                if($values && isset($values[$deal[$b24_code]])) {
                    if($compas_order_fields_plural[$field['value']])
                        $data->{$compas_order_fields[$field['value']]} = array($values[$deal[$b24_code]]);
                    else
                        $data->{$compas_order_fields[$field['value']]} = $values[$deal[$b24_code]];
                    echo '<b>1'.$compas_order_fields[$field['value']].':</b>'.$values[$deal[$b24_code]].'<br>';
                } else {
                    $data->{$compas_order_fields[$field['value']]} = $deal[$b24_code];
                    echo '<b>2'.$compas_order_fields[$field['value']].':</b>'.$deal[$b24_code].'<br>';
                }
            }
            if(isset($client) && $client)
                $data->client_id = $client->id;
            $data->save();
    });
    Route::match(['get', 'post'], '/b24_crm_deal_update', function(Illuminate\Http\Request $request) {
        //info('EVENT TEST');
        die();
        $user = \App\Models\User::where('api_token', $request->token)->first();//\Auth::user();
        $config = json_decode($user->bitrix24_config, true);
        $deal_id = $request['data']['FIELDS']['ID'];
        $response = Http::post($config['webhook'].'crm.deal.get', ['ID' => $deal_id])->collect();
        //info($response['result']['STAGE_ID'].' = '.$config['stage_id']);


        $compas_order_fields = array();
        $field_ids = array();
        foreach($config['order_fields'] as $field) {
            $field_ids[] = $field['value'];
        }
        $compas_order_fields = \DB::table('data_rows')->whereIn('id', $field_ids)->pluck('field', 'id')->toArray();
        $compas_order_fields_plural = \DB::table('data_rows')->whereIn('id', $field_ids)->pluck('is_plural', 'id')->toArray();


        $compas_product_fields = array();
        $field_ids = array();
        foreach($config['product_fields'] as $b24_code => $field) {
            $field_ids[] = $field;
        }
        $compas_product_fields = \DB::table('data_rows')->whereIn('id', $field_ids)->pluck('field', 'id')->toArray();


        $compas_client_fields = array();
        $field_ids = array();
        foreach($config['client_fields'] as $b24_code => $field) {
            $field_ids[] = $field['value'];
        }
        $compas_client_fields = \DB::table('data_rows')->whereIn('id', $field_ids)->pluck('field', 'id')->toArray();

        if(isset($response['result']) && $response['result']['STAGE_ID'] == $config['stage_id']) {
            $deal = $response['result'];
            $data = \App\Models\Order::where('number', $deal_id)->first();
            //info($deal_id);
            if(!$data)
                $data = new \App\Models\Order;
            $data->number = $deal_id;
            $data->save();
            //clients
            $response = Http::post($config['webhook'].'crm.deal.contact.items.get', ['ID' => $deal_id])->collect();
            if(isset($response['result'])) {
                //info('contacts');
                $contacts_ids = array();
                foreach($response['result'] as $c) {
                    $contacts_ids[] = $c['CONTACT_ID'];
                    $response_contact = Http::post($config['webhook'].'crm.contact.get',
                        [
                            'id' => $c['CONTACT_ID']
                        ]
                    );
                    if(isset($response_contact['result'])) {
                        $contact = $response_contact['result'];
                        $client = \Modules\Clients\Entities\Client::where('id_b24', $contact['ID'])->first();
                        if(!$client) {
                            $client = new \Modules\Clients\Entities\Client();
                            //$prod->name = $products_data[$product['PRODUCT_ID']]['NAME'];
                        }
                        //info($compas_client_fields);
                        foreach($config['client_fields'] as $b24_code => $field) {
                            $values = null;
                            if(isset($field['field_values'])) {
                                $values = array();
                                foreach($field['field_values'] as $field_value) {
                                    $values[$field_value['name']] = $field_value['value'];
                                };
                            }
                            if($values && isset($values[$contact[$b24_code]])) {
                                $client->{$compas_client_fields[$field['value']]} = $values[$contact[$b24_code]];
                            } else {
                                // info($compas_client_fields);
                                // info($contact);
                                $client->{$compas_client_fields[$field['value']]} = isset($contact[$b24_code]) && is_array($contact[$b24_code]) ? $contact[$b24_code][0]['VALUE'] : $contact[$b24_code];
                            }
                        }
                        $client->id_b24 = $contact['ID'];
                        $client->save();
                        //info($contact['result']);
                    }
                }
                // $response_contacts = Http::post(
                //     $config['webhook'].'crm.contact.list',
                //     [
                //         'order' => ['ID' => 'ASC'],
                //         'filter' => [
                //             'ID' => $contacts_ids
                //         ],
                //         'select' => ['*', 'PROPERTY_*']
                //     ]
                // );

                // if(isset($response_contacts['result'])) {
                //     info($response_contacts['result']);
                // }
                //info($response['result']);
            }
            //clients

            $response_products = Http::post($config['webhook'].'crm.deal.productrows.get', [
                'id' => $deal['ID'],
            ])->collect();
            $delivery_price = 0;
            $all_weight = 0;
            $products = array();
            if(isset($response_products['result'])) {
                $arItems = array();
                $product_ids = array();
                foreach($response_products['result'] as $product) {
                    if ($product['PRODUCT_ID'] != 113 && $product['PRODUCT_ID'] != 111 && $product['PRODUCT_ID']) {
                        $product_ids[] = $product['PRODUCT_ID'];
                    }
                }
                $result_products = Http::post(
                    $config['webhook'].'crm.product.list',
                    [
                        'order' => ['ID' => 'ASC'],
                        'filter' => [
                            'ID' => $product_ids
                        ],
                        'select' => ['*', 'PROPERTY_*']
                    ]
                );
                $products_data = array();
                if(isset($result_products['result'])) {
                    foreach($result_products['result'] as $product) {
                        $products_data[$product['ID']] = $product;
                    }
                }
                foreach($response_products['result'] as $product) {
                    if ($product['PRODUCT_ID'] != 113 && $product['PRODUCT_ID'] != 111 && $product['PRODUCT_ID']) {
                        $weight = 0;
                        $prod = \Modules\Products\Entities\Product::where('id_b24', $product['PRODUCT_ID'])->first();
                        if(!$prod) {
                            $prod = new \Modules\Products\Entities\Product();
                            //$prod->name = $products_data[$product['PRODUCT_ID']]['NAME'];
                        }
                        $prod->id_b24 =  $product['PRODUCT_ID'];
                        foreach($config['product_fields'] as $b24_code => $field) {
                            $values = null;
                            // if(isset($field['field_values'])) {
                            //     $values = array();
                            //     foreach($field['field_values'] as $field_value) {
                            //         $values[$field_value['name']] = $field_value['value'];
                            //     };
                            // }
                            // if($values && isset($values[$deal[$b24_code]])) {

                            //     $data->{$compas_product_fields[$field['value']]} = $values[$deal[$b24_code]];
                            // } else {
                                $prod->{$compas_product_fields[$field]} = $products_data[$product['PRODUCT_ID']][$b24_code];
                            //}
                        }
                        $prod->save();
                        $all_weight = $all_weight + $prod->weight*$product['QUANTITY'];
                        
                        if(!array_key_exists($prod['name'], $arItems))
                            $arItems[$prod['name']] = $product['QUANTITY'];
                        else
                            $arItems[$prod['name'].'.'] = $product['QUANTITY'];
                        $products[] = array(
                            'name' => $prod['name'],
                            'price' => $product['PRICE'],
                            'count' => $product['QUANTITY'],
                            'weight' => $prod->weight,
                            'sum' => $product['PRICE']*$product['QUANTITY']
                        );
                    } else {
                        $delivery_price = $delivery_price + (float)$product['PRICE']*$product['QUANTITY'];
                    }
                    
                }
            }
            $data->products = json_encode($products, JSON_UNESCAPED_UNICODE);
            //print_r($data->products);
            
            foreach($config['order_fields'] as $b24_code => $field) {
                $values = null;
                if(isset($field['field_values'])) {
                    $values = array();
                    foreach($field['field_values'] as $field_value) {
                        $values[$field_value['name']] = $field_value['value'];
                    };
                }
                if($values && isset($values[$deal[$b24_code]])) {
                    if($compas_order_fields_plural[$field['value']])
                        $data->{$compas_order_fields[$field['value']]} = array($values[$deal[$b24_code]]);
                    else
                        $data->{$compas_order_fields[$field['value']]} = $values[$deal[$b24_code]];
                } else {
                    $data->{$compas_order_fields[$field['value']]} = $deal[$b24_code];
                    //echo '<b>'.$code.':</b>'.$deal[$b24_code].'<br>';
                }
            }
            if(isset($client) && $client)
                $data->client_id = $client->id;
            $data->save();
            //info($data);
            //info('id '.$data->id);
        };


    });
    Route::match(['get', 'post'], '/b24_crm_contact_update', function(Illuminate\Http\Request $request) {
        //info('EVENT TEST2');
        //info($request['data']);
        die();
        $user = \App\Models\User::where('api_token', $request->token)->first();//\Auth::user();
        $config = json_decode($user->bitrix24_config, true);
        $contact_id = $request['data']['FIELDS']['ID'];
        $response = Http::post($config['webhook'].'crm.contact.get', ['ID' => $contact_id])->collect();
        //info($response['result']['STAGE_ID'].' = '.$config['stage_id']);
        $compas_client_fields = array();
        $field_ids = array();
        foreach($config['client_fields'] as $b24_code => $field) {
            $field_ids[] = $field['value'];
        }
        $compas_client_fields = \DB::table('data_rows')->whereIn('id', $field_ids)->pluck('field', 'id')->toArray();

        if(isset($response['result'])) {
            $contact = $response['result'];
            $client = \Modules\Clients\Entities\Client::where('id_b24', $contact['ID'])->first();
            if(!$client) {
                $client = new \Modules\Clients\Entities\Client();
            }
            foreach($config['client_fields'] as $b24_code => $field) {
                $values = null;
                if(isset($field['field_values'])) {
                    $values = array();
                    foreach($field['field_values'] as $field_value) {
                        $values[$field_value['name']] = $field_value['value'];
                    };
                }
                if($values && isset($values[$contact[$b24_code]])) {
                    $client->{$compas_client_fields[$field['value']]} = $values[$contact[$b24_code]];
                } elseif(isset($contact[$b24_code])) {
                    $client->{$compas_client_fields[$field['value']]} = is_array($contact[$b24_code]) ? $contact[$b24_code][0]['VALUE'] : $contact[$b24_code];
                }
            }
            $client->id_b24 = $contact['ID'];
            $client->save();
        }
    });
    Route::get('/test_api', function(Request $request) {
        // try {
        //     throw new \Exception("Old password is not valid");
        // } catch (\Throwable $e) {
        //     echo 'ok';
        // }
        //cache()->getMemcached()->flush();
        // $s = get_settings();
        // echo '<pre>';
        // print_r($s['models']['requisites']);
        // echo '</pre>';
        // cache()->getMemcached()->flush();
        // $tenants = \App\Models\Tenant::get();
        // foreach ($tenants as $tenant) {
        //     $tenant->run(function () use ($tenant) {
        //         \Modules\Gibdd\Entities\Module::update_fines($tenant);
        //     });
        // }
        // die();
        // cache()->getMemcached()->flush();
        // cache()->flush();
        // $s = get_settings();
        // // $posts = \App\Models\Task::whereDoesntHave('clients', function ($q) {
        // //     $q->where('id', '=', 131);
        // // })->pluck('id');
        // // $s = get_settings();
        // echo '<pre>';
        // print_r($s['models']);
        // echo '</pre>';
        // // $order = \App\Models\Order::find(1120);
        // // print_r($order->clients);
        // die();
        //cache()->flush();
        //cache()->getMemcached()->flush();
        // // $f = \App\Models\Filter::list('products');
        // // // $c = Modules\Products\Entities\Category::find(1);
        // // // print_r($c->children);
        // // // \App\Models\Settings::clear_cache();
        // // // $table = get_settings();
        // // echo '<pre>';
        // // print_r($f);
        // // echo '</pre>';
        // die();
        // 
        // $settings = get_settings();
        // // // // $users = \App\Models\User::get();
        // // // // foreach($users as $user) {
        // // // //     cache()->getMemcached()->delete(tenant('id').':sidebar-'.$user->id);
        // // // // }
        // // // die();
        // // // $detail = \App\Models\EntityObject::detail('tasks', 1120, $request);
        // echo '<pre>';
        // print_r($settings['logistic_tasks']);
        // echo '</pre>';
        // die();
        // $data = array(
        //     'name' => 'Расширенный',
        //     'sort' => 1,
        //     'prices' => json_encode(array(
        //         '1 месяц' => 500,
        //         '3 месяца' => 1000,
        //         '6 месяцев' => 1500,
        //         '9 месяцев' => 2000,
        //         '1 год' => 2500
        //     ), JSON_UNESCAPED_UNICODE)
        // );

        // \App\Models\Tariff::create($data);
        // die();
        // $tenants = \App\Models\Tenant::get();
        // foreach ($tenants as $tenant) {
        //     $tenant->run(function () {
        //         $table = \App\Models\Table::get('cars');
        //         echo '<pre>';
        //         print_r($table);
        //         echo '</pre>';
        //     });
            
        // }
        // $s = \App\Models\Table::get('tasks');
        // echo '<pre>';
        // print_r($s);
        // echo '</pre>';
        // die();
        // $table = \App\Models\Table::get('tasks');
        // die();
        // $user = \App\Models\User::find(1);
        // $filters = $user->getSidebar();
        // // $name = 'opt6:filter-tasks-8';
        // // $data = cache()->getMemcached()->get($name);
        // // $filters = \App\Models\Filter::list('tasks');
        // // // \App\Models\Settings::clear_cache();
        // // // $settings = get_settings();
        // $item = \DB::table('settings')->where([
        //             'type' => 'sidebar',
        //             'user_id' => 1
        //         ])->first();
        // echo '<pre>';
        // print_r($item);
        // echo '</pre>';
        // die();
        // $settings = get_settings();
        // $slug = 'companies';
        // $current = \App\Models\Company::find(31);
        // $value = $current->employee_id;
        // $data = ValueHelper::isJson($value) && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;


        // //if($field->type == 'relation' && $field->is_plural) {
        //     $values = $data;
        //     $data = array();
        //     if(is_array($values)) {
        //         foreach($values as $val) {
        //             if(isset($settings[$slug]['list_values']['employee_id'][$val]))
        //                 $data[] = $settings[$slug]['list_values']['employee_id'][$val];
        //         }
        //     }
        // // } elseif($field->type == 'relation' && isset($settings[$slug]['list_values'][$field->field][$value])) {
        // //     $data = $settings[$slug]['list_values'][$field->field][$value];
        // // } elseif($field->type == 'relation') {
        // //     $data = null;
        // // };

        // print_r($data);
        // die();
        // \App\Models\Settings::clear_cache();
        // $s = get_settings();
        // echo '<pre>';
        // print_r($s['tasks']['list_values']['user_id']);
        // echo '</pre>';
        // die();
        //\App\Models\Settings::clear_cache();
        


// \Cache::forget('laravel_cache:86299e35827772eec6b01d1667b4110c0d3f97f3:tasks-2');
// $keys = cache()->getMemcached()->getAllKeys();
// $regex = 'tasks-*';
// foreach($keys as $item) {
//     if(preg_match('/'.$regex.'/', $item)) {
//         cache()->getMemcached()->delete($item);
//     }
// }
// print_r(cache()->getMemcached()->getAllKeys());
 
        // cache()->flush();
        // $s = get_settings();
        // echo '<pre>';
        // print_r($s['companies']['fields']['employee_id']);
        // echo '</pre>';
        // echo '<pre>';
        // print_r($s['clients']['fields']['task_id']);
        // echo '</pre>';
        // echo '<pre>';
        // print_r($s['cars']['fields']['fine_id']);
        // echo '</pre>';
        
        // $user = \App\Models\User::find(1);
        // print_r($user->getSidebar());
        // // $res = \Modules\Gibdd\Entities\Module::add_car([
        // //     'stsnum' => '434343',
        // //     'regnum' => '43434343'
        // // ]);
        // die();
        // if($request->n) {
        //     $user = \App\Models\User::where('email', $request->email)->first();
        //     echo $user->id;
        // }
        // cache()->flush();
        // die();
        // $tenants = \App\Models\Tenant::get();
        // foreach ($tenants as $tenant) {
        //     $res = \Modules\Gibdd\Entities\Module::update_fines($tenant);
        // }
        // $s = get_settings();
        // echo '<pre>';
        // print_r($s['tasks']['list_values']['tip_tk']);
        // echo '</pre>';
        // die();
        
        $client = new GuzzleHttp\Client();
        try {
            $response = $client->request('GET', 'https://opt6.compas.pro/api/requisites', [
                'headers' => [
                    'Authorization' => 'Bearer RPTlo5T7FEiGwJvtCNHuEOzBEla1qxWxP3aFTpOzVZPaeQxcA0FK0qfGYYGh',
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'rows' => array(
                        array(
                        'id' => 1,
                        'name' => 'OPT6'
                        )
                    )
                    //'name' => '123',
                    //'sum' => 300
                    //'uin' => '18810550231018083263'
                ]
            ]);

            //echo $response->getBody();
//             foreach ($response->getHeaders() as $name => $values) {
//     echo $name . ': ' . implode(', ', $values) . "\r\n";
// }
            $res = json_decode($response->getBody()->getContents(), true);
            echo '<pre>';
            print_r($res);
            echo '</pre>';
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $res = json_decode($responseBodyAsString, true);
            echo '<pre>';
            print_r($res);
            echo '</pre>';
            //echo $responseBodyAsString;
        }
        die();
    });
    Route::get('/generate', function () {
        cache()->flush();
        // $settings = get_settings();
        // echo '<pre>';
        // print_r($settings);
        // echo '</pre>';
        // die();
        // $filters = \App\Models\Filter::where(['user_id' => \Auth::user()->id, 'data_type' => 8])->orderBy('sort', 'asc')->get();
        // if(!$filters->count()) {
        //     $filter = \App\Models\Filter::create([
        //         'name' => 'Фильтр',
        //         'user_id' => \Auth::user()->id,
        //         'data_type' => 8,
        //         'is_active' => 1,
        //         'config' => json_encode(array('fields' => array('id' => '')))
        //     ]);
        //     $filters->push($filter);
        // };
        // $fields = collect(\DB::table('data_rows')->where('data_type_id', 8)->get())->keyBy('field')->toArray();
        // echo '<pre>';
        // print_r($fields[]);
        // echo '</pre>';
        // die();
        if(!\App\Models\Account::find(1)) {
            echo 1;
            $account = new App\Models\Account;
            $account->save();

            $balance = new App\Models\Balance;
            $balance->sum = 0;
            $balance->account_id = $account->id;
            $balance->save();

            $role = new App\Models\Role;
            $role->is_admin = 1;
            $role->name = 'admin';
            $role->display_name = 'Администратор';
            $role->save();

            $sidebar_item = new \App\Models\SidebarItem;
            $sidebar_item->name = 'Задачи';
            $sidebar_item->code = 'tasks';
            $sidebar_item->link = '/objects/tasks';
            //$sidebar_item->url = 'order';
            $sidebar_item->save();
            $sidebar_item = new \App\Models\SidebarItem;
            $sidebar_item->name = 'Настройки';
            $sidebar_item->code = 'settings';
            $sidebar_item->link = '/users';
            //$sidebar_item->url = 'users';
            $sidebar_item->save();
            echo 2;
            
            

            $user = new \App\Models\User;
            $user->email = 'lerevolte@yandex.ru';
            
            $user->password = 'new';
            $user->name = 'admin';
            $user->save();
            $user->roles()->sync([$role->id]);
            $user->save();
            $path = public_path('sql/seeds.sql');
            $sql = file_get_contents($path);
            \DB::unprepared($sql);
            
        } else {
            \Auth::loginUsingId(1);
            return redirect('/');
        }
        //echo \Auth::user()->id;

        //return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
    });
    
    
});



//php artisan migrate:generate --tables="accounts,balances,balance_operations,data_rows,data_types,failed_jobs,fields, field_sections,field_values,files,histories,menus,menu_items,orders,password_resets,permissions,permission_role, personal_access_tokens,products,roles,settings,sidebar_items,translations,users,user_histories,user_roles"
