<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Broadcast::routes();//auth:api
        //[ "middleware" => ["web", "auth", "tenant"] ]
        Broadcast::routes(['middleware' => [
            'auth:api',
            //'universal', 
            InitializeTenancyByDomain::class
        ]]);
        // Broadcast::routes(['middleware' => [
        //     'auth:api',
        //     InitializeTenancyByDomain::class,
        //     //PreventAccessFromCentralDomains::class
        // ]]);
        
        
        require base_path('routes/channels.php');
    }
}
