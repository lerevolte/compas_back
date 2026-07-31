<?php

namespace App\Models;
use Auth;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ValueHelper;


class Settings extends Model
{
	public $timestamps = false;

	const LAZY_LIST_THRESHOLD = 500;
	const LAZY_LIST_PRELOAD = 30;

	protected static $lazy_resolved = array();
	protected static $palette_colors = null;

	private static function getUser() {
	    $user = \Auth::user();

	    if (!$user) {
	        $user = User::find(1);
	    }

	    return $user;
	}

	private static function getGuidesFromDatabase()
	{
		$guides = \DB::table('guides')->select('id', 'fields', 'entities')->get();
        $res = ['fields' => [], 'entities' => []];
        foreach($guides as $guide) {
            echo $guide->id;
            if($guide->fields) {
                $fields = json_decode($guide->fields, true);
                foreach($fields as $field) {
                    $arr = explode('.', $field);
                    if(!isset($res['fields'][$arr[0]]))
                        $res['fields'][$arr[0]] = [];
                    $res['fields'][$arr[0]][$arr[1]] = $guide->id;
                }
            }
            if($guide->entities) {
                $entities = json_decode($guide->entities, true);
                foreach($entities as $entity) {
                    $res['entities'][$entity] = $guide->id;
                }
            }
        }

        return $res;
	}

	public static function getGuidesWithCache()
	{
		$res = tenancy()->central(function (){
			$cacheName = 'guide-fields';
            $res = cache()->getMemcached()->get($cacheName);
            if(!$res) {
            	$res = self::getGuidesFromDatabase();
            	cache()->getMemcached()->add($cacheName, $res);
            }

            return $res;
        });

        return $res;
		
	}

	//private static function getSettingsWithCache($user, $cacheName) {
	//     $settings = cache()->getMemcached()->get($cacheName);

	//     if (!$settings) {
	//         $settings = self::getSettingsFromDatabase($user);
	//         cache()->getMemcached()->add($cacheName, $settings);
	//     }

	//     return $settings;
	// }

	// private static function getCacheName($userId) {
	//     return tenant('id') . ':settings-' . $userId;
	// }

	// private static function getSettingsFromDatabase($user) {
	// 	$settings = [
    //         'is_admin' => $user->isAdmin(),
    //         'field_values' => [],
    //         'models' => [],
    //         'list_values' => [],
    //         'modules' => [],
    //         'entities' => ['perms' => []],
    //         'settings' => ['sidebar_items' => []],
    //     ];

    //     $modules = \DB::table('modules')->select('id', 'name', 'slug', 'enabled')->get()->keyBy('slug')->toArray();
	// 	$modules_config = \Nwidart\Modules\Facades\Module::all();
	// 	$data_types = \DB::table('data_types')->get();
    //     $models_by_name = $data_types->keyBy('name')->toArray();
    //     $settings['models'] = $models_by_name;
    //     $models = $data_types->pluck('name', 'id')->toArray();
	// 	//$data_types = \DB::table('data_types')->get()->keyBy('name')->toArray();

	// 	$all_fields = \DB::table('data_rows')->where('is_remove', 0)->orderBy('id')->get();
	// 	$all_fields_models = Field::get()->keyBy('id');

	// 	// Группировка полей по типу данных
	// 	$groups = $all_fields->groupBy('data_type_id');

	// 	// Загрузка статусов и полей
	// 	$statuses = \DB::table('field_values')->orderBy('is_hidden')->orderBy('sort')->get()->groupBy('field_id');

	// 	$fields = \DB::table('data_rows')->whereIn('type', ['relation', 'select_dropdown'])->get();

	// 	// Дополнительная обработка полей и таблиц
	// 	$table_objects = [];
	// 	$user_roles = $user ? $user->roles_all()->pluck('id')->toArray() : [];
	// 	$field_values = $permissions = [];

	// 	// Обработка полей и прав доступа
	// 	foreach ($all_fields as $field) {
    //         $perm = $permissions[$field->data_type_id]['read'][$field->field] = $permissions[$field->data_type_id]['write'][$field->field] = 1;
    //         if($field->roles_read) {
    //             $read_roles = json_decode($field->roles_read);
    //             if($read_roles) {
    //                 $intersect = array_intersect($read_roles, $user_roles);
    //                 if(!count($intersect))
    //                     $perm = 0;
    //             }
    //         }
    //         $permissions[$field->data_type_id]['read'][$field->field] = $perm;

    //         $perm = 1;
    //         if($field->roles_write) {
    //             $write_roles = json_decode($field->roles_write);
    //             if($write_roles) {
    //                 $intersect = array_intersect($write_roles, $user_roles);
    //                 if(!count($intersect))
    //                     $perm = 0;
    //             }
    //         }
    //         $permissions[$field->data_type_id]['write'][$field->field] = $perm;
	// 	}

