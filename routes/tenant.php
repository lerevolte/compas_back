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
use Nwidart\Modules\Facades\Module as ModuleConfig;
use App\Models\History;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use App\Models\Settings;
use App\Models\Tariff;

if(!function_exists('get_settings')) {
    function get_settings() {
        return \App\Models\Settings::get();
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

Route::get('/leaflet', [App\Http\Controllers\MapController::class, 'show'])->middleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        'tenantkeeper'
    ]);
Route::get('/route/v1/driving/{coords}', function(Request $request) {
    $currentDomain = request()->getHttpHost();
    $url = str_replace('https://'.$currentDomain, 'http://compas-osrm.ru:5000', url()->full());
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);

    info(url()->full());
    info($request->all());
    info($url);
    info($data);
    return $data;
});
Route::post('/set_geoposition', [App\Http\Controllers\Api\GeopositionController::class, 'store']);
Route::match(['get', 'post'],'/set_fcm_token', function(Request $request) {
    if($request->userId) {
        $tenant = \App\Models\Tenant::find($request->accountId);
        $tenant->run(function () use ($request) {
            $user = \App\Models\User::find($request->userId);
            $devices = [];
            if($user->device_id)
                $devices = json_decode($user->device_id, true);
            if(!in_array($request->deviceId, $devices)) {
                info('add device');
                $devices[] = $request->deviceId;
            }
            $tokens = [];
            if($user->token)
                $tokens = json_decode($user->token, true);
            if(!in_array($request->token, $tokens)) 
                $tokens[] = $request->token;
            $user->token = json_encode($tokens);
            $user->device_id = json_encode($devices);

            $user->app_version = $request->version;
            $user->save();
        });
    }
});
Route::get('/api/external/{token}', [App\Http\Controllers\Api\ExternalLinkController::class, 'show'])
    ->name('external.show')
    ->middleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        'tenantkeeper'
    ]);
// Таблица привязанной сущности по внешней ссылке (без auth, scoping на сервере).
Route::get('/api/external/{token}/module/{module}', [App\Http\Controllers\Api\ExternalLinkController::class, 'showModule'])
    ->name('external.module')
    ->middleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        'tenantkeeper'
    ]);
Route::get('/api/external/{token}/table/{slug}', [App\Http\Controllers\Api\ExternalLinkController::class, 'table'])
    ->name('external.table')
    ->middleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        'tenantkeeper'
    ]);
// Задачи маршрута со статусами (statusColor) и данные карты для внешней ссылки
// на деталку маршрута (без auth). Дают ту же форму, что авторизованные
// routes/{id}/tasks и routes/{id}/map_data — нужны полям «Статусы» и
// «Маршрут на карте» во внешней ссылке (8651).
Route::get('/api/external/{token}/route-tasks', [App\Http\Controllers\Api\ExternalLinkController::class, 'routeTasks'])
    ->name('external.route_tasks')
    ->middleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        'tenantkeeper'
    ]);
Route::get('/api/external/{token}/route-map', [App\Http\Controllers\Api\ExternalLinkController::class, 'routeMapData'])
    ->name('external.route_map')
    ->middleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        'tenantkeeper'
    ]);
Route::post('/api/external/{token}/batch', [App\Http\Controllers\Api\ExternalLinkController::class, 'batch'])
    ->name('external.batch')
    ->middleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        'tenantkeeper'
    ]);
Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    ])->prefix('api')->group(function () {
        Route::post(
            'auth',
            [App\Http\Controllers\Api\AuthController::class, 'login']
        );
        Route::get('auth', function () {
            return redirect('/auth');
        });
        Route::post(
            'auth1', 
            [App\Http\Controllers\Api\AuthController::class, 'login1']
        );
        Route::post(
            'password/forgot', 
            [App\Http\Controllers\Api\AuthController::class, 'password_forgot']
        );
        Route::post(
            'password/reset', 
            [App\Http\Controllers\Api\AuthController::class, 'password_reset']
        );
        Route::get('auth/token_expired',  [App\Http\Controllers\Api\AuthController::class, 'token_expired']);
});

