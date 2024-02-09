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

class ModuleController extends Controller
{
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
                if($module->get('is_hidden'))
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
                    // if(isset($config['images'])) {
                    //     foreach ($config['images'] as $key => $value) {
                    //         $file_path = getcwd().'/../Modules/'.$module->getName().'/'.$value;
                    //         $path = dirname(public_path('assets/modules/'.$module->getName().'/'.$value));
                    //         if(!\File::isDirectory($path))
                    //             \File::makeDirectory($path, 0777, true, true);
                    //         \File::copy($file_path, public_path('assets/modules/'.$module->getName().'/'.$value));
                    //     }
                    // }

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
                    'name' => $module->get('display_name'),
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
                        'name' => $entity->display_name_plural,
                        'slug' => $entity->name,
                        'enable' => $entity->enable,
                        'description' => $e['description'] ?? ''
                    );
                }
                
            }
        }
        $local_module = \DB::table('modules')->where('slug', $slug)->first();
        $data = array(
            'name' => $module->get('display_name'),
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

        // $module_side_item = new \App\Models\SidebarItem;
        // $module_side_item->name = $module->get('display_name');
        // $module_side_item->slug = $module->get('slug');
        // $module_side_item->link = '/'.$module->get('slug');
        // //$module_side_item->url = '';
        // $module_side_item->save();
        // if(isset($config['menu'])) {
        //     $module_side_item = new \App\Models\SidebarItem;
        //     $module_side_item->name = $module->get('display_name');
        //     $module_side_item->code = $module->get('slug');
        //     $module_side_item->link = $config['menu'][0]['link'];
        //     //$module_side_item->url = '';
        //     $module_side_item->save();
        //     foreach ($config['menu'] as $key => $item) {
        //         $sidebar_item = new \App\Models\SidebarItem;
        //         $sidebar_item->name = $item['name'];
        //         $sidebar_item->code = $item['name'];
        //         $sidebar_item->link = $item['link'];
                
        //         //$sidebar_item->parent_id = $module_side_item->id;
        //         $sidebar_item->save();
        //     };
        // }
        \App\Models\SidebarItem::fixTree();
        // if(isset($config['entities'])) {
        //     foreach ($config['entities'] as $key => $entity) {
        //         $e = \DB::table('data_types')->where('slug', $entity['name'])->first();
        //         if(!$e) {
        //             \DB::table('data_types')->insert([
        //                 'name' => $entity['name'],
        //                 'slug' => $entity['name'],
        //                 'display_name_singular' => $entity['display_name_singular'],
        //                 'display_name_plural' => $entity['display_name_plural'],
        //                 'model_name' => $entity['class'],
        //                 'enable' => 0
        //             ]);
        //             $data_type = \DB::table('data_types')->where('model_name', $entity['class'])->first();
        //             foreach($entity['sections'] as $section) {
        //                 \DB::table('field_sections')->insert([
        //                     'name' => $section['name'],
        //                     'page' => $entity['name']
        //                 ]);
        //                 $s = \DB::table('field_sections')->where('page', $entity['name'])->where('name', $section['name'])->first();
        //                 foreach($section['fields'] as $field) {
        //                     $details = '';
        //                     if(isset($field['table'])) {
        //                         $details = json_encode(array('table' => $field['table']));
        //                     } elseif(isset($field['options'])) {
        //                         $details['options'] = array();
        //                         foreach($field['options'] as $option => $value) {
        //                             $details['options'][$option] = $value;
        //                         }
        //                         $details = json_encode($details);
        //                     };
        //                     \DB::table('data_rows')->insert([
        //                         'data_type_id' => $data_type->id,
        //                         'field' => $field['name'],
        //                         'type' => $field['type'],
        //                         'display_name' => $field['display_name'],
        //                         'module_section_id' => $s->id,
        //                         'visible_always' => $field['visible_always'],
        //                         'only_read' => isset($field['read_only']) && $field['read_only'] ? 1 : 0,
        //                         'is_plural' => isset($field['is_plural']) ? 1 : 0,
        //                         'details' => $details
        //                     ]);
        //                 }
                        
        //             }
        //         }
        //     }
        // }
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
                        'title' => $config['display_name'],
                        'sort' => $key,
                        'enabled' => 1,
                        'id' => $key,
                        'slug' => $config['slug']
                    );
                else
                    $data['childs'][$entity['entity']] = array(
                        'title' => $config['display_name'],
                        'sort' => $key,
                        'enabled' => 1,
                        'id' => $key,
                        'slug' => $config['slug']
                    );
                $entity_class = $entity['class'];
                $data_type = \DB::table('data_types')->where('model_name', $entity_class)->first();
                $slug = $data_type->slug;
                $s = new \App\Models\FieldSection;
                $s->name = $module->get('display_name');
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
                        'display_name' => $field['display_name'],
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

                // foreach($entity['sections'] as $section) {
                //     \DB::table('field_sections')->insert([
                //         'name' => $section['name'],
                //         'page' => $slug
                //     ]);
                //     $s = \DB::table('field_sections')->where('page', $slug)->where('name', $section['name'])->first();

                //     foreach($section['fields'] as $field) {
                //         $details = array();
                //         if(isset($field['table'])) {
                //             $details = json_encode(array('table' => $field['table']));
                //         } elseif(isset($field['options'])) {
                //             $details['options'] = array();
                //             foreach($field['options'] as $option => $value) {
                //                 $details['options'][$option] = $value;
                //             }
                //             $details = json_encode($details, true);
                //         } else {
                //             $details = '';
                //         };
                //         \DB::table('data_rows')->insert([
                //             'data_type_id' => $data_type->id,
                //             'field' => $field['name'],
                //             'type' => $field['type'],
                //             'display_name' => $field['display_name'],
                //             'section_id' => $s->id,
                //             'visible_always' => $field['visible_always'],
                //             'only_read' => isset($field['read_only']) && $field['read_only'] ? 1 : 0,
                //             'is_plural' => isset($field['is_plural']) ? 1 : 0,
                //             'details' => $details
                //         ]);
                //     }
                // }
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
        
        // \Artisan::call('tenants:migrate',
        //  array(
        //    '--path' => getcwd().'/../Modules/'.$module->getName().'/Database/Migrations',
        //    '--tenants' => $tenant
        // ));
        //print_r($config);
        \DB::table('field_sections')->where('module', $module->get('slug'))->update(['hide' => 0]);
        \DB::table('modules')->insert([
            'name' => $config['display_name'],
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

        if(isset($config['menu'])) {
            $link = \App\Models\SidebarItem::where('name', $module->get('display_name'))->where('slug', $module->get('slug'))->first();
            if($link)
                $link->delete();
            foreach ($config['menu'] as $key => $item) {
                $link = \App\Models\SidebarItem::where('name', $item['name'])->where('link', $item['link'])->first();
                if($link)
                    $link->delete();
            };
            \App\Models\SidebarItem::fixTree();
        }
        
        // if(isset($config['entities'])) {
        //     foreach ($config['entities'] as $key => $entity) {
        //         \DB::table('data_types')->where([
        //                 'model_name' => $entity['class']
        //             ])->delete();
        //         \DB::table('field_sections')->where([
        //                 'page' => $entity['name']
        //             ])->delete();
                
        //     }
        // }
        // \Artisan::call('tenants:rollback',
        //  array(
        //    '--path' => getcwd().'/../Modules/'.$module.'/Database/Migrations'));

        $mod = Module::findOrFail($module);
        \DB::table('modules')->where([
                'slug' => $slug
            ])->delete();
        $mod->disable();

        $config = file_get_contents(getcwd().'/../Modules/'.$module->getName().'/module.json');
        
        $config = json_decode($config, true);
        if(isset($config['changes_entities'])) {
            $menus = \DB::table('settings')->where([
                'type' => 'menu',
                'user_id' => \Auth::user()->id
            ])->get()->keyBy('entity')->toArray();
            foreach ($config['changes_entities'] as $key => $entity) {
                
                if(!isset($data['childs'][$entity['entity']]))
                    $data['childs'][$entity['entity']] = 1;
                else
                    $data['childs'][$entity['entity']] = array(1);
                $entity_class = $entity['class'];
                $data_type = \DB::table('data_types')->where('model_name', $entity_class)->first();
                $slug = $data_type->slug;
                \DB::table('field_sections')->where('module', $module->get('slug'))->update(['hide' => 1]);
                foreach($entity['fields'] as $field) {
                    $details = array();
                    if(isset($field['table'])) {
                        $details = json_encode(array('table' => $field['table']));
                    } elseif(isset($field['options'])) {
                        $details['options'] = array();
                        foreach($field['options'] as $option => $value) {
                            $details['options'][$option] = $value;
                        }
                        $details = json_encode($details);
                    } else {
                        $details = '';
                    };
                    
                    $field_name = $field['name'];
                    \Schema::table($data_type->slug, function($table) use($field_name) {
                        $table->dropColumn($field_name);
                    });
                    
                }
            }
            \DB::table('data_rows')->where([
                'module' => $module->get('slug')
            ])->update(['is_inactive' => 1]);
            foreach($menus as $entity => $menu) {
                if(isset($data['childs'][$entity])) {
                    $new_menu = json_decode($menu->value, true);
                    $modules_item = null;
                    foreach($new_menu as $k => $m) {
                        if($m['tab'] == 'modules')
                            $modules_item = $k;
                    }

                    if($modules_item) {
                        foreach($new_menu[$modules_item]['childs'] as $k => $m) {
                            if(isset($m['slug']) && $m['slug'] == $module->get('slug'))
                                unset($new_menu[$modules_item]['childs'][$k]);
                        }
                        \DB::table('settings')->where([
                            'id' => $menu->id
                        ])->update([
                            'value' => $new_menu
                        ]);
                    }
                    
                }
            }
        }
        \App\Models\SidebarItem::where('slug', $module->get('slug'))->delete();
        \App\Models\SidebarItem::fixTree();
        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => 'sidebar', 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => 'sidebar', 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        

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

        foreach($config as $k => $param) {
            foreach($request->fields as $i => $new_param) {
                if($param['id'] == $new_param['id']) {
                    $config[$k] = $new_param;
                }
            }
        }
        $config = json_encode($config);
        \DB::table('modules')->where('slug', $slug)->update(['config' => $config]);
    }

    public function checkWork($entity, $id, $module, Request $request)
    {
        $comparisons = \DB::table('comparison_fields')->where([
            'module' => $module,
        ])->pluck('entity_field', 'module_field')->toArray();
        $relations = array();
        $relations[$entity] = \DB::table($entity)->where('id', $id)->get()->toArray();
        $entity = \DB::table('data_types')->where('slug', $entity)->first();
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();
        $fields_data = array();
        $current = $entity_class::findOrFail($id);

        $field_ids = array();
        foreach($model_fields as $field) {
            $field_ids[$field->field] = $field->id;
            if($field->type == 'relation') {
                $details = json_decode($field->details, true);
                if(isset($details['table'])) {
                    $relation_entity = \DB::table('data_types')->where('slug', $details['table'])->first();
                    $relation_entity_class = $relation_entity->model_name;
                    $relation_fields = $relation_entity_class::getFields();
                    foreach($relation_fields as $f) {
                        $field_ids[$f->field] = $f->id;
                    }
                    if($field->is_plural && $current->{$field->field}) {
                        $val = json_decode($current->{$field->field}, true);
                    } elseif($current->{$field->field}) {
                        $val = array($current->{$field->field});
                    }
                    if(isset($val))
                        $relations[$details['table']] = \DB::table($details['table'])->whereIntegerInRaw('id', $val)->get()->toArray();
                }
            }
        }
        $module = Module::findOrFail($module);
        $config = file_get_contents(getcwd().'/../Modules/'.$module->getName().'/module.json');
        
        $config = json_decode($config, true);

        $entities_fields = array();
        $checks = array();
        if(isset($config['changes_entities'])) {
            foreach($config['changes_entities'] as $module_entity) {
                if(isset($module_entity['need_check']) && $module_entity['need_check']) {
                    $entities_fields[$module_entity['entity']] = array();
                    $checks[$module_entity['entity']] = 1;
                    foreach($module_entity['fields'] as $field) {
                        $entities_fields[$module_entity['entity']][] = $field['name'];

                    }
                }
            }
        }

        foreach($entities_fields as $entity => $fields) {
            if(isset($relations[$entity])) {
                foreach($relations[$entity] as $relation) {

                    foreach($fields as $field) {
                        if(isset($comparisons[$field_ids[$field]])) {
                            $field_name = array_search($comparisons[$field_ids[$field]], $field_ids);
                            if($field_name && !$relation->{$field_name}) {
                                $checks[$entity] = 0;
                            }
                        } elseif(!$relation->{$field})
                            $checks[$entity] = 0;
                    }
                }
            }
        }

        $entities = array();
        if($res = $module->get('entities')) {
            foreach($res as $e) {
                $entity = \DB::table('data_types')->where('model_name', $e['class'])->first();
                if($entity && isset($checks[$entity->name])) {
                    $entities[] = array(
                        'id' => $entity->id,
                        'name' => $entity->display_name_plural,
                        'slug' => $entity->name,
                        'enable' => $entity->enable,
                        'description' => $e['description'] ?? '',
                        'status' => $checks[$entity->name]
                    );
                }
                
            }
        }

        return response()->json($entities);
    }
}