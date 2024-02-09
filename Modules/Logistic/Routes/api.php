<?php

use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\Logistic\Http\Controllers\Api\TableController;
use Modules\Logistic\Http\Controllers\Api\LogisticController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware([
    'auth:api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
])->prefix('logistic')->group(function () {
    Route::get(
        'tables/{slug}', 
        [TableController::class, 'get']
    )->name('tables.get');

    Route::post(
        'tables/{slug}', 
        [TableController::class, 'set']
    )->name('tables.set');

    Route::get(
        'settings', 
        [LogisticController::class, 'get_settings']
    )->name('settings.get');

    Route::post(
        'settings', 
        [LogisticController::class, 'set_settings']
    )->name('settings.set');

    Route::get(
        'companies', 
        [LogisticController::class, 'companies']
    )->name('companies');
});