Route::middleware([
    'auth:api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'tenantkeeper'
    ])->prefix('api')->group(function () {
    Route::name('api.')->group(function () {
        Route::prefix('external-links')->group(function () {
            Route::post('/', [App\Http\Controllers\Api\ExternalLinkController::class, 'create'])->name('external_links.create');
            Route::get('/', [App\Http\Controllers\Api\ExternalLinkController::class, 'list'])->name('external_links.list');
            Route::delete('/{token}', [App\Http\Controllers\Api\ExternalLinkController::class, 'revoke'])->name('external_links.revoke');
        });
        Route::post(
            'auth/{id}', 
            [App\Http\Controllers\Api\AuthController::class, 'loginByUser']
        );
        
        Route::prefix('chat')->group(function () {
            Route::get(
                'users', 
                [App\Http\Controllers\Api\ChatController::class, 'users']
            );
            Route::resource('groups', App\Http\Controllers\Api\ChatGroupController::class);
            Route::resource('messages', App\Http\Controllers\Api\ChatMessageController::class);
        });
       
        Route::post('b24_config', function (Request $request) {
            $user = \App\Models\User::create();

            return response()->json(['tenant' => tenant('id')]);
        });

        Route::get(
            'sidebar/get', 
            [App\Http\Controllers\Api\SidebarController::class, 'get']
        );
        Route::post(
            'sidebar/set', 
            [App\Http\Controllers\Api\SidebarController::class, 'set']
        );
        Route::post(
            'sidebar/set/group', 
            [App\Http\Controllers\Api\SidebarController::class, 'set_group']
        );
        Route::get(
            'sidebar/reset', 
            [App\Http\Controllers\Api\SidebarController::class, 'reset']
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
        Route::get(
            'balance/operations', 
            [App\Http\Controllers\Api\BalanceController::class, 'operations']
        );
        Route::put('balance', [
            'uses' => 'App\Http\Controllers\Api\BalanceController@change'
        ]);
        Route::post(
            'balance/payment', 
            [App\Http\Controllers\Api\BalanceController::class, 'payment']
        );

        Route::get(
            'settings', 
            [App\Http\Controllers\Api\SettingsController::class, 'compose']
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
        Route::put(
            'roles/{id}', 
            [App\Http\Controllers\Api\RoleController::class, 'update']
        );
        Route::delete('roles/{id}', [App\Http\Controllers\Api\RoleController::class, 'destroy']);

        Route::get(
            'requisites/{company_id?}', 
            [App\Http\Controllers\Api\RequisiteController::class, 'list']
        );
        Route::put(
            'requisites/batch', 
            [App\Http\Controllers\Api\RequisiteController::class, 'batch']
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
        );
        Route::get(
            'tables/{model}/reset', 
            [App\Http\Controllers\Api\TableController::class, 'reset']
        );

         Route::post(
            'tables/{model}/per_page', 
            [App\Http\Controllers\Api\TableController::class, 'set_per_page']
        )->name('tables.set_per_page');

        Route::post(
            'tables/{model}/all', 
            [App\Http\Controllers\Api\TableController::class, 'set_all']
        );
        Route::post(
            'tables/{model}/{role_id}', 
            [App\Http\Controllers\Api\TableController::class, 'set_role']
        );

       
        

        Route::get('profile', [App\Http\Controllers\Api\ProfileController::class, 'show'])->name('profile.show');
        Route::get('users', [App\Http\Controllers\Api\UserController::class, 'list'])->name('users.list');
        Route::get('users/{user}/roles', [App\Http\Controllers\Api\UserController::class, 'list'])->name('user.roles');
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

        Route::get(
            'tariffs', 
            [App\Http\Controllers\Api\TariffController::class, 'list']
        )->name('tariffs.list');
        Route::put(
            'tariffs/{id}', 
            [App\Http\Controllers\Api\TariffController::class, 'set']
        )->name('tariffs.set');

        Route::get('redactor/questions', [App\Http\Controllers\Api\FaqController::class, 'questions'])->name('redactor.questions');

        Route::get('objects/search', [App\Http\Controllers\Api\ObjectController::class, 'search'])->name('objects.search');
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
        
        Route::match(['post','put'], 'objects/{model}/batch', [App\Http\Controllers\Api\ObjectController::class, 'batch'])->name('objects.batch');
        Route::delete('objects/{model}', [App\Http\Controllers\Api\ObjectController::class, 'delete'])->name('objects.delete');
        Route::post('objects/{model}/restore', [App\Http\Controllers\Api\ObjectController::class, 'restore'])->name('objects.restore');
        Route::post('objects/{model}/{id}/restore', [App\Http\Controllers\Api\ObjectController::class, 'restore_single'])->name('objects.restore_single');
        Route::get('objects/{model}/{id}/compose', [App\Http\Controllers\Api\ObjectController::class, 'compose_show'])->name('objects.compose_show');
        Route::post('objects/{model}/{id}/attach-employee', [App\Http\Controllers\Api\ObjectController::class, 'attach_employee'])->name('objects.attach_employee');

        Route::put('objects/{model}/{id}', [App\Http\Controllers\Api\ObjectController::class, 'update'])->name('objects.update');
        Route::get('objects/{model}/{id}/{module}/compose', [App\Http\Controllers\Api\ObjectController::class, 'compose_show_module'])->name('objects.compose_show_module');

        Route::get('history/{model}/{id}', [App\Http\Controllers\Api\HistoryController::class, 'index']);
        Route::get('history/{model}/{id}/{module?}', [App\Http\Controllers\Api\HistoryController::class, 'index']);
        Route::get('history/table/{model}/{id}', [App\Http\Controllers\Api\HistoryController::class, 'table']);

        Route::post('field_sections/change-sort', [App\Http\Controllers\Api\FieldSectionController::class, 'changeSort']);
        Route::post('field_sections/hide', [App\Http\Controllers\Api\FieldSectionController::class, 'hide']);
        Route::delete('field_sections/{id}', [App\Http\Controllers\Api\FieldSectionController::class, 'delete']);
        Route::post('field_sections', [App\Http\Controllers\Api\FieldSectionController::class, 'store']);
        Route::put('field_sections/{id}', [App\Http\Controllers\Api\FieldSectionController::class, 'update']);
        

        Route::post('field/change-sort', [App\Http\Controllers\Api\FieldController::class, 'changeSort']);
        Route::post('field/hide/{id}', [App\Http\Controllers\Api\FieldController::class, 'hide']);
        Route::post('field/hide_batch', [App\Http\Controllers\Api\FieldController::class, 'hide_batch']);
        Route::delete('field/{id}', [App\Http\Controllers\Api\FieldController::class, 'delete']);
        Route::post('field/status', [App\Http\Controllers\Api\FieldController::class, 'status_store']);
        Route::post('field', [App\Http\Controllers\Api\FieldController::class, 'store']);
        Route::put('field/{id}', [App\Http\Controllers\Api\FieldController::class, 'new_update']);

        Route::post('files/store', [App\Http\Controllers\Api\FileController::class, 'store']);

        //Route::get('entities', [App\Http\Controllers\Api\EntityController::class, 'list']);
        Route::put('entities', [App\Http\Controllers\Api\EntityController::class, 'update']);
        Route::get('entities', [App\Http\Controllers\Api\EntityController::class, 'compose_list']);
        Route::get('entities/{slug}/menu', [App\Http\Controllers\Api\EntityController::class, 'get_menu']);
        Route::get('entities/{slug}/menu/reset', [App\Http\Controllers\Api\EntityController::class, 'reset_menu']);
        Route::put('entities/{slug}/menu', [App\Http\Controllers\Api\EntityController::class, 'set_menu']);
        Route::put('entities/{slug}/menu/all', [App\Http\Controllers\Api\EntityController::class, 'set_menu_all']);
        Route::put('entities/{slug}/menu/role/{role_id}', [App\Http\Controllers\Api\EntityController::class, 'set_menu_role']);

        Route::get('tabs/trash', [App\Http\Controllers\Api\TabController::class, 'trash']);
        Route::get('tabs/{entity}', [App\Http\Controllers\Api\TabController::class, 'get']);
        Route::put('tabs/{slug}/permissions', [App\Http\Controllers\Api\TabController::class, 'permissions']);
        Route::put('tabs/{slug}/events-visibility', [App\Http\Controllers\Api\TabController::class, 'events_visibility']);

        Route::get('map/suggest', [App\Http\Controllers\Api\MapController::class, 'suggest']);
        Route::get('map/geocode', [App\Http\Controllers\Api\MapController::class, 'geocode']);
        Route::get('suggest/bank', [App\Http\Controllers\Api\SuggestController::class, 'bank']);
        
        
        Route::get('modules/categories', [App\Http\Controllers\Api\ModuleController::class, 'categories'])->name('modules.categories');
        Route::get('modules/installed', [App\Http\Controllers\Api\ModuleController::class, 'installed'])->name('modules.installed');
        Route::get('modules/{slug?}', [App\Http\Controllers\Api\ModuleController::class, 'list'])->name('modules.list');
        Route::put('modules/{slug}', [App\Http\Controllers\Api\ModuleController::class, 'update'])->name('modules.update');

        Route::get('modules/{slug}/show', [App\Http\Controllers\Api\ModuleController::class, 'show'])->name('modules.show');
        Route::get('modules/{slug}/settings', [App\Http\Controllers\Api\ModuleController::class, 'settings'])->name('modules.settings');
        Route::post('modules/{slug}/install', [App\Http\Controllers\Api\ModuleController::class, 'install'])->name('modules.install');
        Route::post('modules/{slug}/uninstall', [App\Http\Controllers\Api\ModuleController::class, 'uninstall'])->name('modules.uninstall');
        Route::get('modules/{entity}/{id}/{module}/check_entities', [App\Http\Controllers\Api\ModuleController::class, 'check_entity_statuses']);

        Route::get('settings/account', [App\Http\Controllers\Api\SettingsController::class, 'account']);
        Route::put('settings/account', [App\Http\Controllers\Api\SettingsController::class, 'account_update']);
        Route::get('settings/user', [App\Http\Controllers\Api\SettingsController::class, 'user']);
        Route::put('settings/user', [App\Http\Controllers\Api\SettingsController::class, 'user_update']);

        Route::get('routes', [App\Http\Controllers\Api\RouteController::class, 'list'])->name('routes.list');
        Route::post('routes', [App\Http\Controllers\Api\RouteController::class, 'store'])->name('routes.store');
        Route::put('routes/batch', [App\Http\Controllers\Api\RouteController::class, 'batch'])->name('routes.batch');
        Route::delete('routes', [App\Http\Controllers\Api\RouteController::class, 'delete']);
        Route::get('routes/{id}/map_data', [App\Http\Controllers\Api\RouteController::class, 'map_data'])->name('routes.map_data');
        Route::get('routes/{id}/tasks', [App\Http\Controllers\Api\RouteController::class, 'tasks'])->name('routes.tasks');
        Route::put('routes/{id}/tasks', [App\Http\Controllers\Api\RouteController::class, 'update_tasks'])->name('routes.update_tasks');
        // Создать задачу логистики из адреса «Справочника адресов» (drag&drop),
        // адрес-источник не меняется. Body: { address_id, route_id? }.
        Route::post('routes/task-from-address', [App\Http\Controllers\Api\RouteController::class, 'task_from_address'])->name('routes.task_from_address');
        Route::post('routes/task-from-warehouse', [App\Http\Controllers\Api\RouteController::class, 'task_from_warehouse'])->name('routes.task_from_warehouse');
        Route::get('routes/{id}/task_filter', [App\Http\Controllers\Api\RouteController::class, 'task_filter'])->name('routes.task_filter');
        Route::post('object-relations', [App\Http\Controllers\Api\ObjectRelationController::class, 'store'])->name('object_relations.store');
        Route::get('object-relations/{slug}/{id}', [App\Http\Controllers\Api\ObjectRelationController::class, 'tree'])->name('object_relations.tree');
        Route::get('object-relations/{slug}/{id}/products-check', [App\Http\Controllers\Api\ObjectRelationController::class, 'productsCheck'])->name('object_relations.products_check');
        Route::get('object-relations/{slug}/{id}/print-documents', [App\Http\Controllers\Api\ObjectRelationController::class, 'printDocuments'])->name('object_relations.print_documents');
        Route::get('logistic_tasks/{id}/saby-waybills', [App\Http\Controllers\Api\SabyWaybillController::class, 'index'])->name('saby.waybills');
        Route::post('logistic_tasks/{id}/saby-waybills', [App\Http\Controllers\Api\SabyWaybillController::class, 'store'])->name('saby.waybills.store');
        Route::get('logistic_tasks/{id}/saby-waybills/check', [App\Http\Controllers\Api\SabyWaybillController::class, 'check'])->name('saby.waybills.check');
        Route::get('logistic_tasks/{id}/saby-waybills/route-tasks', [App\Http\Controllers\Api\SabyWaybillController::class, 'routeTasks'])->name('saby.waybills.route_tasks');
        Route::post('saby-waybills/{id}/refresh', [App\Http\Controllers\Api\SabyWaybillController::class, 'refresh'])->name('saby.waybills.refresh');
        Route::delete('saby-waybills/{id}', [App\Http\Controllers\Api\SabyWaybillController::class, 'destroy'])->name('saby.waybills.destroy');
        Route::get('logistic_tasks/{id}/saby-orders', [App\Http\Controllers\Api\SabyOrderController::class, 'index'])->name('saby.orders');
        Route::post('logistic_tasks/{id}/saby-orders', [App\Http\Controllers\Api\SabyOrderController::class, 'store'])->name('saby.orders.store');
        Route::get('logistic_tasks/{id}/saby-orders/check', [App\Http\Controllers\Api\SabyOrderController::class, 'check'])->name('saby.orders.check');
        Route::post('saby-orders/{id}/refresh', [App\Http\Controllers\Api\SabyOrderController::class, 'refresh'])->name('saby.orders.refresh');
        Route::delete('saby-orders/{id}', [App\Http\Controllers\Api\SabyOrderController::class, 'destroy'])->name('saby.orders.destroy');
        Route::get('saby/settings', [App\Http\Controllers\Api\SabyConfigController::class, 'show'])->name('saby.settings');
        Route::put('saby/settings', [App\Http\Controllers\Api\SabyConfigController::class, 'update'])->name('saby.settings.update');
        // Настройки полей вкладки «Маршрут списком» (общие на портал).
        Route::get('route-tasks-view/fields', [App\Http\Controllers\Api\RouteController::class, 'tasks_view_fields'])->name('routes.tasks_view_fields');
        Route::put('route-tasks-view/fields', [App\Http\Controllers\Api\RouteController::class, 'save_tasks_view_fields'])->name('routes.save_tasks_view_fields');

        Route::get('product-stats/products', [App\Http\Controllers\Api\ProductStatsController::class, 'products'])->name('product_stats.products');
        Route::get('product-stats/tasks', [App\Http\Controllers\Api\ProductStatsController::class, 'tasks'])->name('product_stats.tasks');

        Route::put(
            'logistic_tasks/{id}/set_products',
            [App\Http\Controllers\Api\TaskController::class, 'set_products']
        );

        Route::put(
            'deals/{id}/set_products',
            [App\Http\Controllers\Api\TaskController::class, 'set_deal_products']
        );

        Route::put(
            'payment_invoices/{id}/set_products',
            [App\Http\Controllers\Api\TaskController::class, 'set_payment_invoice_products']
        );

        Route::put(
            'expense_invoices/{id}/set_products',
            [App\Http\Controllers\Api\TaskController::class, 'set_expense_invoice_products']
        );

        Route::put(
            'product_returns/{id}/set_products',
            [App\Http\Controllers\Api\TaskController::class, 'set_product_return_products']
        );

        Route::put(
            'pickups/{id}/set_products',
            [App\Http\Controllers\Api\TaskController::class, 'set_pickup_products']
        );

        Route::put(
            'addresses/{id}/set_products',
            [App\Http\Controllers\Api\TaskController::class, 'set_address_products']
        );

        Route::get(
            'analytics', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'get_all_analytics']
        );
        Route::put(
            'analytics/settings', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'settings']
        );
        Route::get(
            'analytics/settings', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'get_settings']
        );

        Route::prefix('analytics')->group(function () {
            Route::get('/logistics-car-count', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_car_count']);
            Route::get('/logistics-order-stats', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_order_stats']);
            Route::get('/logistics-route-mileage', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_route_mileage']);
            Route::get('/logistics-route-duration', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_route_duration']);
            Route::get('/logistics-reserve-for-delivery', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_reserve_for_delivery']);
            Route::get('/logistics-delivery-price', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_delivery_price']);
            Route::get('/logistics-delivery-compare', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_delivery_compare']);
            Route::get('/logistics-total-weight', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_total_weight']);
            Route::get('/logistics-arrival-percent', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_arrival_percent']);
            Route::get('/logistics-car-arrival-percent', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_car_arrival_percent']);
            Route::get('/logistics-company-profit', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_company_profit']);
            Route::get('/logistics-car-profit', [App\Http\Controllers\Api\AnalyticsController::class, 'logistics_car_profit']);
            Route::get('/logistics-all', [App\Http\Controllers\Api\AnalyticsController::class, 'get_all_logistics_analytics']);
            Route::get('logistics-day-summary', [App\Http\Controllers\Api\AnalyticsController::class, 'logisticsDaySummary']);
        });
        
        Route::post('analytics/export/{type}', [App\Http\Controllers\Api\AnalyticsController::class, 'export']);

        Route::get('logistic/sections', [App\Http\Controllers\Api\LogisticController::class, 'get_sections']);
        Route::put('logistic/sections/{id}', [App\Http\Controllers\Api\LogisticController::class, 'update_section']);
        

        Route::get('cache_changes', function(Request $request){
            if($request->time)
                $time = date('Y-m-d H:i:s', (int)$request->time);
            else
                $time = date('Y-m-d H:i:s');
            $urls = \DB::table('local_cache')->where('updated_at', '>=', $time)->where('user_id', \Auth::user()->id)->get();;
            return response()->json($urls);
        });
        Route::delete(
            '/tenant/delete', 
            [App\Http\Controllers\Api\TenantController::class, 'delete']
        );
    });
});



Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'tenantkeeper'
])->group(function () {

    Route::get('/test_api', function(Request $request) {
        
        $startTotal = microtime(true);
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', 'https://opt6.compas.pro/api/objects/logistic_tasks/133374/compose', [
                'headers' => [
                    'Authorization' => 'Bearer 0vNsh0NrXyQjo75HSNzZJIkZYn6ss1dCh8BXx9boBBQVSDaeu1UgbZYPoZcA',
                    'Accept' => 'application/json',
                    //'Content-Type' => 'application/json',
                ],
                // 'form_params' => [
                //     'username' => 'reg@opt6.ru',
                //     'password' => 'ciSnin-6zykdu-mykkyt'
                //     //'name' => '123',
                //     //'sum' => 300
                //     //'uin' => '18810550231018083263'
                // ]
            ]);
            $res = json_decode($response->getBody(), true);
            echo 111;
            echo '<pre>';
            print_r($res);
            echo '</pre>';
            //return $res;
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            echo 222;
            echo $e->getMessage();
            return array('error' => 406);
        }
        die();
        $totalTime = microtime(true) - $startTotal;

        // Вывод результатов замера времени
        echo "<pre>Анализ производительности:\n";
        echo "Общее время выполнения: " . number_format($totalTime, 4) . " сек\n\n";
        die();
        $wo_cache = false;
        $model = $slug = 'cars';
        $id = 488;
        $module = null;
        $timings = [];
        \Auth::loginUsingId(1); // Авторизует пользователя с ID = 1
        
        $settings = app('settings');
        $timings = [];
        

        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        if(!$entity)
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        $data_type_id = $entity->id;
        $permissions = array();

        if(\Auth::user()->role_id) {
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
            if(!$permissions) {
                $data_types = \DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
                $permissions = array();
                $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', \Auth::user()->role_id)->get()->keyBy('entity_id')->toArray();

                $new_permissions_exist = false;
                foreach($data_types as $entity_id => $entity) {
                    if(!array_key_exists($entity_id, $res)) {
                        \DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => \Auth::user()->role_id]]);
                        $new_permissions_exist = true;
                    }
                }
                $permissions = \Auth::user()->role->permissions()->select([
                    'read_p',
                    'create_p',
                    'update_p',
                    'delete_p',
                    'export_p',
                    'import_p'
                ])->where('entity_id', $data_type_id)->first();
            }
            $permissions = $permissions->toArray();
        }
        if($slug == 'users' && $id == \Auth::user()->id || \Auth::user()->is_admin)
            $permissions['update_p'] = 'A';
        if(isset($permissions['read_p']) && $permissions['read_p'] == 'N' && ($slug != 'users' || 
            $slug == 'users' && \Auth::user()->id != $id) && !\Auth::user()->is_admin) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }
        //api/objects/$slug/$id
        $add_params = [];
        if(isset($permissions['read_p']) && $permissions['read_p'] == 'Y')
            $add_params['user_id'] = \Auth::user()->id;
        if($request->is_copy)
            $add_params['is_copy'] = 1;
        $request->merge($add_params);

        $detail = \App\Models\EntityObject::detail($slug, $id, $request);
        if(isset($detail['error'])) {
            return response()->json([
                    'message' => $detail['error']['message']
                ], $detail['error']['code']);
        }
        //api/objects/products?order_id=$id
        $products = array();
        if($slug == 'logistic_tasks' && $id) {
            $nrequest = new Request(['order_id' => $id]);
            $products = \App\Models\EntityObject::list('products', $nrequest);
        }
        //api/tables/order_products
        $table = array();
        if($slug == 'logistic_tasks')
            $table = \App\Models\Table::get_order_products();
        //api/entities
        $entities = \DB::table('data_types')->select(['slug', 'title_singular', 'title_plural', 'color'])->get();
        //api/history/$slug/$id
        if(!$id || $request->is_copy) {
            $history_events = $history_fields = array();
        } else {
            $history_events = \App\Models\History::list($slug, $id, null, new Request(['filter' => 'events']));
            $history_fields = \App\Models\History::list($slug, $id, null, null);
        }

        //api/entities/$slug/menu
        $menu = \App\Models\Menu::get($slug);
        

        $totalTime = microtime(true) - $startTotal;

        // Вывод результатов замера времени
        echo "<pre>Анализ производительности:\n";
        echo "Общее время выполнения: " . number_format($totalTime, 4) . " сек\n\n";

        
        die();
        //return $data;
    });


    // Route::get('auth/forgot',  App\Http\Controllers\SpaController::class)->name('password.reset');
    Route::get('auth',  App\Http\Controllers\SpaController::class)->name('password.reset');

    Route::get('{path}', App\Http\Controllers\SpaController::class)->where('path', '^(?!api).*$');
    
});



//php artisan migrate:generate --tables="accounts,balances,balance_operations,data_rows,data_types,failed_jobs,fields, field_sections,field_values,files,histories,menus,menu_items,orders,password_resets,permissions,permission_role, personal_access_tokens,products,roles,settings,sidebar_items,translations,users,user_histories,user_roles"
