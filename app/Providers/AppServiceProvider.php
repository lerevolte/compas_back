<?php

namespace App\Providers;

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Контент, от которого зависит список URL лендинга (см. SpaController::dynamicLandingUrls).
     * Таблицы этих моделей совпадают с теми, что читает контроллер.
     */
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

    public function boot()
    {
        // Без сброса новая или снятая с публикации страница до 10 минут отдаёт
        // не тот статус: 404 на свежей статье и 200 на снятой.
        // Контент лендинга живёт в центральной БД, поэтому ключ здесь без tenant-префикса.
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
