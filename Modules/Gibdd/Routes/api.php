<?php

use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\Gibdd\Http\Controllers\Api\GibddController;
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
Route::prefix('gibdd')->group(function () {
    Route::get(
        'check_by_req', 
        [GibddController::class, 'check_by_req']
    )->name('gibdd.check_by_req');
    Route::post(
        'moneta_pay', 
        [GibddController::class, 'moneta_pay']
    );
});
Route::middleware([
    'auth:api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
])->prefix('gibdd')->group(function () {
    Route::post(
        'check', 
        [GibddController::class, 'check']
    )->name('gibdd.check');
    Route::post(
        'check_autodor', 
        [GibddController::class, 'check_autodor']
    )->name('gibdd.check_autodor');

    Route::get(
        'find_by_num/{num}', 
        [GibddController::class, 'findByNum']
    )->name('gibdd.find_by_num');

    Route::post(
        'pay/{id}', 
        [GibddController::class, 'pay']
    );

    Route::post(
        'moneta_pay/{id}', 
        [GibddController::class, 'moneta_pay']
    );

});
