<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;
use App\Services\TenantService;
use App\Services\CrudService;

class SpaController extends Controller
{
    /**
     * Список URL лендинга, зависящих от контента в БД.
     * Сбрасывается из AppServiceProvider при сохранении контента;
     * TTL остаётся страховкой на случай записи в обход Eloquent.
     */
    public const ALLOWED_URLS_CACHE_KEY = 'landing:allowed-urls';

    /**
     * Get the SPA view.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        if(request()->n) {
            $crud = new CrudService;
            $s = new TenantService($crud);
            //$s->syncEntity('car_categories');
            //$s->syncEntity('car_marks');
            $s->syncEntity('car_models');
            die();
            tenancy()->central(function ()  {
                $users = \App\Models\User::get();
                foreach($users as $user) {
                    $cache_name = tenant('id').':sidebarmenu-'.$user->id;
                    cache()->getMemcached()->delete($cache_name);
                    $cache_name = tenant('id').':settings-'.$user->id;
                    cache()->getMemcached()->delete($cache_name);
                }
            });
            die();
            $tenants = \App\Models\Tenant::get();  // Получаем всех тенантов

            foreach ($tenants as $tenant) {
                tenancy()->initialize($tenant);  // Инициализируем подключение к базе данных тенанта
                try {
                    if (\Schema::hasTable('modules')) {
                        $slugsToDelete = ['osago', 'gibdd'];
                        
                        $deletedCount = \DB::table('modules')
                            ->whereIn('slug', $slugsToDelete)
                            ->update([
                                'enabled' => 0
                            ]);
                        $users = \App\Models\User::get();
                        foreach($users as $user) {
                            $cache_name = tenant('id').':sidebarmenu-'.$user->id;
                            cache()->getMemcached()->delete($cache_name);
                            $cache_name = tenant('id').':settings-'.$user->id;
                            cache()->getMemcached()->delete($cache_name);
                        }

                        
                        
                        echo "Tenant {$tenant->id}: Deleted {$deletedCount} records<br>";
                    } else {
                        echo "Tenant {$tenant->id}: Table 'sidebar_items' not found<br>";
                    }
                } catch (\Exception $e) {
                    echo "Tenant {$tenant->id}: Error - " . $e->getMessage() . "<br>";
                } finally {
                    tenancy()->end();
                }
            }
            die();
        }
        // Nuxt запрашивает _payload.json при каждом SPA-переходе. Файлы лежат в
        // public/landing/, но catch-all роут перехватывает запрос и раньше отдавал HTML.
        if (!tenant('id')) {
            $payload = $this->landingPayload();
            if ($payload !== null) {
                return $payload;
            }
        }

        $account = null;
        $allow_dirs = $this->staticLandingUrls();

        if(tenant('id')){
            $tenant = tenant('id');
            $account = tenancy()->central(function () use ($tenant) {
                $account = \App\Models\Account::where('name', tenant('id'))->first();
                if(!$account)
                    $account = \App\Models\Account::where('name->value', tenant('id'))->first();

                return $account;
            });
        }
        if(tenant('id') && $account || !tenant('id') && strstr(url()->full(), 'objects')) {
            $path = public_path('index2.html');
        } else {
            if(in_array(url()->current(), $allow_dirs)) {
                $path = public_path('landing/index.html');
            } else {
                $allow_dirs = array_merge($allow_dirs, $this->dynamicLandingUrls());

                if(!in_array(url()->current(), $allow_dirs)) {
                    info(url()->current());
                    info('abort');
                    abort(404);
                }
            }

            $url = str_replace('https://compas.pro/', '', url()->current());
            $url_parts = explode('/', $url);
            if(file_exists(public_path("landing/{$url}/index.html"))) {
                $path = public_path("landing/{$url}/index.html");
            }
            elseif(file_exists(public_path("landing/{$url_parts[0]}/index.html"))) {
                $path = public_path('landing/200.html');
            }
            else {
                // Детальные страницы гайдов не пререндерятся — отдаём SPA-оболочку.
                $k = array_search('guides', $url_parts);
                if($k !== false && isset($url_parts[$k+1])){
                    $path = public_path('landing/200.html');
                } else
                    $path = public_path('landing/index.html');
            }
        }

        abort_unless(file_exists($path), 400, 'Make sure to run npm run build!');

        return file_get_contents($path);
    }

    /**
     * Nuxt тянет _payload.json при каждом SPA-переходе. Собранные файлы лежат в
     * public/landing/, а catch-all роут перехватывал запрос и отдавал HTML —
     * фронт логировал "Cannot load payload ... is not valid JSON".
     */
    private function landingPayload()
    {
        $path = trim(request()->path(), '/');

        if (!str_ends_with($path, '_payload.json') || str_contains($path, '..')) {
            return null;
        }

        $file = public_path('landing/' . $path);

        if (!is_file($file)) {
            abort(404);
        }

        return response()->file($file, ['Content-Type' => 'application/json']);
    }

