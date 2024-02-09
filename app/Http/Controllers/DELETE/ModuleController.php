<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Nwidart\Modules\Facades\Module;

class ModuleController extends Controller
{
	public function list()
	{
		$modules = Module::all();

		return view('modules.list', compact('modules'));
	}

	public function installed()
	{
		$modules = Module::allEnabled();

		return view('modules.list', compact('modules'));
	}

	public function show($module)
	{
		$modules = Module::all();

		foreach($modules as $m) {
			if($m->get('alias') == $module) {
				$module = $m;
			}
		}

		return view('modules.show', compact('module'));
	}

	public function install($module)
	{
		$tenant = tenant('id');
		// echo $tenant;
		// die();
		$modules = Module::all();

		foreach($modules as $m) {
			if($m->get('alias') == $module) {
				$module = $m;
			}
		}
		$config = file_get_contents(getcwd().'/../Modules/'.$module->getName().'/module.json');
		
		$config = json_decode($config, true);

		if(isset($config['images'])) {
			foreach ($config['images'] as $key => $value) {
				$file_path = getcwd().'/../Modules/'.$module->getName().'/'.$value;
				$path = dirname(public_path('assets/'.$module->getName().'/'.$value));
			    if(!\File::isDirectory($path))
			        \File::makeDirectory($path, 0777, true, true);
				\File::copy($file_path, public_path('assets/'.$module->getName().'/'.$value));
			}
		}
		if(isset($config['menu'])) {
			$module_side_item = new \App\Models\SidebarItem;
	        $module_side_item->name = $module->get('display_name');
	        $module_side_item->code = $module->get('alias');
	        $module_side_item->link = $config['menu'][0]['link'];
	        //$module_side_item->url = '';
	        $module_side_item->save();
			foreach ($config['menu'] as $key => $item) {
				$sidebar_item = new \App\Models\SidebarItem;
		        $sidebar_item->name = $item['name'];
		        $sidebar_item->code = $item['name'];
		        $sidebar_item->link = $item['link'];
		        $sidebar_item->url = '';
		        $sidebar_item->parent_id = $module_side_item->id;
		        $sidebar_item->save();
			};
		}

		\App\Models\SidebarItem::fixTree();
		//php artisan tenants:migrate --path=Modules/Products/Database/Migrations 
		//php artisan module:migrate Products --database=admin_opt6
		//php artisan make:migration create_remnants_table --path=/Modules/Products/Database/Migrations
		//php artisan tenants:migrate
		//php artisan tenants:migrate --path=database/migrations/tenant/n
        if(isset($config['entities'])) {
        	foreach ($config['entities'] as $key => $entity) {
        		echo 1;
				\DB::table('data_types')->insert([
					'name' => $entity['name'],
					'slug' => $entity['name'],
					'display_name_singular' => $entity['display_name_singular'],
					'display_name_plural' => $entity['display_name_plural'],
					'model_name' => $entity['class']
				]);
				$data_type = \DB::table('data_types')->where('model_name', $entity['class'])->first();
				foreach($entity['sections'] as $section) {
					\DB::table('field_sections')->insert([
						'name' => $section['name'],
						'page' => $entity['name']
					]);
					$s = \DB::table('field_sections')->where('page', $entity['name'])->where('name', $section['name'])->first();
					foreach($section['fields'] as $field) {
						$details = '';
						if(isset($field['table'])) {
							$details = json_encode(array('table' => $field['table']));
						} elseif(isset($field['options'])) {
							$details['options'] = array();
				            foreach($field['options'] as $option => $value) {
				                $details['options'][$option] = $value;
				            }
				            $details = json_encode($details, true);
						};
						\DB::table('data_rows')->insert([
							'data_type_id' => $data_type->id,
							'field' => $field['name'],
							'type' => $field['type'],
							'display_name' => $field['display_name'],
							'section_id' => $s->id,
							'visible_always' => $field['visible_always'],
							'only_read' => isset($field['read_only']) && $field['read_only'] ? 1 : 0,
							'is_plural' => isset($field['is_plural']) ? 1 : 0,
							'details' => $details
						]);
					}
					
				}
			}

			if(isset($config['changes_entities'])) {
				foreach ($config['changes_entities'] as $key => $entity) {
					$entity_class = $entity['class'];
					$data_type = \DB::table('data_types')->where('model_name', $entity_class)->first();
					$slug = $data_type->slug;

					foreach($entity['sections'] as $section) {
						\DB::table('field_sections')->insert([
							'name' => $section['name'],
							'page' => $slug
						]);
						$s = \DB::table('field_sections')->where('page', $slug)->where('name', $section['name'])->first();

						foreach($section['fields'] as $field) {
							$details = array();
							if(isset($field['table'])) {
								$details = json_encode(array('table' => $field['table']));
							} elseif(isset($field['options'])) {
								$details['options'] = array();
					            foreach($field['options'] as $option => $value) {
					                $details['options'][$option] = $value;
					            }
					            $details = json_encode($details, true);
							} else {
								$details = '';
							};
							\DB::table('data_rows')->insert([
								'data_type_id' => $data_type->id,
								'field' => $field['name'],
								'type' => $field['type'],
								'display_name' => $field['display_name'],
								'section_id' => $s->id,
								'visible_always' => $field['visible_always'],
								'only_read' => isset($field['read_only']) && $field['read_only'] ? 1 : 0,
								'is_plural' => isset($field['is_plural']) ? 1 : 0,
								'details' => $details
							]);
						}
					}
				}
			}
        }
		

		\Artisan::call('tenants:migrate',
		 array(
		   '--path' => getcwd().'/../Modules/'.$module.'/Database/Migrations',
		   '--tenants' => $tenant
		));
		//print_r($config);

		$mod = Module::find($module);
		$mod->enable();

		if($module == 'Addresses') {
			$addr = new \Modules\Addresses\Entities\Address;
			$addr->name = 'test';
			$addr->address = 'Москва';
			$addr->save();
		}
/*		
rm -rf /home/bitrix/www/bitrix/managed_cache/* 
rm -rf /home/bitrix/www/bitrix/stack_cache/* 
rm -rf /home/bitrix/www/bitrix/MYSQL/*

rm -rf /home/bitrix/www/upload/resize_cache/*
*/
		echo getcwd().'/../Modules/'.$module.'/Database/Migrations';
		die();

		return redirect()->back();
	}
	public function uninstall($module)
	{
		$modules = Module::allEnabled();

		foreach($modules as $m) {
			if($m->get('alias') == $module) {
				$module = $m;
			}
		}

		$config = file_get_contents(getcwd().'/../Modules/'.$module.'/module.json');
		
		$config = json_decode($config, true);

		if(isset($config['menu'])) {
			$link = \App\Models\SidebarItem::where('name', $module->get('display_name'))->where('code', $module->get('alias'))->first();
			if($link)
				$link->delete();
			foreach ($config['menu'] as $key => $item) {
				$link = \App\Models\SidebarItem::where('name', $item['name'])->where('link', $item['link'])->first();
				if($link)
					$link->delete();
			};
			\App\Models\SidebarItem::fixTree();
		}
		
        if(isset($config['entities'])) {
			foreach ($config['entities'] as $key => $entity) {
				// foreach($entity['sections'] as $section) {
				// 	$s = \DB::table('field_sections')->where('page', $entity['name'])->where('name', $section['name'])->first();
				// 	foreach($section['fields'] as $field) {
				// 		\DB::table('data_rows')->where([
				// 			'section_id' => $s->id,
				// 		])->delete();
				// 	}
					
				// }
				\DB::table('data_types')->where([
						'model_name' => $entity['class']
					])->delete();
				\DB::table('field_sections')->where([
						'page' => $entity['name']
					])->delete();
				
			}
		}
		\Artisan::call('tenants:rollback',
		 array(
		   '--path' => getcwd().'/../Modules/'.$module.'/Database/Migrations'));

		$mod = Module::find($module);
		$mod->disable();

		return redirect()->back();
		//print_r($config);
	}
}