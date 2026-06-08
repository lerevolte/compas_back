<?php

use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\Bitrix24\Http\Controllers\Api\Bitrix24Controller;
use Modules\Bitrix24\Http\Controllers\Bitrix24Controller as Bitrix24WebController;
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
])->prefix('bitrix24')->group(function () {
    Route::get(
            'sync',
            [Bitrix24Controller::class, 'sync']
        );
});

// Вебхук из Bitrix24 — БЕЗ auth (Битрикс не авторизуется). Под /api, т.к.
// nginx отдаёт /bitrix24/* как статику SPA и до Laravel веб-роут не доходит.
// Тенант определяется по домену. URL: /api/bitrix24/deal-hook?id=#{deal.ID}
Route::middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
])->prefix('bitrix24')->group(function () {
    Route::match(['get', 'post'], 'deal-hook', [Bitrix24WebController::class, 'dealHook']);
});

// Управление конфигурацией модуля через API (nginx блокирует /bitrix24/ как SPA).
// Требует авторизации через Bearer-токен.
Route::middleware([
    'auth:api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
])->prefix('bitrix24')->group(function () {
    Route::get('config',  [Bitrix24WebController::class, 'getConfig']);
    Route::post('config', [Bitrix24WebController::class, 'setConfig']);
});