	// 	// Обработка деталей полей и значений
	// 	foreach ($fields as $field) {
	// 	    // Обработка деталей полей
	// 	    if ($field->details) {
	// 	        $details = json_decode($field->details, true);

	// 	        // Обработка связанных объектов
	// 	        if (isset($details['table'])) {
	// 	            $type = isset($models_by_name[$details['table']]) ? $models_by_name[$details['table']] : null;
    //                 if($type) {
    //                 	if($details['table'] == 'cars' || $details['table'] == 'employees' || $details['table'] == 'clients')
    //                 		$table_objects = isset($table_objects[$details['table']]) ? $table_objects[$details['table']] :$type->model_name::orderBy('choosed_at', 'DESC')->orderBy('name', 'ASC')->whereNull('deleted_at')->get();
    //                 	else
    //                     	$table_objects = isset($table_objects[$details['table']]) ? $table_objects[$details['table']] :$type->model_name::orderBy('choosed_at', 'DESC')->orderBy('name', 'ASC')->whereNull('deleted_at')->get();
    //                 } else {
    //                     $table_objects = isset($table_objects[$details['table']]) ? $table_objects[$details['table']] :\DB::table($details['table'])->orderBy('choosed_at', 'DESC')->orderBy('name', 'ASC')->whereNull('deleted_at')->get();
    //                 }
    //                 $table_objects[$details['table']] = $table_objects;
    //                 $i = 0;
    //                 foreach ($table_objects[$details['table']] as $i => $object) {
    //                 	$avatar = isset($object->avatar) ? $object->avatar : (isset($object->photo) ? $object->photo : '');
    //                 	$avatar = isset($object->icon) ? $object->icon : $avatar;
    //                 	if($avatar) {

    //                 		$avatar = json_decode($avatar, true);
    //                 		if(isset($avatar[0]['url']))
    //                 			$avatar = $avatar[0]['url'];
    //                 	}
    //                     if(isset($object->title)) {
    //                         $field_values[$field->id][$object->id] = array(
    //                 				'label' => [	
	// 									'id' =>	$object->id,
	// 									'sort' => $i,
	// 									'file' => $avatar,
	// 									'is_hidden' => 0,
	// 									'field_id' => $field->id,
	// 									'color' => isset($object->color) ? $object->color : '',
	// 									'text' => $object->title
	// 								],
	// 								'value' => $object->id
    //                 			);
    //                     } elseif(isset($object->first_name)) {
    //                     	$field_values[$field->id][$object->id] = array(
    //                 				'label' => [	
	// 									'id' =>	$object->id,
	// 									'sort' => $i,
	// 									'file' => $avatar,
	// 									'is_hidden' => 0,
	// 									'field_id' => $field->id,
	// 									'color' => isset($object->color) ? $object->color : '',
	// 									'text' => $object->first_name.' '.$object->last_name,
	// 								],
	// 								'value' => $object->id
    //                 			);
    //                     } elseif(isset($object->display_name)) {
    //                     	$name = $object->display_name;
    //                     	if(ValueHelper::isJson($name)) {
    //                     		$name = json_decode($object->display_name, true)['value'];
    //                     	}
    //                     	$obj_arr = $object->toArray();

    //                     	$field_values[$field->id][$object->id] = array(
    //                 				'label' => [	
	// 									'id' =>	$object->id,
	// 									'sort' => $i,
	// 									'file' => $avatar,
	// 									'is_hidden' => 0,
	// 									'field_id' => $field->id,
	// 									'color' => array_key_exists('color', $obj_arr) && !$object->color ? $object->getColor() : ($object->color ?? ''),
	// 									'text' => $name,
	// 								],
	// 								'value' => $object->id
    //                 			);
    //                     } elseif(isset($object->name)) {
    //                     	$name = $object->name;
    //                     	if(ValueHelper::isJson($name)) {
    //                     		$name = json_decode($object->name, true)['value'];
    //                     	}


    //                     	$last_name = '';
    //                     	if(isset($object->last_name))
    //                     		$last_name = $object->last_name;
    //                     	if(ValueHelper::isJson($last_name)) {
    //                     		$last_name = json_decode($object->last_name, true)['value'];
    //                     	}
    //                     	$obj_arr = $object->toArray();

    //                     	$field_values[$field->id][$object->id] = array(
    //                 				'label' => [	
	// 									'id' =>	$object->id,
	// 									'sort' => $i,
	// 									'file' => $avatar,
	// 									'is_hidden' => 0,
	// 									'field_id' => $field->id,
	// 									'color' => array_key_exists('color', $obj_arr) && !$object->color ? $object->getColor() : ($object->color ?? ''),
	// 									'text' => $name.($last_name ? ' '.$last_name:''),
	// 								],
	// 								'value' => $object->id
    //                 			);
    //                     } elseif(isset($object->id)) {
    //                     	$field_values[$field->id][$object->id] = array(
    //                 				'label' => [	
	// 									'id' =>	$object->id,
	// 									'sort' => $i,
	// 									'file' => $avatar,
	// 									'is_hidden' => 0,
	// 									'field_id' => $field->id,
	// 									'color' => isset($object->color) && !$object->color ? $object->getColor() : ($object->color ?? ''),
	// 									'text' => $object->name,
	// 								],
	// 								'value' => $object->id
    //                 			);
    //                     }
    //                     $i++;
    //                 }
	// 	        }

