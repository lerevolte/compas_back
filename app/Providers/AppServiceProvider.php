<?php

namespace App\Providers;

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const LANDING_CONTENT_MODELS = [
        \App\Models\Article::class,
        \App\Models\Guide::class,
        \App\Models\Knowledge::class,
        \App\Models\Faq::class,
        \App\Models\BlogCategory::class,
        \App\Models\GuideCategory::class,
        \App\Models\KnowledgeCategory::class,
        \App\Models\FaqCategory::class,
    ];

    public function register()
    {
        $this->app->singleton('settings', function () {
            $settings = \App\Models\Settings::get();//get_settings();
            

            return $settings;
        });
        
    }

    public function boot()
    {
        $forget = fn () => Cache::forget(SpaController::ALLOWED_URLS_CACHE_KEY);

        foreach (self::LANDING_CONTENT_MODELS as $model) {
            $model::saved($forget);
            $model::deleted($forget);
            $model::restored($forget);
            $model::forceDeleted($forget);
        }
    }

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