    /**
     * Статические маршруты лендинга. Используется и для отдачи страниц (__invoke),
     * и для списка пререндера (pages()), чтобы списки не разъезжались.
     */
    private function staticLandingUrls(): array
    {
        return [
            "https://compas.pro",
            "https://compas.pro/404",
            "https://compas.pro/payment",
            "https://compas.pro/tariffs",
            "https://compas.pro/contacts",
            "https://compas.pro/auth/entry",
            "https://compas.pro/auth/registration",
            "https://compas.pro/auth/accounts",
            "https://compas.pro/docs",
            "https://compas.pro/docs/license",
            "https://compas.pro/docs/politics",
            "https://compas.pro/guides",
            "https://compas.pro/guides-category",
            "https://compas.pro/articles",
            "https://compas.pro/articles-category",
            "https://compas.pro/questions",
            "https://compas.pro/questions-category",
            "https://compas.pro/knowledge",
            "https://compas.pro/knowledge-category",
            "https://compas.pro/products",
            "https://compas.pro/products/fines",
            "https://compas.pro/products/fines/list",
            "https://compas.pro/products/fines/po-sts",
            "https://compas.pro/products/fines/po-voditelskomu-udostovereniyu",
            "https://compas.pro/products/fines/po-nomeru-postanovleniya",
            "https://compas.pro/products/fines/po-nomeru-avto",
            "https://compas.pro/products/fines/po-inn",
            "https://compas.pro/products/distance",
            "https://compas.pro/products/distance/rasstoyanie-ot-mkad",
            "https://compas.pro/products/distance/rasstoyanie-ot-kad",
            "https://compas.pro/products/osago",
            "https://compas.pro/products/osago/processing",
            "https://compas.pro/products/logisticheskaya-programma",
        ];
    }

    /**
     * Маршруты, которые зависят от контента в БД.
     */
    private function dynamicLandingUrls(): array
    {
        return Cache::remember(self::ALLOWED_URLS_CACHE_KEY, now()->addMinutes(10), function () {
            $sections = [
                'articles-category' => ['table' => 'blog_categories',      'active' => false],
                'questions-category' => ['table' => 'faq_categories',       'active' => false],
                'knowledge-category' => ['table' => 'knowledge_categories', 'active' => false],
                'guides-category'    => ['table' => 'guide_categories',     'active' => false],
                'questions'          => ['table' => 'faq',                  'active' => true],
                'articles'           => ['table' => 'articles',             'active' => true],
                'knowledge'          => ['table' => 'knowledge',            'active' => true],
                'guides'             => ['table' => 'guides',               'active' => true],
            ];

            $urls = [];

            foreach ($sections as $prefix => $section) {
                $query = \DB::table($section['table'])->whereNull('deleted_at');

                if ($section['active']) {
                    $query->where('is_active', 1);
                }

                foreach ($query->pluck('slug') as $slug) {
                    $decoded = json_decode($slug, true);
                    if (isset($decoded['value'])) {
                        $slug = $decoded['value'];
                    }
                    $urls[] = 'https://compas.pro/' . $prefix . '/' . $slug;
                }
            }

            return $urls;
        });
    }

    public function pages()
    {
        return response()->json(array_merge($this->staticLandingUrls(), $this->dynamicLandingUrls()));
    }
}
