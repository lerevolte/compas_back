<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// if(!function_exists('isMobile')) {
//     function isMobile() {
//         return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
//     };
// };
// if(!function_exists('get_settings')) {
//     function get_settings() {
//         return \App\Models\Settings::get();
//     };
// };
Route::group(['middleware' => ['web']], function() {
    Route::get('/', function() {
        
        return view('home');
    });

    Route::get('/privacy', function() {
        
        return view('privacy');
    });
});
Route::get('/clear', function () {
    \Cache::forever('settings-14', 'value');
    //cache()->rememberForever('settings-14', function() {return 111;});
    $settings = Illuminate\Support\Facades\Cache::get('settings-14');
    print_r($settings);
    die();
    // Artisan::call('storage:link');
    // Artisan::call('cache:clear');
    // Artisan::call('config:cache');
    // Artisan::call('view:clear');
    // Artisan::call('route:clear');
    // Artisan::call('route:list');
    // App\Models\Tenant::all()->runForEach(function () {
    //     App\Models\User::factory()->create();
    // });
    return "Кэш очищен.";
});
// Route::group(['middleware' => ['web', 'auth', InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class]], function() {
//     Route::get('/', [
//         'as' => 'home',
//         'uses' => 'App\Http\Controllers\PageController@home'
//     ]);

//     Route::get('/profile/', [App\Http\Controllers\UserController::class, 'profile'])->name('users.profile');
//     Route::get('/profile/edit-password', [App\Http\Controllers\UserController::class, 'edit_password'])->name('users.edit_password');
//     Route::post('/profile/update-password', [App\Http\Controllers\UserController::class, 'update_password'])->name('users.update_password');
//     Route::put('/profile/{user}', [App\Http\Controllers\UserController::class, 'update_profile'])->name('users.update_profile');

//     Route::get('/settings/', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index')->middleware('check_permission:read_settings');
//     Route::get('/settings/zones', [App\Http\Controllers\SettingsController::class, 'zones'])->name('settings.zones')->middleware('check_permission:read_settings');
//     Route::put('/settings/update/{account}', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update')->middleware('check_permission:read_settings');
//     Route::get('/settings/users/', [App\Http\Controllers\SettingsController::class, 'users'])->name('settings.users')->middleware('check_permission:read_users');
//     Route::get('/settings/roles/', [App\Http\Controllers\SettingsController::class, 'roles'])->name('settings.roles')->middleware('check_permission:read_roles');

//     Route::get('/balance/', [
//         'uses' => 'App\Http\Controllers\BalanceController@index'
//     ])->name('balance');
//     Route::post('/balance/plus', [
//         'uses' => 'App\Http\Controllers\BalanceController@plus'
//     ]);
//     Route::post('/balance/minus', [
//         'uses' => 'App\Http\Controllers\BalanceController@minus'
//     ]);

//     Route::delete('/roles/{role}', [App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy')->middleware('check_permission:delete_roles');
//     Route::get('/roles/create', [App\Http\Controllers\RoleController::class, 'create'])->name('roles.create');
//     Route::get('/roles/edit/{id}', [App\Http\Controllers\RoleController::class, 'edit'])->name('roles.edit');
//     Route::post('/roles/store', [App\Http\Controllers\RoleController::class, 'store'])->name('roles.store');
//     Route::post('/roles/', [App\Http\Controllers\RoleController::class, 'update'])->name('roles.update')->middleware('check_permission:write_roles');
//     Route::post('/roles/change-sort', [App\Http\Controllers\RoleController::class, 'changeSort'])->name('roles.sort')->middleware('check_permission:write_roles');

//     Route::get('/users/get_drivers', [App\Http\Controllers\UserController::class, 'get_drivers'])->name('users.get_drivers');
//     Route::post('/users/store', [App\Http\Controllers\UserController::class, 'store'])->name('users.store');
//     Route::get('/users/create', [App\Http\Controllers\UserController::class, 'create'])->name('users.create')->middleware('check_permission:write_users');
//     Route::get('/users/{user}', [App\Http\Controllers\UserController::class, 'edit'])->name('users.edit')->middleware('check_permission:write_users');
//     Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('users.update')->middleware('check_permission:write_users');
//     Route::delete('/users/{id}', [App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy')->middleware('check_permission:delete_users');