	// 	        // Обработка опций для селектов и связанных полей
	// 	        if (isset($details['options'])) {
	// 	            foreach($details['options'] as $k => $option) {
    //                     if(is_array($option) && isset($option['value']))
    //                         $field_values[$field->id][$option['value']] = $option;
    //                     else
    //                         $field_values[$field->id][$k] = $option;
    //                 }
	// 	        }
	// 	    }
	// 	}

	// 	foreach($groups as $model_id => $group) {
    //         if(isset($models[$model_id])) {
    //             $settings[$models[$model_id]] = array(
    //                 'perms' => array(),
    //                 'colors' => array(),
    //                 //'list_values' => array(),
    //                 'options' => array(),
    //                 'fields' => array(),
    //                 'field_data' => array(),
    //                 'buttons' => array(),
    //                 'used_in_modules' => array()
    //             );
    //             foreach ($modules_config as $module) {
	// 	            $changes_entity = $module->get('changes_entities');
	// 	            if($changes_entity) {
	// 	                foreach($changes_entity as $change) {
	// 	                    if(isset($change['buttons']) && $change['entity'] == $models[$model_id]) {
	// 	                        $settings[$models[$model_id]]['buttons'] = array_merge($settings[$models[$model_id]]['buttons'], $change['buttons']);
	// 	                    }
	// 	                }
		                
	// 	            }
	// 	        }
    //             foreach($group as $field) {

    //                 if($field->type == 'status') {
    //                 	$settings[$models[$model_id]]['options'][$field->field] = array();
    //                 	if(isset($statuses[$field->id])) {
    //                 		foreach($statuses[$field->id] as $status) {
    //                 			$settings[$models[$model_id]]['options'][$field->field][] = array(
    //                 				'label' => [	
	// 									'id' =>	$status->id,
	// 									'sort' => $status->sort,
	// 									'file' => $status->file,
	// 									'is_hidden' => $status->is_hidden,
	// 									'field_id' => $status->field_id,
	// 									'color' => $status->color,
	// 									'text' => $status->value
	// 								],
	// 								'value' => $status->id
    //                 			);
    //                 		}
    //                 	}
    //                     $settings['list_values'][$field->id] = $settings[$models[$model_id]]['options'][$field->field];
    //                 }
    //                 if($field->type == 'select_dropdown' || $field->type == 'relation')
    //                     $settings['list_values'][$field->id] = isset($field_values[$field->id]) ? $field_values[$field->id] : null;
    //                 $settings[$models[$model_id]]['colors'][$field->field] = $field->label_color ?? '';
    //                 $settings[$models[$model_id]]['perms'][$field->field] = array(
    //                     'read' => $permissions[$model_id]['read'][$field->field],
    //                     'write' => $permissions[$model_id]['write'][$field->field],
    //                 );
    //                 $settings[$models[$model_id]]['used_in_modules'][$field->field] = array();
    //                 if($field->module) {
    //                 	$used_in_modules = json_decode($field->module, true);
    //                 	foreach($used_in_modules as $module_slug) {
    //                 		if(isset($modules[$module_slug]))
    //                 			$settings[$models[$model_id]]['used_in_modules'][$field->field][] = $modules[$module_slug];
    //                 	}
    //                 }
    //                 if(isset($settings[$models[$model_id]]['perms'][$field->field]['read']) && $settings[$models[$model_id]]['perms'][$field->field]['read'] != 'disabled') {
    //                 	if (\Schema::hasColumn($models[$model_id], $field->field)) {
    //                     	$settings[$models[$model_id]]['fields'][$field->field] = $field;
    //                     	$settings[$models[$model_id]]['field_data'][$field->field] = $all_fields_models[$field->id]->getData();
    //                 	}

    //                     if($field->type == 'relation' && $field->details && $details = json_decode($field->details, true)) {
    //                     	if(isset($details['unique'])) {
    //                             $choosed = \DB::table($details['table'])->whereNotNull($details['relation_field'])->whereNotNull('choosed_at')->pluck('id')->toArray();
    //                             if(isset($settings[$models[$model_id]]['fields'][$field->field]))
    //                             	$settings[$models[$model_id]]['fields'][$field->field]->choosed = $choosed;
    //                         }
                        	
    //                     }
    //                 }
    //             }
    //         }
    //     }
        
