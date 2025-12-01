<?php

use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\Osago\Http\Controllers\Api\OsagoController;
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
])->prefix('osago')->group(function () {
    Route::get(
        'offers/{id}', 
        [OsagoController::class, 'offers']
    );
    Route::get(
        'offers/{id}/link', 
        [OsagoController::class, 'link']
    );

});