//     Route::post('/users/change-sort', [App\Http\Controllers\UserController::class, 'changeSort'])->name('users.sort')->middleware('check_permission:write_users');
//     Route::post('/users/restore/{id}', [App\Http\Controllers\UserController::class, 'restore'])->name('users.restore')->middleware('check_permission:write_users');
//     Route::get('/users/', [App\Http\Controllers\UserController::class, 'index'])->name('users.index')->middleware('check_permission:write_users');

//     Route::resource('field_sections', App\Http\Controllers\FieldSectionController::class);
//     Route::post('/fields/change-sort', [App\Http\Controllers\FieldController::class, 'changeSort']);
//     Route::post('/field_sections/change-sort', [App\Http\Controllers\FieldSectionController::class, 'changeSort']);
//     Route::post('/field_sections/hide', [App\Http\Controllers\FieldSectionController::class, 'hide']);
//     Route::post('/field_sections/destroy', [App\Http\Controllers\FieldSectionController::class, 'destroy']);

//     Route::get('/field/', [
//         'as' => 'field',
//         'uses' => 'App\Http\Controllers\FieldController@field'
//     ]);
//     Route::get('/field/properties/{type}', [
//         'as' => 'field_properties',
//         'uses' => 'App\Http\Controllers\FieldController@renderProperties'
//     ]);
//     Route::post('/field/hide/', [
//         'as' => 'field_hide',
//         'uses' => 'App\Http\Controllers\FieldController@hide'
//     ]);
//     Route::post('/field/show/', [
//         'as' => 'field_show',
//         'uses' => 'App\Http\Controllers\FieldController@show'
//     ]);
//     Route::get('/field/add_color/', [
//         'as' => 'field_add_color',
//         'uses' => 'App\Http\Controllers\FieldController@add_color'
//     ]);
//     Route::match(['put', 'post'], '/field/store/', [
//         'as' => 'field_store',
//         'uses' => 'App\Http\Controllers\FieldController@store'
//     ]);
//     Route::get('/field/edit/', [
//         'as' => 'field_edit',
//         'uses' => 'App\Http\Controllers\FieldController@edit'
//     ]);
//     Route::post('/field/update/', [
//         'as' => 'field_update',
//         'uses' => 'App\Http\Controllers\FieldController@update'
//     ]);
//     Route::post('/field/destroy/{id}', [
//         'as' => 'field.destroy',
//         'uses' => 'App\Http\Controllers\FieldController@destroy'
//     ]);

//     Route::put('/filters/{filter}/add_field', [App\Http\Controllers\FilterController::class, 'add_field'])->name('filters.add_field');
//     Route::put('/filters/show_field', [App\Http\Controllers\FilterController::class, 'show_field'])->name('filters.show_field');
    
//     Route::resource('filters', App\Http\Controllers\FilterController::class);

//     Route::get('/objects/{model}', [App\Http\Controllers\ObjectController::class, 'list'])->name('objects.list');
//     Route::get('/objects/{model}/get', [App\Http\Controllers\ObjectController::class, 'get'])->name('objects.get');
//     Route::get('/objects/{model}/show/{id}', [App\Http\Controllers\ObjectController::class, 'show'])->name('objects.show');

//     Route::get('/modules/install/{module}', [App\Http\Controllers\ModuleController::class, 'install'])->name('modules.install');
// });

// Route::group(['prefix' => 'admin'], function () {
    
//     Voyager::routes();
// });


// Route::get('/login', function () {
//     if(\Auth::user()) {
//         if(\Auth::user()->hasRole('driver')) {
//             //$driver = \App\Models\Driver::where('user_id', \Auth::user()->id)->first();
//             return redirect()->route('drivers.show', ['driver' => $driver->id]);
//         } else {
//             return redirect()->route('users.profile');
//         }
//     } else {
//         return redirect('/admin/login');
//     }
    
// })->name('login');