    //     $permissions = $user->getPermissions();
    //     $perms = array();
    //     foreach ($permissions as $perm) {
    //         if(isset($perm['key']) && $perm['area'] && !$perm['is_parent']) {
    //             if(!isset($perms[$perm['key']]) || $perms[$perm['key']] != '') {
    //                 $perms[$perm['entity']][$perm['type']] = $perm['value'];
    //             }
    //         }
    //     }
    //     $settings['entities']['perms'] = $perms;
        
    //     $tree = \App\Models\SidebarItem::defaultOrder()->where('enabled', 1)->get()->toTree()->toArray();
    //     $settings['settings']['sidebar_items'] = $tree;
    //     $settings['modules'] = $modules;
        
    // 	cache()->getMemcached()->add($cache_name, $settings);

	//     return $settings;
	// }

	// private static function getSettingsWithCache($user, $cacheName) {
	//     $settings = cache()->getMemcached()->get($cacheName);

	//     if (!$settings) {
	//         $settings = self::getSettingsFromDatabase($user);
	//         cache()->getMemcached()->add($cacheName, $settings);
	//     }

	//     return $settings;
	// }

	// public static function get($wo_cache = false) {
	//     $user = self::getUser();

	//     if (!$user) {
	//         return null; // или выполните действие по умолчанию, если пользователь не найден
	//     }

	//     $cacheName = self::getCacheName($user->id);

	//     if (!$wo_cache) {
	//         return self::getSettingsWithCache($user, $cacheName);
	//     } else {
	//         return self::getSettingsFromDatabase($user);
	//     }
	// }


	// private static function cacheUserSettings($cacheName, $settings) {
	//     cache()->getMemcached()->add($cacheName, $settings);
	// }

	// private static function cacheModels($cacheName, $models) {
	//     cache()->getMemcached()->add($cacheName, $models);
	// }

	// public static function get($wo_cache = false) {
	//     $user = \Auth::user();

	//     if (!$user) {
	//         $user = User::find(1);
	//     }

	//     $cacheNameUserSettings = tenant('id') . ':settings-' . ($user ? $user->id : 1) . '-user';
	//     $cacheNameModels = tenant('id') . ':settings-' . ($user ? $user->id : 1) . '-models';
	//     $cacheNameOtherData = tenant('id') . ':settings-' . ($user ? $user->id : 1) . '-other';

	//     if ($user) {
	//         $settings = cache()->getMemcached()->get($cacheNameUserSettings);

	//         if (!$settings || $wo_cache) {
	//             $settings = [];

	//             // Получение данных
	//             $models = self::getModels();
	//             $otherData = self::getOtherData(); // Например, статусы и другие данные

	//             // Сохранение в кэш
	//             self::cacheUserSettings($cacheNameUserSettings, $settings);
	//             self::cacheModels($cacheNameModels, $models);
	//             self::cacheOtherData($cacheNameOtherData, $otherData);

	//             return $settings;
	//         } else {
	//             return $settings;
	//         }
	//     }
	// }









