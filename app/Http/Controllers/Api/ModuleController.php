<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use App\Models\ModuleCategory;
use Carbon\Carbon;
use App\Services\SearchService;

class ModuleController extends Controller
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function categories()
    {
        $categories = ModuleCategory::all();

        return response()->json($categories);
    }

    public function list(Request $request, $slug = null)
    {
        if(!$slug) {
            $modules = Module::all();
            $data = array();
            $local_modules = \DB::table('modules')->get()->keyBy('slug');
            foreach ($modules as $module) {
                if($module->get('is_hidden') || !$module->get('title'))
                    continue;
                $images = array();
                if($images_res = $module->get('images')) {
                    foreach($images_res as $image) {
                        $path = str_replace('storage/', '', \Storage::disk('public')->url('assets/modules/'.$module->getName().'/'.$image));
                        $images[] = $path;
                    }
                }
                $logo_path = '';
                $logo_res = $module->get('logo');
                if($logo_res) {
                    if(!file_exists(public_path('assets/modules/'.$module->getName().'/logo/'.$logo_res))) {
                        $file_path = getcwd().'/../Modules/'.$module->getName().'/'.$logo_res;
                        $path = dirname(public_path('assets/modules/'.$module->getName().'/logo/'.$logo_res));
                        if(!\File::isDirectory($path))
                            \File::makeDirectory($path, 0777, true, true);
                        \File::copy($file_path, public_path('assets/modules/'.$module->getName().'/logo/'.$logo_res));
                    }
                    $logo_path = str_replace('storage/', '', \Storage::disk('public')->url('assets/modules/'.$module->getName().'/logo/'.$logo_res));
                }
                $entities = $module->get('entities');
                $module_slug = strtolower($module->getName());
                $item = array(
                    'name' => $module->get('title'),
                    'logo' => $logo_path,
                    'description' => $module->getDescription(),
                    'enabled' => $module->isEnabled() ? 1 : 0,
                    'slug' => $module_slug,
                    'category' => $module->get('category'),
                    'info' => array(
                        'full_description' => $module->get('full_description'),
                        'version' => $module->get('version'),
                        'support' => $module->get('support'),
                        'install' => $module->get('install')
                    ),
                    'images' => $images,
                    'entities' => $entities
                );
                

                if(isset($local_modules[$module_slug]) && $local_modules[$module_slug]->config) {
                    $item['fields'] = json_decode($local_modules[$module_slug]->config, true);
                }
                $data[] = $item;
            }
        } else {
            $category = ModuleCategory::where('slug', $slug)->first();
            if(!$category)
                return response()->json(
                    array(
                        'code' => 404,
                        'error' => 'Категория не найдена',
                    )
                );
            $data = $category->modules();
        }
        usort($data, function($a, $b){
            if ($a['name'] == $b['name']) {
                return 0;
            }
            return ($a['name'] < $b['name']) ? -1 : 1;
        });

        $data = array(
            'categories' => ModuleCategory::all(),
            'modules' => $data
        );

        return response()->json($data);
    }

    public function show($slug)
    {
        $module = Module::findOrFail($slug);
        $data = array();
        $images = array();
        $logo_path = '';
        if($images_res = $module->get('images')) {
            foreach($images_res as $image) {
                $path = str_replace('storage/', '', \Storage::disk('public')->url('assets/modules/'.$module->getName().'/'.$image));
                $images[] = $path;
            }
        }
        $logo_res = $module->get('logo');
        if($logo_res)
            $logo_path = str_replace('storage/', '', \Storage::disk('public')->url('assets/modules/'.$module->getName().'/logo/'.$logo_res));
        $entities = array();
        if($res = $module->get('entities')) {
            foreach($res as $e) {
                $entity = \DB::table('data_types')->where('model_name', $e['class'])->first();
                if($entity) {
                    $entities[] = array(
                        'name' => $entity->title_plural,
                        'slug' => $entity->name,
                        'enable' => $entity->enable,
                        'description' => $e['description'] ?? ''
                    );
                }
                
            }
        }
        $local_module = \DB::table('modules')->where('slug', $slug)->first();
        $data = array(
            'name' => $module->get('title'),
            'logo' => $logo_path,
            'description' => $module->getDescription(),
            'enabled' => $module->isEnabled() ? 1 : 0,
            'slug' => strtolower($module->getName()),
            'category' => $module->get('category'),
            'info' => array(
                'full_description' => $module->get('full_description'),
                'version' => $module->get('version'),
                'support' => $module->get('support'),
                'install' => $module->get('install')
            ),
            'images' => $images,
            'entities' => $entities
        );
        if($local_module && $local_module->config) {
            $data['fields'] = json_decode($local_module->config, true);
        }

        return response()->json($data);
    }

    public function settings($slug)
    {
        $local_module = \DB::table('modules')->where('slug', $slug)->first();
        if(!$local_module)
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Модуль не найден',
                )
            );
        $data = json_decode($local_module->config, true);
        if(isset($data['fields']))
            $data = $data['fields'];
        foreach($data as $key => $param) {
            if(!is_array($param)) continue;
            if(isset($param['related_table']) && $param['related_table'] == 'users') {
                $data[$key]['value']['localOptions'] = $this->searchService->find(['q' => '', 'field_id' => 458]);
            } elseif(isset($param['related_table']) && $param['related_table'] == 'roles') {
                $data[$key]['value']['localOptions'] = $this->searchService->find(['q' => '', 'field_id' => 2025]);
            }
            // Поля настроек модулей должны быть редактируемыми. У части тенантов в БД
            // лежит старый конфиг с can_edit=0/read_only=1 — нормализуем при чтении.
            $data[$key]['can_edit'] = 1;
            $data[$key]['read_only'] = 0;
        }

        // Нормализация конфига модуля «Логистика» для существующих тенантов:
        // 1) переименовать legacy-ключ «email» → «main_city»
        // 2) добавить отсутствующее поле «map_type»
        if($slug === 'logistic') {
            $hasMainCity = false;
            $hasMapType = false;
            foreach($data as $key => $param) {
                if(!is_array($param)) continue;
                if(($param['key'] ?? null) === 'email' && ($param['title'] ?? null) === 'Главный город') {
                    $data[$key]['key'] = 'main_city';
                    $data[$key]['type'] = 'address';
                    $data[$key]['subtype'] = 'city';
                    $hasMainCity = true;
                }
                if(($param['key'] ?? null) === 'main_city') {
                    $hasMainCity = true;
                    $data[$key]['type'] = 'address';
                    if(!isset($data[$key]['subtype'])) $data[$key]['subtype'] = 'city';
                }
                if(($param['key'] ?? null) === 'map_type') {
                    $hasMapType = true;
                    $data[$key]['options'] = [
                        ['label' => 'OpenStreetMap', 'value' => 'openstreet'],
                        ['label' => 'Яндекс.Карты', 'value' => 'yandex'],
                    ];
                }
            }
            if(!$hasMapType) {
                $data[] = [
                    'id' => 2,
                    'key' => 'map_type',
                    'title' => 'Тип карты',
                    'type' => 'select_dropdown',
                    'value' => 'openstreet',
                    'visible_always' => 1,
                    'required' => 1,
                    'read_only' => 0,
                    'can_read' => 1,
                    'can_edit' => 1,
                    'options' => [
                        ['label' => 'OpenStreetMap', 'value' => 'openstreet'],
                        ['label' => 'Яндекс.Карты', 'value' => 'yandex'],
                    ],
                ];
            }
            $data = array_values($data);
        }

        return response()->json($data);
    }

    public function install($slug)
    {
        $tenant = tenant('id');
        
        $module = Module::findOrFail($slug);
        if($module->isEnabled())
            return response()->json(
                array(
                    'error' => 'Модуль установлен',
                )
            );

        $config = file_get_contents(getcwd().'/../Modules/'.$module->getName().'/module.json');
        
        $config = json_decode($config, true);

        if(isset($config['images'])) {
            foreach ($config['images'] as $key => $value) {
                $file_path = getcwd().'/../Modules/'.$module->getName().'/'.$value;
                $path = dirname(public_path('assets/modules/'.$module->getName().'/'.$value));
                if(!\File::isDirectory($path))
                    \File::makeDirectory($path, 0777, true, true);
                \File::copy($file_path, public_path('assets/modules/'.$module->getName().'/'.$value));
            }
        }

        if(isset($config['logo'])) {
            $file_path = getcwd().'/../Modules/'.$module->getName().'/'.$config['logo'];
            $path = dirname(public_path('assets/modules/'.$module->getName().'/logo/'.$config['logo']));
            if(!\File::isDirectory($path))
                \File::makeDirectory($path, 0777, true, true);
            \File::copy($file_path, public_path('assets/modules/'.$module->getName().'/logo/'.$config['logo']));
        }
        if(isset($config['menu'])) {

            foreach ($config['menu'] as $key => $item) {
                if(isset($item['entity'])) {
                    \DB::table('data_types')->where('slug', $item['entity'])->update(['enable' => 1]);
                };
                if(isset($item['slug'])) {
                    $sidebar_item = new \App\Models\SidebarItem;
                    $sidebar_item->name = $item['name'];
                    $sidebar_item->slug = $item['slug'];
                    $sidebar_item->link = $item['link'];
                    $sidebar_item->save();
                }
            };
        }
        \App\Models\SidebarItem::fixTree();
        if(isset($config['changes_entities'])) {
            $data = array(
                'title' => 'Модули',
                'tab' => 'modules',
                'sort' => 99,
                'enabled' => 1,
                'id' => 99,
                'childs' => array()
            );
            $menus = \DB::table('settings')->where([
                'type' => 'menu',
                'user_id' => \Auth::user()->id
            ])->get()->keyBy('entity')->toArray();

            foreach ($config['changes_entities'] as $key => $entity) {
                if(!isset($entity['entity']))
                    continue;
                if(!isset($data['childs'][$entity['entity']]))
                    $data['childs'][$entity['entity']] = array(
                        'title' => $config['title'],
                        'sort' => $key,
                        'enabled' => 1,
                        'id' => $key,
                        'slug' => $config['slug']
                    );
                else
                    $data['childs'][$entity['entity']] = array(
                        'title' => $config['title'],
                        'sort' => $key,
                        'enabled' => 1,
                        'id' => $key,
                        'slug' => $config['slug']
                    );
                $entity_class = $entity['class'];
                $data_type = \DB::table('data_types')->where('model_name', $entity_class)->first();
                $slug = $data_type->slug;
                $s = new \App\Models\FieldSection;
                $s->name = $module->get('title');
                $s->module = $module->get('slug');
                $s->page = $entity['entity'];
                $s->column_id = 1;
                $s->save();
                foreach($entity['fields'] as $field) {
                    $details = array();
                    if(isset($field['table'])) {
                        $details = json_encode(array('table' => $field['table']));
                    } elseif(isset($field['options'])) {
                        $details['options'] = array();
                        $k = 0;
                        foreach($field['options'] as $option => $value) {
                            $details['options'][] = array(
                                'value' => $value,
                                'sort' => $k
                            );
                            $k++;
                        }
                        $details = json_encode($details);
                    } else {
                        $details = '';
                    };
                    \DB::table('data_rows')->insert([
                        'data_type_id' => $data_type->id,
                        'field' => $field['name'],
                        'type' => $field['type'],
                        'title' => $field['title'],
                        'module_section_id' => $s->id,
                        'visible_always' => $field['visible_always'],
                        'only_read' => isset($field['read_only']) && $field['read_only'] ? 1 : 0,
                        'is_plural' => isset($field['is_plural']) ? 1 : 0,
                        'details' => $details,
                        'module' => $module->get('slug')
                    ]);
                    $field_name = $field['name'];
                    if($field['type'] == 'text')
                        \Schema::table($data_type->slug, function($table) use ($field_name) {
                            if (!\Schema::hasColumn($table->getTable(), $field_name)) {
                                $table->string($field_name)->nullable();
                            }
                        });
                    else
                        \Schema::table($data_type->slug, function($table) use ($field_name) {
                            if (!\Schema::hasColumn($table->getTable(), $field_name)) {
                                $table->text($field_name)->nullable();
                            }
                        });

                }

            }
            foreach($menus as $entity => $menu) {
                if(isset($data['childs'][$entity])) {
                    $new_menu = json_decode($menu->value, true);
                    $modules_item = null;
                    foreach($new_menu as $k => $m) {
                        if($m['tab'] == 'modules')
                            $modules_item = $k;
                    }
                    if(!$modules_item)
                        $new_menu[] = array(
                            'title' => 'Модули',
                            'tab' => 'modules',
                            'sort' => 99,
                            'enabled' => 1,
                            'id' => 99,
                            'childs' => array($data['childs'][$entity])
                        );
                    else
                        $new_menu[$modules_item]['childs'][] = $data['childs'][$entity];
                    \DB::table('settings')->where([
                        'id' => $menu->id
                    ])->update([
                        'value' => $new_menu
                    ]);
                }
            }
        }
        
        \DB::table('field_sections')->where('module', $module->get('slug'))->update(['hide' => 0]);
        \DB::table('modules')->insert([
            'name' => $config['title'],
            'config' => isset($config['settings']) ? json_encode($config['settings']) : '',
            'entities' => isset($config['entities']) ? json_encode($config['entities']) : '',
            'slug' => $config['slug'],
            'enabled' => 1
        ]);
        $module->enable();

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'sidebar', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        \App\Models\Settings::clear_cache();

        cache()->flush();

        return response()->json(
            array(
                'success' => true,
                'code' => 200
            )
        );
    }
    public function uninstall($slug)
    {
        $module = Module::findOrFail($slug);
        if(!$module->isEnabled())
            return response()->json(
                array(
                    'error' => 'Модуль не установлен',
                )
            );

        $config = file_get_contents(getcwd().'/../Modules/'.$module.'/module.json');
        
        $config = json_decode($config, true);
        $ids_delete = array();
        if(isset($config['menu'])) {
            $link = \App\Models\SidebarItem::where('name', $module->get('title'))->where('slug', $module->get('slug'))->first();
            if($link)
                $link->delete();
            $menus = \DB::table('settings')->where([
                'type' => 'sidebar'
            ])->get();

            foreach ($config['menu'] as $key => $item) {
                $link = \App\Models\SidebarItem::where('link', $item['link'])->first();
                if($link)
                    $ids_delete[] = $link->id;
                foreach($menus as $menu) {
                    $new_menu = json_decode($menu->value, true);
                    foreach($new_menu as $k => $menu_item) {
                        if(in_array($menu_item['id'], $ids_delete)) {
                            unset($new_menu[$k]);
                        }
                    }
                    $new_menu = array_values($new_menu);
                    \DB::table('settings')->where(['id' => $menu->id])->update(['value' => json_encode($new_menu, JSON_UNESCAPED_UNICODE)]);
                }

                if($link)
                    $link->delete();
            };
            
            
            \App\Models\SidebarItem::fixTree();
        }
        

        $mod = Module::findOrFail($module);
        \DB::table('modules')->where([
                'slug' => $slug
            ])->delete();
        $mod->disable();

        $config = file_get_contents(getcwd().'/../Modules/'.$module->getName().'/module.json');
        
        $config = json_decode($config, true);
        \App\Models\SidebarItem::where('slug', $module->get('slug'))->delete();
        \App\Models\SidebarItem::fixTree();
        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'sidebar', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        

        \App\Models\Settings::clear_cache();
        cache()->flush();

        return response()->json(
            array(
                'success' => true,
                'code' => 200
            )
        );
    }

    public function update($slug, Request $request)
    {
        $local_module = \DB::table('modules')->where('slug', $slug)->first();
        if(!$local_module)
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Модуль не найден',
                )
            );
        $config = json_decode($local_module->config, true);

        // Алиасы legacy-ключей. Для модуля Logistic ключ «email» исторически
        // использовался для поля «Главный город» — теперь это «main_city».
        $keyAliases = [];
        if($slug === 'logistic') {
            $keyAliases['email'] = 'main_city';
            $keyAliases['main_city'] = 'email';
        }
        $matchesKey = function($fieldKey, $reqKey) use ($keyAliases) {
            if($fieldKey === $reqKey) return true;
            return isset($keyAliases[$fieldKey]) && $keyAliases[$fieldKey] === $reqKey;
        };

        $applyValue = function(&$config, $path, $new_value) {
            // $path: ['fields', $k] | [$k]
            $ref = &$config;
            foreach($path as $p) $ref = &$ref[$p];
            if(isset($ref['value']) && is_array($ref['value']) && isset($ref['value']['value'])) {
                $ref['value']['value'] = $new_value;
            } else {
                $ref['value'] = $new_value;
            }
        };

        if(isset($config['fields'])) {
            // Гарантируем наличие поля map_type для логистики, если конфиг старый.
            if($slug === 'logistic') {
                $hasMapType = false;
                foreach($config['fields'] as $param) {
                    if(is_array($param) && ($param['key'] ?? null) === 'map_type') $hasMapType = true;
                }
                if(!$hasMapType) {
                    $config['fields'][] = ['key' => 'map_type', 'value' => 'openstreet'];
                }
            }
            foreach($config['fields'] as $k => $param) {
                if(!is_array($param) || !isset($param['key'])) continue;
                foreach($request->all() as $key => $new_value) {
                    if($matchesKey($param['key'], $key)) {
                        $applyValue($config, ['fields', $k], $new_value);
                    }
                }
            }
        } else {
            if($slug === 'logistic') {
                $hasMapType = false;
                foreach($config as $param) {
                    if(is_array($param) && ($param['key'] ?? null) === 'map_type') $hasMapType = true;
                }
                if(!$hasMapType) {
                    $config[] = ['key' => 'map_type', 'value' => 'openstreet'];
                }
            }
            foreach($config as $k => $param) {
                if(!is_array($param) || !isset($param['key'])) continue;
                foreach($request->all() as $key => $new_value) {
                    if($matchesKey($param['key'], $key)) {
                        $applyValue($config, [$k], $new_value);
                    }
                }
            }
        }
        
        $config = json_encode($config);
        \DB::table('modules')->where('slug', $slug)->update(['config' => $config]);
    }

    public function check_entity_statuses($entity, $id, $module)
    {
        $module = \App\Models\Module::where('slug', $module)->firstOrFail();
        $related_entities = $module->statusesEntities($entity, $id);

        return response()->json($related_entities);
    }
}