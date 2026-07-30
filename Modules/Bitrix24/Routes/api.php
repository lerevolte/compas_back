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
    // any: Bitrix24 шлёт POST (исходящий вебхук) или GET (роботы/тест из
    // браузера); любой другой метод раньше получал 405 ещё до контроллера
    // и в логи ничего не попадало.
    Route::any('deal-hook', [Bitrix24WebController::class, 'dealHook']);
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

// Синхронизация сущностей deals/contacts/companies (политика avixo: код общий,
// включается наличием сущностей в тенанте — см. B24EntitySync::ready).
Route::middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
])->prefix('bitrix24')->group(function () {
    Route::any('entity-hook', [\Modules\Bitrix24\Http\Controllers\B24EntityController::class, 'entityHook']);
});

Route::middleware([
    'auth:api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
])->prefix('bitrix24')->group(function () {
    Route::get('deal-stages', [\Modules\Bitrix24\Http\Controllers\B24EntityController::class, 'stages']);
    Route::post('deals/{id}/stage', [\Modules\Bitrix24\Http\Controllers\B24EntityController::class, 'changeStage']);
});