<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('settings', function () {
            $settings = \App\Models\Settings::get();//get_settings();
            

            return $settings;
        });
        
    }

    // public function boot()
    // {
    //     $this->warmUpCache();
    // }

    // protected function warmUpCache()
    // {
    //     if ($this->app->runningInConsole()) return;

    //     $cacheKey = 'cache_warmed_'.tenant('id');
        
    //     if (!cache()->has($cacheKey)) {
    //         dispatch(function() {
    //             \Auth::loginUsingId(1);
    //             app('user_settings'); // Прогрев
    //         })->afterResponse(); // Не блокирует ответ
            
    //         cache()->put($cacheKey, true, now()->addDay());
    //     }
    // }
}
