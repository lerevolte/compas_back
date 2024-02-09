<?php

use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\Instructions\Http\Controllers\Api\CategoryController;
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
])->prefix('instructions')->group(function () {
    Route::get(
            'categories', 
            [CategoryController::class, 'list']
        );
    Route::post(
        'categories', 
        [CategoryController::class, 'store']
    );
    Route::put(
        'categories/{id}', 
        [CategoryController::class, 'update']
    );
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
});