    public static function get($wo_cache = false) {
        //return app('user_settings');
	    $user = \Auth::user();

	    if(!$user) {
	    	$user = User::find(1);
	    }
	    $cache_name = tenant('id').':settings-'.($user ? $user->id : 1);
        info('cache_name');
        info($cache_name);
	    if($user) {
	        $settings = cache()->getMemcached()->get($cache_name);
	        if($settings && !is_array($settings)) {
	        	$settings = unserialize(gzuncompress($settings));
	        }


	        if(!$settings || $wo_cache) {
            	$settings = array();
		        $settings['is_admin'] = $user->isAdmin();
		        $settings['field_values'] = array();
		        $settings['lazy_relations'] = array();
		        $modules = \DB::table('modules')->select('id', 'name', 'slug', 'enabled')->get()->keyBy('slug')->toArray();
		        $modules_config = \Nwidart\Modules\Facades\Module::all();
		        $data_types = \DB::table('data_types')->get();
		        $models_by_name = $data_types->keyBy('name')->toArray();
		        $settings['models'] = $models_by_name;
		        $models = $data_types->pluck('name', 'id')->toArray();
		        
		        $all_fields = \DB::table('data_rows')->where(['is_remove' => 0])/*->where('type', '!=', 'text_group')*/->orderBy('id')->get();
		        $all_fields_models = Field::get()->keyBy('id');
		        $groups = $all_fields->groupBy('data_type_id')->toArray();
		        
		        $status_rows = \DB::table('field_values')->orderBy('is_hidden', 'asc')->orderBy('sort')->get();
		        $statuses = $status_rows->groupBy('field_id')->toArray();

		        // У маршрутов (routes.color) в колонке color хранится ID записи
		        // field_values (палитра цвета линии на карте), а не hex/градиент.
		        // Для аватарок/чипов на фронте резолвим числовой color в hex.
		        $fv_color_by_id = $status_rows->pluck('color', 'id');
		        $resolveDisplayColor = function ($color) use ($fv_color_by_id) {
		            if ($color !== null && $color !== '' && is_numeric($color)) {
		                return $fv_color_by_id[(int) $color] ?? '';
		            }
		            return $color ?? '';
		        };

		        $fields = \DB::table('data_rows')->where('type', 'relation')->orWhere('type', 'select_dropdown')->get();
		        $field_values = array();

		        $table_objects = array();
		        $relation_counts = array();
		        $user_roles = [$user->role_id];//$user->roles_all()->pluck('id')->toArray();
		        $permissions = array();

		        foreach($all_fields as $field) {
		            if(!isset($permissions[$field->data_type_id]))
		                $permissions[$field->data_type_id] = array('read' => array(), 'write' => array());
		            $perm = 1;
		            if($field->roles_read) {
		                $read_roles = json_decode($field->roles_read);
		                if($read_roles) {
		                    $intersect = array_intersect($read_roles, $user_roles);
		                    if(!count($intersect))
		                        $perm = 0;
		                }
		            }
		            $permissions[$field->data_type_id]['read'][$field->field] = $perm;

		            $perm = 1;
		            if($field->roles_write) {
		                $write_roles = json_decode($field->roles_write);
		                if($write_roles) {
		                    $intersect = array_intersect($write_roles, $user_roles);
		                    if(!count($intersect))
		                        $perm = 0;
		                }
		            }
		            // if($field->data_type_id == 1 && ($field->field == 'role_id' || $field->field == 'user_id' || $field->field == 'employee_id') && !$settings['is_admin'])
		            // 	$perm = 0;
		            $permissions[$field->data_type_id]['write'][$field->field] = $perm;
		        }
		        foreach($fields as $field) {
		            if($field->details && isset($models[$field->data_type_id]) && $details = json_decode($field->details, true)) {
		                $field_values[$field->id] = array();
		                if(isset($details['table'])) {
		                    $type = isset($models_by_name[$details['table']]) ? $models_by_name[$details['table']] : null;
	                        if(!\Schema::hasTable($details['table'])) {
	                            // Центральный домен (admin_compas_main) не содержит
	                            // тенантских таблиц вроде routes — пустой набор вместо
	                            // ошибки "Base table or view not found".
	                            $table_objects = collect();
	                        } else {
	                            if(!array_key_exists($details['table'], $relation_counts)) {
	                                $count_query = \DB::table($details['table']);
	                                if(\Schema::hasColumn($details['table'], 'deleted_at'))
	                                    $count_query->whereNull('deleted_at');
	                                $relation_counts[$details['table']] = $count_query->count();
	                            }
	                            if($relation_counts[$details['table']] > self::LAZY_LIST_THRESHOLD)
	                                $settings['lazy_relations'][$field->id] = $details['table'];
	                            if(isset($table_objects[$details['table']])) {
	                                $table_objects = $table_objects[$details['table']];
	                            } else {
	                                $list_query = $type
	                                    ? $type->model_name::orderBy('choosed_at', 'DESC')->orderBy('name', 'ASC')->whereNull('deleted_at')
	                                    : \DB::table($details['table'])->orderBy('choosed_at', 'DESC')->orderBy('name', 'ASC')->whereNull('deleted_at');
	                                if(isset($settings['lazy_relations'][$field->id]))
	                                    $list_query->limit(self::LAZY_LIST_PRELOAD);
	                                $table_objects = $list_query->get();
	                            }
	                        }
		                    $table_objects[$details['table']] = $table_objects;
		                    $i = 0;
		                    foreach ($table_objects[$details['table']] as $i => $object) {
		                    	$avatar = isset($object->avatar) ? $object->avatar : (isset($object->photo) ? $object->photo : '');
		                    	$avatar = isset($object->icon) ? $object->icon : $avatar;
		                    	if($avatar) {

		                    		$avatar = json_decode($avatar, true);
		                    		if(isset($avatar[0]['url']))
		                    			$avatar = $avatar[0]['url'];
		                    	}
		                        if(isset($object->title)) {
		                            $field_values[$field->id][$object->id] = array(
		                    				'label' => [	
												'id' =>	$object->id,
												'sort' => $i,
												'file' => $avatar,
												'is_hidden' => 0,
												'field_id' => $field->id,
												'color' => $resolveDisplayColor(isset($object->color) ? $object->color : ''),
												'text' => $object->title
											],
											'value' => $object->id
		                    			);
		                        }/* elseif(isset($object->first_name)) {
		                        	$field_values[$field->id][$object->id] = array(
		                    				'label' => [	
												'id' =>	$object->id,
												'sort' => $i,
												'file' => $avatar,
												'is_hidden' => 0,
												'field_id' => $field->id,
												'color' => isset($object->color) ? $object->color : '',
												'text' => $object->first_name.' '.$object->last_name,
											],
											'value' => $object->id
		                    			);
		                        } */elseif(isset($object->display_name)) {
		                        	$name = $object->display_name;
		                        	if(ValueHelper::isJson($name)) {
		                        		$name = json_decode($object->display_name, true)['value'];
		                        	}
		                        	$obj_arr = $object->toArray();

		                        	$field_values[$field->id][$object->id] = array(
		                    				'label' => [	
												'id' =>	$object->id,
												'sort' => $i,
												'file' => $avatar,
												'is_hidden' => 0,
												'field_id' => $field->id,
												'color' => $resolveDisplayColor(array_key_exists('color', $obj_arr) && !$object->color ? $object->getColor() : ($object->color ?? '')),
												'text' => $name,
											],
											'value' => $object->id
		                    			);
		                        } elseif(isset($object->name)) {
		                        	$name = $object->name;
		                        	if(ValueHelper::isJson($name)) {
		                        		$name = json_decode($object->name, true)['value'];
		                        	}

		                        	$last_name = '';
		                        	if($details['table'] == 'users' && isset($object->last_name)) {
		                        		$last_name = $object->last_name;
		                        		if(ValueHelper::isJson($last_name)) {
		                        			$last_name = json_decode($object->last_name, true)['value'] ?? '';
		                        		}
		                        	}
		                        	$obj_arr = $object->toArray();

		                        	$label = [
												'id' =>	$object->id,
												'sort' => $i,
												'file' => $avatar,
												'is_hidden' => 0,
												'field_id' => $field->id,
												'color' => $resolveDisplayColor(array_key_exists('color', $obj_arr) && !$object->color ? $object->getColor() : ($object->color ?? '')),
												'text' => trim($name.($last_name ? ' '.$last_name : '')),
											];
											// Для товаров подкидываем цену/вес/количество — фронт
											// (getRow в Body.vue) подхватывает их при выборе товара
											// в таблице задачи логистики.
											if(isset($details['table']) && $details['table'] === 'products') {
												if(array_key_exists('price', $obj_arr)) $label['price'] = $object->price;
												if(array_key_exists('weight', $obj_arr)) $label['weight'] = $object->weight;
												if(array_key_exists('quantity', $obj_arr)) $label['count'] = $object->quantity;
											}
		                        	$field_values[$field->id][$object->id] = array(
		                    				'label' => $label,
											'value' => $object->id
		                    			);
		                        } elseif(isset($object->id)) {
		                        	$field_values[$field->id][$object->id] = array(
		                    				'label' => [	
												'id' =>	$object->id,
												'sort' => $i,
												'file' => $avatar,
												'is_hidden' => 0,
												'field_id' => $field->id,
												'color' => $resolveDisplayColor(isset($object->color) && !$object->color ? $object->getColor() : ($object->color ?? '')),
												'text' => $object->name,
											],
											'value' => $object->id
		                    			);
		                        }
		                        $i++;
		                    }

		                } elseif(isset($details['options'])) {
		                    foreach($details['options'] as $k => $option) {
		                        if(is_array($option) && isset($option['value']))
		                            $field_values[$field->id][$option['value']] = $option;
		                        else
		                            $field_values[$field->id][$k] = $option;
		                    }
		                }
		            }
		        }
		        foreach($groups as $model_id => $group) {
		            if(isset($models[$model_id])) {
		                $settings[$models[$model_id]] = array(
		                    'perms' => array(),
		                    'colors' => array(),
		                    //'list_values' => array(),
		                    'options' => array(),
		                    'fields' => array(),
		                    'field_data' => array(),
		                    'buttons' => array(),
		                    'used_in_modules' => array()
		                );
		                foreach ($modules_config as $module) {
				            $changes_entity = $module->get('changes_entities');
				            if($changes_entity) {
				                foreach($changes_entity as $change) {
				                    if(isset($change['buttons']) && $change['entity'] == $models[$model_id]) {
				                        $settings[$models[$model_id]]['buttons'] = array_merge($settings[$models[$model_id]]['buttons'], $change['buttons']);
				                    }
				                }
				                
				            }
				        }
		                foreach($group as $field) {

		                    if($field->type == 'status') {
		                    	$settings[$models[$model_id]]['options'][$field->field] = array();
		                    	if(isset($statuses[$field->id])) {
		                    		foreach($statuses[$field->id] as $status) {
		                    			$settings[$models[$model_id]]['options'][$field->field][] = array(
		                    				'label' => [	
												'id' =>	$status->id,
												'sort' => $status->sort,
												'file' => $status->file,
												'is_hidden' => $status->is_hidden,
												'field_id' => $status->field_id,
												'color' => $status->color,
												'text' => $status->value
											],
											'value' => $status->id
		                    			);
		                    		}
		                    	}
		                        $settings['list_values'][$field->id] = $settings[$models[$model_id]]['options'][$field->field];
		                    }
		                    if($field->type == 'select_dropdown' || $field->type == 'relation')
		                        $settings['list_values'][$field->id] = isset($field_values[$field->id]) ? $field_values[$field->id] : null;
		                    $settings[$models[$model_id]]['colors'][$field->field] = $field->label_color ?? '';
		                    $settings[$models[$model_id]]['perms'][$field->field] = array(
		                        'read' => $permissions[$model_id]['read'][$field->field],
		                        'write' => $permissions[$model_id]['write'][$field->field],
		                    );
		                    $settings[$models[$model_id]]['used_in_modules'][$field->field] = array();
		                    if($field->module) {
		                    	$used_in_modules = json_decode($field->module, true);
		                    	foreach($used_in_modules as $module_slug) {
		                    		if(isset($modules[$module_slug]))
		                    			$settings[$models[$model_id]]['used_in_modules'][$field->field][] = $modules[$module_slug];
		                    	}
		                    }
		                    if(isset($settings[$models[$model_id]]['perms'][$field->field]['read']) && $settings[$models[$model_id]]['perms'][$field->field]['read'] != 'disabled') {
		                    	if (\Schema::hasColumn($models[$model_id], $field->field)) {
		                        	$settings[$models[$model_id]]['fields'][$field->field] = $field;
		                        	$settings[$models[$model_id]]['field_data'][$field->field] = $all_fields_models[$field->id]->getData();
		                    	}

		                        if($field->type == 'relation' && $field->details && $details = json_decode($field->details, true)) {
		                        	if(isset($details['unique'])) {
		                                $choosed = \DB::table($details['table'])->whereNotNull($details['relation_field'])->whereNotNull('choosed_at')->pluck('id')->toArray();
		                                if(isset($settings[$models[$model_id]]['fields'][$field->field]))
		                                	$settings[$models[$model_id]]['fields'][$field->field]->choosed = $choosed;
		                            }
		                        	
		                        }
		                    }
		                }
		            }
		        }
		        
		        //$permissions = $user->getPermissions();
		        $perms = array();
		        // foreach ($permissions as $perm) {
		        //     if(isset($perm['key']) && $perm['area'] && !$perm['is_parent']) {
		        //         if(!isset($perms[$perm['key']]) || $perms[$perm['key']] != '') {
		        //             $perms[$perm['entity']][$perm['type']] = $perm['value'];
		        //         }
		        //     }
		        // }
		        $settings['entities']['perms'] = $perms;
		        $tree = \App\Models\SidebarItem::defaultOrder()->where('enabled', 1)->get()->toTree()->toArray();
		        $settings['settings']['sidebar_items'] = $tree;
		        $settings['modules'] = $modules;
            	cache()->getMemcached()->set($cache_name, gzcompress(serialize($settings)));

	            return $settings;
	        } else {
	            return $settings;
	        }
	    }
	}

