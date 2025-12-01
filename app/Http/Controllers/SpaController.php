<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;
use App\Services\TenantService;
use App\Services\CrudService;

class SpaController extends Controller
{
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
        $account = null;
        $allow_dirs = [
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
            //"https://compas.pro/guides",
            "https://compas.pro/guides-category",
            "https://compas.pro/articles",
            "https://compas.pro/articles-category",
            "https://compas.pro/questions",
            "https://compas.pro/questions-category",
            "https://compas.pro/knowledge",
            "https://compas.pro/knowledge-category",
            "https://compas.pro/products/fines",
            "https://compas.pro/products/fines/list",
            "https://compas.pro/products/fines/po-sts",
            "https://compas.pro/products/fines/po-voditelskomu-udostovereniyu",
            "https://compas.pro/products/fines/po-nomeru-postanovleniya",
            "https://compas.pro/products/fines/po-nomeru-avto",
            "https://compas.pro/products/fines/po-inn",
            "https://compas.pro/products/distance/mkad",
            "https://compas.pro/products/distance/kad",
            "https://compas.pro/products/distance",
            "https://compas.pro/osago",
            "https://compas.pro/osago/processing"
        ];

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
                $allow_dirs2 = Cache::get('key', function () {
                    $allow_dirs2 = [];
                    $faq_categories = \DB::table('faq_categories')->whereNull('deleted_at')->pluck('slug');
                    $blog_categories = \DB::table('blog_categories')->whereNull('deleted_at')->pluck('slug');
                    $knowledge_categories = \DB::table('knowledge_categories')->whereNull('deleted_at')->pluck('slug');
                    $guide_categories = \DB::table('guide_categories')->whereNull('deleted_at')->pluck('slug');
                    $faq = \DB::table('faq')->whereNull('deleted_at')->where('is_active', 1)->pluck('slug');
                    $blog = \DB::table('articles')->whereNull('deleted_at')->where('is_active', 1)->pluck('slug');
                    $knowledge = \DB::table('knowledge')->whereNull('deleted_at')->where('is_active', 1)->pluck('slug');
                    $guides = \DB::table('guides')->whereNull('deleted_at')->where('is_active', 1)->pluck('slug');
                    foreach ($blog_categories as $slug) {
                        $s = json_decode($slug,true);
                        if(isset($s['value']))
                            $slug = $s['value'];
                        $allow_dirs2[] = implode('/', ['https://compas.pro/articles-category', $slug]);
                    }

                    foreach ($faq_categories as $slug) {
                        $s = json_decode($slug,true);
                        if(isset($s['value']))
                            $slug = $s['value'];
                        $allow_dirs2[] = implode('/', ['https://compas.pro/questions-category', $slug]);
                    }
                    
                    foreach ($knowledge_categories as $slug) {
                        $s = json_decode($slug,true);
                        if(isset($s['value']))
                            $slug = $s['value'];
                        $allow_dirs2[] = implode('/', ['https://compas.pro/knowledge-category', $slug]);
                    }

                    foreach ($guide_categories as $slug) {
                        $s = json_decode($slug,true);
                        if(isset($s['value']))
                            $slug = $s['value'];
                        $allow_dirs2[] = implode('/', ['https://compas.pro/guides-category', $slug]);
                    }

                    foreach ($faq as $slug) {
                        $s = json_decode($slug,true);
                        if(isset($s['value']))
                            $slug = $s['value'];
                        $allow_dirs2[] = implode('/', ['https://compas.pro/questions', $slug]);
                    }
                    
                    foreach ($blog as $slug) {
                        $s = json_decode($slug,true);
                        if(isset($s['value']))
                            $slug = $s['value'];
                        $allow_dirs2[] = implode('/', ['https://compas.pro/articles', $slug]);
                    }

                    foreach ($knowledge as $slug) {
                        $s = json_decode($slug,true);
                        if(isset($s['value']))
                            $slug = $s['value'];
                        $allow_dirs2[] = implode('/', ['https://compas.pro/knowledge', $slug]);
                    }

                    foreach ($guides as $slug) {
                        $s = json_decode($slug,true);
                        if(isset($s['value']))
                            $slug = $s['value'];
                        $allow_dirs2[] = implode('/', ['https://compas.pro/guides', $slug]);
                    }

                    return $allow_dirs2;
                });
                
