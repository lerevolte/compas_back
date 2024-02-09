<?php

namespace App\Models;
use Auth;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ValueHelper;


class Settings extends Model
{
    public static function get() {
	    $user = \Auth::user();

	    if(!$user) {
	    	$user = User::find(1);
	    }
	    $cache_name = tenant('id').':settings-'.$user->id;
	    info($cache_name);
	    if($user) {
	        //$settings = cache('settings-'.$user->id);
	        $settings = cache()->getMemcached()->get($cache_name);
	        if(!$settings) {
	        	$settings = cache()->getMemcached()->get(tenant('id').':settings-1');
	        	if($settings)
	        		cache()->getMemcached()->add($cache_name, $settings);
	        }
	        if(!$settings) {
	            // $s = cache()->rememberForever('settings-'.$user->id, function() use ($user)
	            // {
	            	info('SETTINGS CACHE');
	            	$settings = array();
			        $settings['is_admin'] = $user->isAdmin();
			        $modules = \DB::table('modules')->select('id', 'name', 'slug')->get()->keyBy('slug')->toArray();
			        $modules_config = \Nwidart\Modules\Facades\Module::all();
			        $data_types = \DB::table('data_types')->get();
			        $models_by_name = $data_types->keyBy('name')->toArray();
			        $settings['models'] = $models_by_name;
			        $models = $data_types->pluck('name', 'id')->toArray();
			        
			        $all_fields = \DB::table('data_rows')->where(['is_remove' => 0])/*->where('type', '!=', 'text_group')*/->orderBy('id')->get();
			        $groups = $all_fields->groupBy('data_type_id')->toArray();
			        
			        $statuses = \DB::table('field_values')->orderBy('is_hidden', 'asc')->orderBy('sort')->get()->groupBy('field_id')->toArray();

			        $fields = \DB::table('data_rows')->where('type', 'relation')->orWhere('type', 'select_dropdown')->get();
			        $field_values = array();

			        $table_objects = array();
			        $user_roles = $user->roles_all()->pluck('id')->toArray();
			        $permissions = array();


			        // $tables_relation = array(
		            //     'car_employee'
		            // );
		            // $choosed = array();
		            // foreach($tables_relation as $table) {
		            //     $choosed[$table] = array();
		            //     $table_data = \DB::table($table)->get()->toArray();
		            //     foreach($table_data as $table_object) {
		            //         foreach($table_object as $table_field => $table_val){
		            //             if(!isset($choosed[$table][$table_field]))
		            //                 $choosed[$table][$table_field] = array($table_val);
		            //             elseif(!in_array($table_val, $choosed[$table][$table_field]))
		            //                 $choosed[$table][$table_field][] = $table_val;
		            //         }
		                    
		            //     }
		            // }

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
			            $permissions[$field->data_type_id]['write'][$field->field] = $perm;
			        }
			        foreach($fields as $field) {
			            if($field->details && isset($models[$field->data_type_id]) && $details = json_decode($field->details, true)) {
			                $field_values[$field->id] = array();
			                if(isset($details['table'])) {
			                    $type = isset($models_by_name[$details['table']]) ? $models_by_name[$details['table']] : null;
			                    //info($details['table']);
		                        if($type)
		                            $table_objects = isset($table_objects[$details['table']]) ? $table_objects[$details['table']] :$type->model_name::orderBy('choosed_at', 'DESC')->whereNull('deleted_at')->get();
		                        else
		                            $table_objects = isset($table_objects[$details['table']]) ? $table_objects[$details['table']] :\DB::table($details['table'])->orderBy('choosed_at', 'DESC')->whereNull('deleted_at')->get();
			                    $table_objects[$details['table']] = $table_objects;
			                    $i = 0;
			                    foreach ($table_objects[$details['table']] as $i => $object) {
			                    	$avatar = isset($object->avatar) ? $object->avatar : (isset($object->photo) ? $object->photo : '');
			                    	if($avatar) {

			                    		$avatar = json_decode($avatar, true);
			                    		if(isset($avatar[0]['url']))
			                    			$avatar = $avatar[0]['url'];
			                    	}
			                        if(isset($object->display_name)) {
			                            $field_values[$field->id][$object->id] = array(
			                    				'label' => [	
													'id' =>	$object->id,
													'sort' => $i,
													'file' => $avatar,
													'is_hidden' => 0,
													'field_id' => $field->id,
													'color' => isset($object->color) ? $object->color : '',
													'text' => $object->display_name
												],
												'value' => $object->id
			                    			);/*array(
			                                'value' => $object->id,
			                                'label' => $object->display_name,
			                                'color' => isset($object->color) ? $object->color : '',
			                                'file' => $avatar,
			                            );*/
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
			                            // $field_values[$field->id][$object->id] = array(
			                            //     'value' => $object->id,
			                            //     'label' => $object->first_name.' '.$object->last_name,
			                            //     'color' => isset($object->color) ? $object->color : '',
			                            //     'file' => $avatar,
			                            //     //'sort' => $i
			                            // );
			                        } elseif(isset($object->name)) {
			                        	$name = $object->name;
			                        	if(ValueHelper::isJson($name)) {
			                        		$name = json_decode($object->name, true)['value'];
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
													'text' => $name.(isset($object->last_name) ? ' '.$object->last_name:''),
												],
												'value' => $object->id
			                    			);
			                            // $field_values[$field->id][$object->id] = array(
			                            //     'value' => $object->id,
			                            //     'label' => $name.(isset($object->last_name) ? ' '.$object->last_name:''),
			                            //     'color' => isset($obj_arr['color']) && !$object->color ? $object->getColor() : ($object->color ?? ''),
			                            //     'file' => $avatar,
			                            //     //'sort' => $i
			                            // );
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
			                            // $field_values[$field->id][$object->id] = array(
			                            //     'value' => $object->id,
			                            //     'label' => $object->name,
			                            //     'color' => isset($object->color) && !$object->color ? $object->getColor() : ($object->color ?? ''),
			                            //     'file' => $avatar,
			                            //     //'sort' => $i
			                            // );
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
			                    'list_values' => array(),
			                    'options' => array(),
			                    'fields' => array(),
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
			                    	// $statuses
			                        $settings[$models[$model_id]]['list_values'][$field->field] = $settings[$models[$model_id]]['options'][$field->field];//isset($statuses[$field->id]) ? $statuses[$field->id] : array();
			                    }
			                    if($field->type == 'select_dropdown' || $field->type == 'relation')
			                        $settings[$models[$model_id]]['list_values'][$field->field] = isset($field_values[$field->id]) ? $field_values[$field->id] : null;
			                    $settings[$models[$model_id]]['colors'][$field->field] = $field->label_color ?? '';
			                    $settings[$models[$model_id]]['perms'][$field->field] = array(
			                        'read' => $permissions[$model_id]['read'][$field->field],
			                        'write' => $permissions[$model_id]['write'][$field->field],
			                    );
			                    $settings[$models[$model_id]]['used_in_modules'][$field->field] = array();
			                    if($field->module) {
			                    	$used_in_modules = json_decode($field->module, true);
			                    	foreach($used_in_modules as $module_slug) {
			                    		$settings[$models[$model_id]]['used_in_modules'][$field->field][] = $modules[$module_slug];
			                    	}
			                    }
			                    //info($choosed);
			                    if(isset($settings[$models[$model_id]]['perms'][$field->field]['read']) && $settings[$models[$model_id]]['perms'][$field->field]['read'] != 'disabled') {
			                    	if (\Schema::hasColumn($models[$model_id], $field->field))
			                        	$settings[$models[$model_id]]['fields'][$field->field] = $field;
			                        // if($field->type == 'relation') {
			                        // 	info('IF1');
			                        // 	if($field->details) {
			                        // 		info('IF2');
			                        // 		if($details = json_decode($field->details, true)) {
			                        // 			info('IF3');
			                        			
			                        // 				info('IF4');
			                        // 		}
			                        // 	}
			                        // }
			                        if($field->type == 'relation' && $field->details && $details = json_decode($field->details, true)) {
			                        	if(isset($details['unique'])) {
			                                $choosed = \DB::table($details['table'])->whereNotNull($details['relation_field'])->pluck('id')->toArray();
			                                $settings[$models[$model_id]]['fields'][$field->field]->choosed = $choosed;
			                            }
			                        	// if(isset($details['relations'])) {
			                        	// 	info('NEED CHOOSE');
				                        // 	info($models[$model_id]);
				                        // 	info($field->field);
				                        // 	info($choosed[$details['relations']][$field->field]);

				                        // 	//$settings[$models[$model_id]]['fields'][$field->field] = json_decode(json_encode($settings[$models[$model_id]]['fields'][$field->field]), true);
				                        	
				                        // 	//$settings[$models[$model_id]]['fields'][$field->field] = collect($settings[$models[$model_id]]['fields'][$field->field]);
			                        	// }
			                        	
			                        }
			                    }
			                }
			            }
			        }
			        
			        $permissions = $user->getPermissions();
			        $perms = array();
			        foreach ($permissions as $perm) {
			            if(isset($perm['key']) && $perm['area'] && !$perm['is_parent']) {
			                if(!isset($perms[$perm['key']]) || $perms[$perm['key']] != '') {
			                    //$perms[$perm['key']] = $perm['value'];
			                    $perms[$perm['entity']][$perm['type']] = $perm['value'];
			                }
			            }
			        }
			        $settings['entities']['perms'] = $perms;
			        $tree = \App\Models\SidebarItem::defaultOrder()->get()->toTree()->toArray();
			        $settings['settings']['sidebar_items'] = $tree;
	                // $settings = array();
	                // $settings['is_admin'] = $user->isAdmin();
	                // $models = \DB::table('data_types')->pluck('name', 'id')->toArray();
	                
	                
	                // $fields = \DB::table('data_rows')->where(['is_remove' => 0])/*->where('type', '!=', 'text_group')*/->orderBy('id')->get();
	                // $groups = $fields->groupBy('data_type_id')->toArray();

	                // foreach($groups as $model_id => $group) {
	                // 	if(isset($models[$model_id])) {
		            //         $settings[$models[$model_id]] = array(
		            //             'perms' => array(),
		            //             'colors' => array(),
		            //             'list_values' => array(),
		            //             'fields' => array()
		            //         );
		            //         foreach($group as $field) {

		            //             if($field->type == 'status')
		            //                 $settings[$models[$model_id]]['list_values'][$field->field] = \App\Models\Field::getStatuses($field->id);
		            //             if($field->type == 'select_dropdown' || $field->type == 'relation')
		            //                 $settings[$models[$model_id]]['list_values'][$field->field] = \App\Models\Field::getValues($field->id);
		            //             $settings[$models[$model_id]]['colors'][$field->field] = $field->label_color ?? '';
		            //             $settings[$models[$model_id]]['perms'][$field->field] = array(
		            //                 'read' => (!$user->canRead($field->field, $models[$model_id]) ? 0:1),
		            //                 'write' => (!$user->canWrite($field->field, $models[$model_id]) ? 0:1)
		            //             );
		            //             if(isset($settings[$models[$model_id]]['perms'][$field->field]['read']) && $settings[$models[$model_id]]['perms'][$field->field]['read'] != 'disabled') {
		            //                 $settings[$models[$model_id]]['fields'][$field->field] = $field;
		            //             }
		            //         }
		            //     }
	                // }
	                
	                // $permissions = $user->getPermissions();
	                // $perms = array();
	                // foreach ($permissions as $perm) {
	                //     if(isset($perm['key']) && $perm['area'] && !$perm['is_parent']) {
	                //         if(!isset($perms[$perm['key']]) || $perms[$perm['key']] != '') {
	                //             //$perms[$perm['key']] = $perm['value'];
	                //             $perms[$perm['entity']][$perm['type']] = $perm['value'];
	                //         }
	                //     }
	                // }
	                // $settings['entities']['perms'] = $perms;
	                
	                
	                // //$point_field = $account->getPointField();
	                // //$type_field = \App\Models\Order::getFieldByCode('orders', 'type');
	                // //$settings['settings']['point_type'] = \App\Models\Order::getFieldByCode('addresses', $point_field);
	                // //$settings['settings']['type'] = $type_field;
	                // //$settings['settings']['point_types'] = \App\Models\Field::getFieldValuesModel($point_field, 39);
	                // $tree = \App\Models\SidebarItem::get()->toTree()->toArray();
	                // $settings['settings']['sidebar_items'] = $tree;//\App\Models\SidebarItem::orderBy('sort')->get();
	                // // foreach ($settings['settings']['sidebar_items'] as $key => $value) {
	                // // 	$settings['settings']['sidebar_items'][$key]['children'] = array();
	                // // }
	                // $settings['managers'] = \App\Models\User::orderBy('name')->get()->filter(function ($user) {
	                //     return $user->hasRole('manager');
	                // });
	                // // $settings['account']['map_zone_radius'] = $account->map_zone_radius;
	                // // $settings['account']['map_stop_car_radius'] = $account->map_stop_car_radius;
	                // // $settings['account']['map_stop_time'] = $account->map_stop_time;
	                // // $settings['account']['map_latitude'] = $account->map_latitude;
	                // // $settings['account']['map_longitude'] = $account->map_longitude;
	                // // $settings['account']['yandex_api_key'] = $account->yandex_api_key;
	                // //$settings['account']['tcompanies'] = \App\Models\Order::where('is_tc',1)->get();
	                
	                //return $settings;
	                // $users = \App\Models\User::get();
			        // foreach($users as $user) {
			        // 	$cache_name = tenant('id').':settings-'.$user->id;
	                	cache()->getMemcached()->add($cache_name, $settings);
	                //}
	            //});

	            return $settings;
	        } else
	            return $settings;
	    }
	}

	public static function validators() {
        $validators = cache('validators');
        if(!$validators) {
            $s = cache()->rememberForever('validators', function(){
            	$validators = \DB::table('validators')->get()->keyBy('id')->toArray();

                return $validators;
            });

            return $s;
        } else
            return $validators;
	}

	public static function fixTableOrder($count_columns, $columns_reorder)
	{
	    if($count_columns > count($columns_reorder)) {
	        $columns_reorder = range(reset($columns_reorder), end($columns_reorder));
	        while($count_columns > count($columns_reorder)) {
	            $val = max($columns_reorder);
	            $columns_reorder[] = $val + 1;
	        }
	        if(count($columns_reorder) > $count_columns) {
	            while(count($columns_reorder) > $count_columns) {
	                $val = max($columns_reorder);
	                $key = array_search($val, $columns_reorder);
	                unset($columns_reorder[$key]);
	            }
	        }
	    } elseif(count($columns_reorder) > $count_columns) {
	        while(count($columns_reorder) > $count_columns) {
	            $val = max($columns_reorder);
	            $key = array_search($val, $columns_reorder);
	            unset($columns_reorder[$key]);
	        }
	    }
	    foreach($columns_reorder as $key => $column) {
	        $columns_reorder[$key] = (string)$column;
	    }
	    return array_values($columns_reorder);
	}

	public static function clear_cache()
	{
		// $keys = cache()->getMemcached()->getAllKeys();
        // $regex = tenant('id').':settings-*';

        // foreach($keys as $item) {
        //     if(preg_match('/'.$regex.'/', $item)) {
        //     	info('delete '.$item);
        //         cache()->getMemcached()->delete($item);
        //     }
        // }
        //\App\Jobs\SettingsClearJob::dispatch();
		$users = \App\Models\User::get();
		foreach($users as $user) {
			cache()->getMemcached()->delete(tenant('id').':settings-'.$user->id);
		}
		// \App\Jobs\SettingsClearJob::dispatch();


		// $keys = cache()->getMemcached()->getAllKeys();
        // $regex = tenant('id').':settings-*';
        // info($regex);
        // foreach($keys as $item) {
        // 	info('privet : '.$item);
        //     if(preg_match('/'.$regex.'/', $item)) {
        //     	info('delete '.$item);
        //         cache()->getMemcached()->delete($item);
        //     }
        // }
	}
}
