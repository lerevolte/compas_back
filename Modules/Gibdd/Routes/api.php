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

Route::middleware([
    'auth:api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
])->prefix('gibdd')->group(function () {
    Route::post(
        'check', 
        [GibddController::class, 'check']
    )->name('gibdd.check');

    Route::get(
        'find_by_num/{num}', 
        [GibddController::class, 'findByNum']
    )->name('gibdd.find_by_num');

});
