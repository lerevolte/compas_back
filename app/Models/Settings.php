<?php

namespace App\Models;
use Auth;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ValueHelper;


class Settings extends Model
{
	public $timestamps = false;
	
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
		        $modules = \DB::table('modules')->select('id', 'name', 'slug', 'enabled')->get()->keyBy('slug')->toArray();
		        $modules_config = \Nwidart\Modules\Facades\Module::all();
		        $data_types = \DB::table('data_types')->get();
		        $models_by_name = $data_types->keyBy('name')->toArray();
		        $settings['models'] = $models_by_name;
		        $models = $data_types->pluck('name', 'id')->toArray();
		        
		        $all_fields = \DB::table('data_rows')->where(['is_remove' => 0])/*->where('type', '!=', 'text_group')*/->orderBy('id')->get();
		        $all_fields_models = Field::get()->keyBy('id');
		        $groups = $all_fields->groupBy('data_type_id')->toArray();
		        
		        $statuses = \DB::table('field_values')->orderBy('is_hidden', 'asc')->orderBy('sort')->get()->groupBy('field_id')->toArray();

		        $fields = \DB::table('data_rows')->where('type', 'relation')->orWhere('type', 'select_dropdown')->get();
		        $field_values = array();

		        $table_objects = array();
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
	                        if($type) {
	                        	if($details['table'] == 'cars' || $details['table'] == 'employees' || $details['table'] == 'clients')
	                        		$table_objects = isset($table_objects[$details['table']]) ? $table_objects[$details['table']] :$type->model_name::orderBy('choosed_at', 'DESC')->orderBy('name', 'ASC')->whereNull('deleted_at')->get();
	                        	else
	                            	$table_objects = isset($table_objects[$details['table']]) ? $table_objects[$details['table']] :$type->model_name::orderBy('choosed_at', 'DESC')->orderBy('name', 'ASC')->whereNull('deleted_at')->get();
	                        } else {
	                            $table_objects = isset($table_objects[$details['table']]) ? $table_objects[$details['table']] :\DB::table($details['table'])->orderBy('choosed_at', 'DESC')->orderBy('name', 'ASC')->whereNull('deleted_at')->get();
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
												'color' => isset($object->color) ? $object->color : '',
												'text' => $object->title
											],
											'value' => $object->id
		                    			);
		                        } elseif(isset($object->first_name)) {
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
		                        } elseif(isset($object->display_name)) {
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
												'color' => array_key_exists('color', $obj_arr) && !$object->color ? $object->getColor() : ($object->color ?? ''),
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
		                        	if(isset($object->last_name))
		                        		$last_name = $object->last_name;
		                        	if(ValueHelper::isJson($last_name)) {
		                        		$last_name = json_decode($object->last_name, true)['value'];
		                        	}
		                        	$obj_arr = $object->toArray();

		                        	$field_values[$field->id][$object->id] = array(
		                    				'label' => [	
												'id' =>	$object->id,
												'sort' => $i,
												'file' => $avatar,
												'is_hidden' => 0,
												'field_id' => $field->id,
												'color' => array_key_exists('color', $obj_arr) && !$object->color ? $object->getColor() : ($object->color ?? ''),
												'text' => $name.($last_name ? ' '.$last_name:''),
											],
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
												'color' => isset($object->color) && !$object->color ? $object->getColor() : ($object->color ?? ''),
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