    public static function lazy_table($settings, $field_id)
    {
        return isset($settings['lazy_relations'][$field_id]) ? $settings['lazy_relations'][$field_id] : null;
    }

    public static function resolve_list_values($settings, $field_id, $values)
    {
        $result = array();
        if(!is_array($values))
            $values = array($values);
        $ids = array();
        foreach($values as $v) {
            if($v === null || $v === '' || is_array($v) || !is_numeric($v))
                continue;
            $ids[] = (int) $v;
        }
        $ids = array_unique($ids);
        if(!count($ids))
            return $result;
        foreach($ids as $id) {
            if(isset($settings['list_values'][$field_id][$id]))
                $result[$id] = $settings['list_values'][$field_id][$id];
        }
        $table = self::lazy_table($settings, $field_id);
        if(!$table)
            return $result;
        $missing = array();
        foreach($ids as $id) {
            if(isset($result[$id]))
                continue;
            $memo_key = $field_id.':'.$id;
            if(array_key_exists($memo_key, self::$lazy_resolved)) {
                if(self::$lazy_resolved[$memo_key])
                    $result[$id] = self::$lazy_resolved[$memo_key];
            } else {
                $missing[] = $id;
            }
        }
        if(count($missing)) {
            $objects = \DB::table($table)->whereIn('id', $missing)->get()->keyBy('id');
            foreach($missing as $id) {
                $option = isset($objects[$id]) ? self::option_from_object($objects[$id], $field_id, $table) : null;
                self::$lazy_resolved[$field_id.':'.$id] = $option;
                if($option)
                    $result[$id] = $option;
            }
        }
        return $result;
    }