                $allow_dirs = array_merge($allow_dirs, $allow_dirs2);

                if(!in_array(url()->current(), $allow_dirs) && !strstr(url()->current(), '_payload.json')) {
                    info(url()->current());
                    info('abort');
                    abort(404);
                }
            }
            
            $url = str_replace('https://compas.pro/', '', url()->current());
            $url_parts = explode('/', $url);
            if(!strstr($url, '_payload.json') && file_exists(public_path("landing/{$url}/index.html"))) {
                $path = public_path("landing/{$url}/index.html");
            }
            elseif(file_exists(public_path("landing/{$url_parts[0]}/index.html"))) {
                $path = public_path('landing/200.html');
            }
            else {
                if($k = array_search('guides', $url_parts) && isset($url_parts[$k+1])){
                    $path = public_path('landing/200.html');
                } else
                    $path = public_path('landing/index.html');
            }
        }

        abort_unless(file_exists($path), 400, 'Make sure to run npm run build!');

        return file_get_contents($path);
    }

    public function pages()
    {
        
        $allow_dirs = [
            "https://compas.pro",
            "https://compas.pro/payment",
            "https://compas.pro/tariffs",
            "https://compas.pro/contacts",
            "https://compas.pro/auth/entry",
            "https://compas.pro/auth/registration",
            "https://compas.pro/auth/accounts",
            "https://compas.pro/docs",
            "https://compas.pro/docs/license",
            "https://compas.pro/docs/politics",
            "https://compas.pro/articles",
            "https://compas.pro/articles-category",
            "https://compas.pro/questions",
            "https://compas.pro/questions-category",
            "https://compas.pro/knowledge",
            "https://compas.pro/knowledge-category",
            "https://compas.pro/products/fines",
            "https://compas.pro/products/fines/list",
            "https://compas.pro/products/fines/po-sts",
            "https://compas.pro/products/fines/po-voditelskomu-udostovereniyu",
            "https://compas.pro/products/fines/po-nomeru-postanovleniya",
            "https://compas.pro/products/fines/po-nomeru-avto",
            "https://compas.pro/products/fines/po-inn",
            "https://compas.pro/products/distance/mkad",
            "https://compas.pro/products/distance/kad",
            "https://compas.pro/products/distance"
        ];

        $faq_categories = \DB::table('faq_categories')->whereNull('deleted_at')->pluck('slug');
        $blog_categories = \DB::table('blog_categories')->whereNull('deleted_at')->pluck('slug');
        $knowledge_categories = \DB::table('knowledge_categories')->whereNull('deleted_at')->pluck('slug');
        $faq = \DB::table('faq')->whereNull('deleted_at')->pluck('slug');
        $blog = \DB::table('articles')->whereNull('deleted_at')->pluck('slug');
        $knowledge = \DB::table('knowledge')->whereNull('deleted_at')->pluck('slug');
        foreach ($blog_categories as $slug) {
            $s = json_decode($slug,true);
            if(isset($s['value']))
                $slug = $s['value'];
            $allow_dirs[] = implode('/', ['https://compas.pro/articles-category', $slug]);
        }

        foreach ($faq_categories as $slug) {
            $s = json_decode($slug,true);
            if(isset($s['value']))
                $slug = $s['value'];
            $allow_dirs[] = implode('/', ['https://compas.pro/questions-category', $slug]);
        }
        
        foreach ($knowledge_categories as $slug) {
            $s = json_decode($slug,true);
            if(isset($s['value']))
                $slug = $s['value'];
            $allow_dirs[] = implode('/', ['https://compas.pro/knowledge-category', $slug]);
        }

        foreach ($faq as $slug) {
            $s = json_decode($slug,true);
            if(isset($s['value']))
                $slug = $s['value'];
            $allow_dirs[] = implode('/', ['https://compas.pro/questions', $slug]);
        }
        
        foreach ($blog as $slug) {
            $s = json_decode($slug,true);
            if(isset($s['value']))
                $slug = $s['value'];
            $allow_dirs[] = implode('/', ['https://compas.pro/articles', $slug]);
        }

        foreach ($knowledge as $slug) {
            $s = json_decode($slug,true);
            if(isset($s['value']))
                $slug = $s['value'];
            $allow_dirs[] = implode('/', ['https://compas.pro/knowledge', $slug]);
        }

        return response()->json($allow_dirs);
    }

}