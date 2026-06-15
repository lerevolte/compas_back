<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ObjectController;
use Modules\Gibdd\Http\Controllers\Api\GibddController;

Route::post(
    'auth', 
    [App\Http\Controllers\Api\AuthController::class, 'login']
);
Route::post(
    'tenant/check', 
    [App\Http\Controllers\Api\TenantController::class, 'check']
);
// Route::get(
//     'gibdd/check_by_sts/{sts}', 
//     [GibddController::class, 'checkBySts']
// )->name('gibdd.check_by_sts');
Route::get('map/suggest', [App\Http\Controllers\Api\MapController::class, 'suggest']);
Route::get('map/geocode', [App\Http\Controllers\Api\MapController::class, 'geocode']);
Route::post('map/yandex-log', [App\Http\Controllers\Api\MapController::class, 'logRoute'])
    ->middleware('throttle:120,1');
Route::post(
    'registration', 
    App\Http\Controllers\Api\RegistrationController::class
);
Route::get('test5', function() {

	info('mda');
        return response()->json(['mda' => 'mda']);
        //return view('home');
    });
Route::group(['middleware' => ['api']], function() {
    Route::get('test4', function() {
        return response()->json(['mda' => 'mda']);
        //return view('home');
    });
});
Route::middleware([
    'api'
])->group(function () {
    Route::get(
        'pages',
        [App\Http\Controllers\SpaController::class, 'pages']
    );
    Route::get(
        'health',
        [App\Http\Controllers\Api\HealthController::class, 'index']
    );
    Route::get(
        'table/fines', 
        [App\Http\Controllers\Api\TableController::class, 'public_fines']
    );
    Route::get(
        'entities/last_modified', 
        [App\Http\Controllers\Api\EntityController::class, 'last_modified']
    );
    Route::get(
        'blog', 
        [App\Http\Controllers\Api\BlogController::class, 'list']
    );
    Route::get(
        'knowledge', 
        [App\Http\Controllers\Api\KnowledgeController::class, 'list']
    );
    Route::get(
        'faq', 
        [App\Http\Controllers\Api\FaqController::class, 'list']
    );
    Route::get(
        'guides', 
        [App\Http\Controllers\Api\GuideController::class, 'list']
    );
    Route::get(
        'faq/search', 
        [App\Http\Controllers\Api\FaqController::class, 'search']
    );
    Route::get(
        'blog/search', 
        [App\Http\Controllers\Api\BlogController::class, 'search']
    );
    Route::get(
        'knowledge/search', 
        [App\Http\Controllers\Api\KnowledgeController::class, 'search']
    );
    Route::get(
        'guides/search', 
        [App\Http\Controllers\Api\GuideController::class, 'search']
    );
    Route::get(
        'blog/{slug}', 
        [App\Http\Controllers\Api\BlogController::class, 'detail']
    );
    Route::get(
        'knowledge/{slug}', 
        [App\Http\Controllers\Api\KnowledgeController::class, 'detail']
    );
    Route::get(
        'faq/{slug}', 
        [App\Http\Controllers\Api\FaqController::class, 'detail']
    );

    Route::get(
        'guides/show/{id}', 
        [App\Http\Controllers\Api\GuideController::class, 'detail_by_id']
    );

    Route::get(
        'guides/{slug}', 
        [App\Http\Controllers\Api\GuideController::class, 'detail']
    );

    

    
    // Route::get(
    //     'blog/{id}', 
    //     [App\Http\Controllers\Api\BlogController::class, 'list']
    // );
    // Route::get(
    //     'faq', 
    //     [App\Http\Controllers\Api\FaqController::class, 'list']
    // );
    // Route::get(
    //     'objects/faq/compose', 
    //     [App\Http\Controllers\Api\ObjectController::class, 'compose_list']
    // );
    // Route::get(
    //     'objects/blog_categories/compose', 
    //     [App\Http\Controllers\Api\ObjectController::class, 'compose_list']
    // );
    // Route::get(
    //     'objects/faq_categories/compose', 
    //     [App\Http\Controllers\Api\ObjectController::class, 'compose_list']
    // );
    // Route::get('objects/{model}/compose', 
    //     [App\Http\Controllers\Api\ObjectController::class, 'compose_list']
    // )->whereIn('model', ['articles', 'faq', 'blog_categories', 'faq_categories']);
});
Route::middleware([
    'auth:api',
    //'api'
    ])->group(function () {
    Route::name('api.')->group(function () {
    	Route::match(['get', 'post'],'application/settings', function(Request $request) {
            $data = array(
                'geo' => array(
                    'time' => 30,
                    'pack' => 5
                ),
                'events' => true
            );

            return response()->json($data);
        });
        Route::post(
            'auth/{id}', 
            [App\Http\Controllers\Api\AuthController::class, 'loginByUser']
        );

        Route::post(
            'auth_account/{tenant}', 
            [App\Http\Controllers\Api\AuthController::class, 'loginByAccount']
        );

        Route::get(
            'analytics', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'get_all_analytics']
        );

        Route::get(
            'analytics/gibdd', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'gibdd']
        );

        Route::get(
            'analytics/income', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'income']
        );
        Route::get(
            'analytics/income_moneta', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'income_moneta']
        );
        Route::get(
            'analytics/account_incomes', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'account_incomes']
        );
        Route::get(
            'analytics/expense_moneta', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'expense_moneta']
        );
        Route::get(
            'analytics/gibdd_queries', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'gibdd_queries']
        );
        Route::get(
            'analytics/all_income', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'all_income']
        );
        
        Route::post('analytics/export/{type}', [App\Http\Controllers\Api\AnalyticsController::class, 'export']);

        
        Route::put(
            'analytics/settings', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'settings']
        );
        Route::get(
            'analytics/settings', 
            [App\Http\Controllers\Api\AnalyticsController::class, 'get_settings']
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
        Route::post('sidebar/change-sort', [App\Http\Controllers\Api\SidebarController::class, 'sort']);
        Route::put(
            'sidebar/{id}', 
            [App\Http\Controllers\Api\SidebarController::class, 'update']
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
        // Route::get(
        //     'roles/{id}/modules', 
        //     [App\Http\Controllers\Api\RoleController::class, 'show_modules']
        // );
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
            'fields/{model}', 
            [App\Http\Controllers\Api\FieldController::class, 'list']
        );

        Route::get(
            'tables/order_products', 
            [App\Http\Controllers\Api\TableController::class, 'get_order_products']
        );
        Route::get(
            'tables/{model}', 
            [App\Http\Controllers\Api\TableController::class, 'get']
        );

        Route::post(
            'tables/{model}', 
            [App\Http\Controllers\Api\TableController::class, 'set']
        );

         Route::post(
            'tables/{model}/per_page', 
            [App\Http\Controllers\Api\TableController::class, 'set_per_page']
        );

        Route::post(
            'tables/{model}/all', 
            [App\Http\Controllers\Api\TableController::class, 'set_all']
        );
        Route::post(
            'tables/{model}/{role_id}', 
            [App\Http\Controllers\Api\TableController::class, 'set_role']
        );

       
        

        Route::get('profile', [App\Http\Controllers\Api\ProfileController::class, 'show']);

        Route::get('users', [App\Http\Controllers\Api\UserController::class, 'list']);
        Route::get('users/{user}/roles', [App\Http\Controllers\Api\UserController::class, 'list']);
        Route::get('permissions', [App\Http\Controllers\Api\UserController::class, 'permissions']);

        Route::get(
            'filters/{model}', 
            [App\Http\Controllers\Api\FilterController::class, 'list']
        );
        Route::post('filters/{model}', [App\Http\Controllers\Api\FilterController::class, 'store']);
        Route::put('filters/{model}/{id}', [App\Http\Controllers\Api\FilterController::class, 'update']);
        Route::delete('filters/{model}/{id}', [App\Http\Controllers\Api\FilterController::class, 'delete']);
        Route::post('filters/{slug}/change-sort', [App\Http\Controllers\Api\FilterController::class, 'sort']);
        Route::get(
            'trash', 
            [App\Http\Controllers\Api\TrashController::class, 'index']
        );
        Route::get(
            'tariffs', 
            [App\Http\Controllers\Api\TariffController::class, 'list']
        );
        Route::put(
            'tariffs/{id}', 
            [App\Http\Controllers\Api\TariffController::class, 'set']
        );

        Route::get('objects/search', [App\Http\Controllers\Api\ObjectController::class, 'search']);
        // Route::get(
        //     'objects/{model}', 
        //     [App\Http\Controllers\Api\ObjectController::class, 'list']
        // );
        Route::get(
            'objects/{model}/compose', 
            [App\Http\Controllers\Api\ObjectController::class, 'compose_list']
        );
        Route::get(
            'objects/{model}/export', 
            [App\Http\Controllers\Api\ObjectController::class, 'export']
        );
        
        Route::match(['post','put'], 'objects/{model}/batch', [App\Http\Controllers\Api\ObjectController::class, 'batch']);

        Route::post('objects/{model}', [App\Http\Controllers\Api\ObjectController::class, 'store']);

        Route::delete('objects/{model}', [App\Http\Controllers\Api\ObjectController::class, 'delete']);
        Route::post('objects/{model}/restore', [App\Http\Controllers\Api\ObjectController::class, 'restore']);
        Route::post('objects/{model}/{id}/restore', [App\Http\Controllers\Api\ObjectController::class, 'restore_single']);
        Route::get('objects/{model}/{id}/compose', [App\Http\Controllers\Api\ObjectController::class, 'compose_show']);

        Route::put('objects/{model}/{id}', [App\Http\Controllers\Api\ObjectController::class, 'update']);
        Route::post('objects/{model}/{id}/copy', [App\Http\Controllers\Api\ObjectController::class, 'copy']);
        Route::get('objects/{model}/{id}/{module}/compose', [App\Http\Controllers\Api\ObjectController::class, 'compose_show_module']);

        Route::get('history/{model}/{id}', [App\Http\Controllers\Api\HistoryController::class, 'index']);
        Route::get('history/{model}/{id}/{module?}', [App\Http\Controllers\Api\HistoryController::class, 'index']);
        Route::get('history/table/{model}/{id}', [App\Http\Controllers\Api\HistoryController::class, 'table']);

        Route::post('field_sections/change-sort', [App\Http\Controllers\Api\FieldSectionController::class, 'changeSort']);
        Route::post('field_sections/hide', [App\Http\Controllers\Api\FieldSectionController::class, 'hide']);
        Route::delete('field_sections/{id}', [App\Http\Controllers\Api\FieldSectionController::class, 'delete']);
        Route::post('field_sections', [App\Http\Controllers\Api\FieldSectionController::class, 'store']);
        Route::put('field_sections/{id}', [App\Http\Controllers\Api\FieldSectionController::class, 'update']);

        Route::get('field/{entity}/compare/{module}', [App\Http\Controllers\Api\FieldController::class, 'get_compare']);
        Route::post('field/{entity}/compare/{module}', [App\Http\Controllers\Api\FieldController::class, 'set_compare']);
        Route::get('field/{field}/modules', [App\Http\Controllers\Api\FieldController::class, 'get_field_modules']);

        

        Route::post('field/change-sort', [App\Http\Controllers\Api\FieldController::class, 'changeSort']);
        Route::post('field/hide/{id}', [App\Http\Controllers\Api\FieldController::class, 'hide']);
        Route::post('field/hide_batch', [App\Http\Controllers\Api\FieldController::class, 'hide_batch']);
        Route::delete('field/{id}', [App\Http\Controllers\Api\FieldController::class, 'delete']);
        Route::post('field/status', [App\Http\Controllers\Api\FieldController::class, 'status_store']);
        //Route::post('field', [App\Http\Controllers\Api\FieldController::class, 'store']);
        Route::post('field', [App\Http\Controllers\Api\FieldController::class, 'store']);
        Route::put('field/{id}', [App\Http\Controllers\Api\FieldController::class, 'update']);

        Route::post('files/store', [App\Http\Controllers\Api\FileController::class, 'store']);

        Route::get('entities', [App\Http\Controllers\Api\EntityController::class, 'list']);
        Route::post('entities', [App\Http\Controllers\Api\EntityController::class, 'update']);
        Route::get('entities/compose', [App\Http\Controllers\Api\EntityController::class, 'compose_list']);
        Route::get('entities/{slug}/menu', [App\Http\Controllers\Api\EntityController::class, 'get_menu']);
        Route::put('entities/{slug}/menu', [App\Http\Controllers\Api\EntityController::class, 'set_menu']);
        Route::put('entities/{slug}/menu/all', [App\Http\Controllers\Api\EntityController::class, 'set_menu_all']);
        Route::put('entities/{slug}/menu/role/{role_id}', [App\Http\Controllers\Api\EntityController::class, 'set_menu_role']);

        Route::get('tabs/trash', [App\Http\Controllers\Api\TabController::class, 'trash']);
        Route::get('tabs/{entity}', [App\Http\Controllers\Api\TabController::class, 'get']);
        Route::put('tabs/{slug}/permissions', [App\Http\Controllers\Api\TabController::class, 'permissions']);


        
        //Route::post('entities/disable', [App\Http\Controllers\Api\EntityController::class, 'disable']);

        
        
        Route::get('settings/account', [App\Http\Controllers\Api\SettingsController::class, 'account']);
        Route::put('settings/account', [App\Http\Controllers\Api\SettingsController::class, 'account_update']);

        Route::get('routes', [App\Http\Controllers\Api\RouteController::class, 'list']);
        Route::post('routes', [App\Http\Controllers\Api\RouteController::class, 'store']);
        Route::put('routes/batch', [App\Http\Controllers\Api\RouteController::class, 'batch']);
        Route::delete('routes', [App\Http\Controllers\Api\RouteController::class, 'delete']);
        Route::get('routes/{id}/tasks', [App\Http\Controllers\Api\RouteController::class, 'tasks']);
        Route::put('routes/{id}/tasks', [App\Http\Controllers\Api\RouteController::class, 'update_tasks']);

        Route::put(
            'logistic_tasks/{id}/set_products', 
            [App\Http\Controllers\Api\TaskController::class, 'set_products']
        );

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