    public static function list_option($settings, $field_id, $value)
    {
        $options = self::resolve_list_values($settings, $field_id, array($value));
        return count($options) ? current($options) : null;
    }

    public static function search_list_values($settings, $field_id, $q = '', $limit = 20)
    {
        $table = self::lazy_table($settings, $field_id);
        if(!$table || !\Schema::hasTable($table))
            return array();
        $name_column = null;
        foreach(array('name', 'display_name', 'title') as $column) {
            if(\Schema::hasColumn($table, $column)) {
                $name_column = $column;
                break;
            }
        }
        $query = \DB::table($table);
        if(\Schema::hasColumn($table, 'deleted_at'))
            $query->whereNull('deleted_at');
        if($q !== null && $q !== '' && $name_column) {
            $like = '%'.str_replace(' ', '%', $q).'%';
            $query->where(function($sub) use ($name_column, $like, $q) {
                $sub->where($name_column, 'LIKE', $like)
                    ->orWhere($name_column.'->value', 'LIKE', $like);
                if(is_numeric($q))
                    $sub->orWhere('id', (int) $q);
            });
        }
        if(\Schema::hasColumn($table, 'choosed_at'))
            $query->orderBy('choosed_at', 'DESC');
        if($name_column)
            $query->orderBy($name_column, 'ASC');
        $result = array();
        $sort = 0;
        foreach($query->limit($limit)->get() as $object) {
            $result[$object->id] = self::option_from_object($object, $field_id, $table, $sort);
            $sort++;
        }
        return $result;
    }

