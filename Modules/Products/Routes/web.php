<?php
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
Route::middleware([
    //'auth:api',
    'auth:api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
    ])->prefix('api')->group(function () {
        Route::prefix('products')->group(function() {
            Route::get('/search', 'ProductsController@search');
        });

        Route::prefix('remnants')->group(function() {
            Route::get('/search', 'RemnantController@search');
        });
});
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Route::prefix('products')->group(function() {
    //     Route::get('/', 'ProductsController@index');
    //     Route::get('/find', 'ProductsController@find');
    //     Route::get('/search', 'ProductsController@search');
    // });
    // Route::prefix('categories')->group(function() {
    //     Route::get('/', 'CategoryController@index')->name('products.categories');
    //     Route::post('/', 'CategoryController@store')->name('products.categories.store');
    //     Route::get('/{category}', 'CategoryController@show')->name('categories.show');
    // });
});
