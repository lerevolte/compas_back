<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ObjectController;

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

// Route::middleware([
//         InitializeTenancyByDomain::class,
//         PreventAccessFromCentralDomains::class
//     ])->group(function () {
//         Route::name('api.')->namespace('App\Http\Controllers\Api')->group(function () {
//             Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//                 return $request->user();
//             });
//             //Route::group(['middleware' => 'auth:api'], function() {

//                 Route::get(
//                     'objects/{model}', 
//                     [App\Http\Controllers\Api\ObjectController::class, 'list']
//                 )->name('objects.list');

//                 Route::get(
//                     'objects/{model}/edit', 
//                     [App\Http\Controllers\Api\ObjectController::class, 'edit_list']
//                 )->name('edit_list');

//             //});
//         });
// });

// Route::middleware('tenancy')->group(function () {
//     Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//         return $request->user();
//     });
//     Route::group(['middleware' => 'auth:api'], function() {

//         Route::get(
//             'objects/{model}', 
//             [App\Http\Controllers\Api\ObjectController::class, 'list']
//         )->name('api.objects.list');

//         Route::get(
//             'objects/{model}/edit', 
//             [App\Http\Controllers\Api\ObjectController::class, 'edit_list']
//         )->name('api.objects.edit_list');

//     });
// });