    public static function option_from_object($object, $field_id, $table, $sort = 0)
    {
        if(is_array($object))
            $object = (object) $object;
        $avatar = isset($object->avatar) ? $object->avatar : (isset($object->photo) ? $object->photo : '');
        $avatar = isset($object->icon) ? $object->icon : $avatar;
        if($avatar) {
            $decoded = json_decode($avatar, true);
            if(isset($decoded[0]['url']))
                $avatar = $decoded[0]['url'];
        }
        if(isset($object->title)) {
            $text = $object->title;
        } elseif(isset($object->display_name)) {
            $text = $object->display_name;
        } elseif(isset($object->name)) {
            $text = $object->name;
        } else {
            $text = '#'.$object->id;
        }
        if(ValueHelper::isJson($text)) {
            $decoded = json_decode($text, true);
            $text = isset($decoded['value']) ? $decoded['value'] : $text;
        }
        if($table == 'users' && isset($object->last_name) && $object->last_name) {
            $last_name = $object->last_name;
            if(ValueHelper::isJson($last_name)) {
                $decoded = json_decode($last_name, true);
                $last_name = isset($decoded['value']) ? $decoded['value'] : '';
            }
            $text = trim($text.' '.$last_name);
        }
        $color = isset($object->color) ? $object->color : '';
        if($color !== '' && $color !== null && is_numeric($color)) {
            if(self::$palette_colors === null)
                self::$palette_colors = \DB::table('field_values')->pluck('color', 'id')->toArray();
            $color = isset(self::$palette_colors[(int) $color]) ? self::$palette_colors[(int) $color] : '';
        }
        $label = array(
            'id' => $object->id,
            'sort' => $sort,
            'file' => $avatar,
            'is_hidden' => 0,
            'field_id' => $field_id,
            'color' => $color === null ? '' : $color,
            'text' => $text,
        );
        if(isset($object->deleted_at) && $object->deleted_at)
            $label['deleted'] = 1;
        if($table === 'products') {
            if(property_exists($object, 'price')) $label['price'] = $object->price;
            if(property_exists($object, 'weight')) $label['weight'] = $object->weight;
            if(property_exists($object, 'quantity')) $label['count'] = $object->quantity;
        }
        return array(
            'label' => $label,
            'value' => $object->id,
        );
    }

    public static function hints() {
        $hints = \DB::table('settings')->where([
                'key' => 'hints',
                'type' => 'account'
            ])->first();

        return $hints ? $hints->value : 0;
    }
	public static function clear_cache()
	{
		// $keys = cache()->getMemcached()->getAllKeys();
        // $regex = tenant('id').':settings-*';

        // foreach($keys as $item) {
        //     if(preg_match('/'.$regex.'/', $item)) {
        //         cache()->getMemcached()->delete($item);
        //     }
        // }
        //\App\Jobs\SettingsClearJob::dispatch();
		$users = \App\Models\User::get();
		foreach($users as $user) {
			cache()->getMemcached()->delete(tenant('id').':settings-'.$user->id);
			cache()->getMemcached()->delete(tenant('id').':sidebarmenu-'.$user->id);

		}
		cache()->getMemcached()->delete(tenant('id').':settings-0');

		// Сбрасываем кэш полей моделей (ModelActions::getFields, ключ
		// "{tenant}:{table}-fields"). Без этого после добавления/изменения поля
		// (например «Оплата, руб») getFields отдаёт устаревший список без нового
		// поля, и его значения не попадают в выдачу /routes/{id}/tasks (8579).
		try {
			$types = \DB::table('data_types')->pluck('name');
			foreach ($types as $t) {
				if (!$t) continue;
				cache()->getMemcached()->delete(tenant('id').':'.$t.'-fields');
			}
		} catch (\Throwable $e) {}
		// \App\Jobs\SettingsClearJob::dispatch();


		// $keys = cache()->getMemcached()->getAllKeys();
        // $regex = tenant('id').':settings-*';
        // foreach($keys as $item) {
        //     if(preg_match('/'.$regex.'/', $item)) {
        //         cache()->getMemcached()->delete($item);
        //     }
        // }
	}
}
