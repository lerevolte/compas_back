<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Storage;
use Auth;
use Mail;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CrudService;
use App\Mail\AccountRegistered;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TenantService
{
    private CrudService $crudService;

    public function __construct(CrudService $crudService)
    {
        $this->crudService = $crudService;
    }

    public function create(array $data)
    {
        if(!isset($data['domain'])) {
            $last_id = \DB::table('accounts')->orderBy('id', 'desc')->first()->id + 1;
            $domain = "$last_id";
        } else {
            $domain = strtolower($data['domain']);
        }
        if($domain == 'admin')
            throw new HttpResponseException(response()->json([
                'success'   => false,
                'message'   => 'Ошибки валидации',
                'data'      => [
                    'domain' => ['Портал уже существует']
                ]
            ]));

        try {
            $tenant = Tenant::create([
                'id' => $domain,
            ]);
        } catch (\Illuminate\Database\QueryException $exception) {
            throw new HttpResponseException(response()->json([
                'success'   => false,
                'message'   => 'Ошибки валидации',
                'data'      => [
                    'domain' => ['Портал уже существует']
                ]
            ]));
        }
        
        $tenant->domains()->create([
            'domain' => $domain.'.compas.pro',
        ]);
        $res = $tenant->run(function ($tenant) {
            $objects = \DB::connection('seeds')->table('tariffs')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('tariffs')->insert([$odata]);
            };
        });

        $gibdd_queries = \DB::table('gibdd_queries')->where('email', $data['email'])->get();

        tenancy()->central(function () use ($tenant) {
            \App\Models\Settings::clear_cache();
            $tariff_name = 'Бесплатный - 0 руб';
            if(isset($data['tariff']) && $t = \DB::table('tariffs')->where('id', $data['tariff'])->first()) {
                $tariff_name = $t->name;
            };
            $result = $this->crudService->batch('accounts', [[
                'id' => 0,
                'name' => $tenant->id,
                'balance' => 0,
                'tariff' => $tariff_name,
                'user_id' => 1
            ]]);
            $account = \App\Models\Account::find($result['id']);
            $account->tenant_id = $tenant->id;
            $account->saveQuietly();
        });

        $res = $tenant->run(function ($tenant) use ($data, $domain, $gibdd_queries) {
            $objects = \DB::connection('seeds')->table('timezones')->get();

            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                

                unset($odata['id']);
                
                \DB::table('timezones')->insert([$odata]);
                
            };

            $objects = \DB::connection('seeds')->table('analytic')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                
                \DB::table('analytic')->insert([$odata]);
                
            };

            $objects = \DB::connection('seeds')->table('data_rows')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                
                \DB::table('data_rows')->insert([$odata]);
                
            };

            $objects = \DB::connection('seeds')->table('settings')->get();
            foreach ($objects as $object) {

                if($object->user_id <= 1 && $object->type != 'account')
                    \DB::table('settings')->insert([
                        'id' => $object->id,
                        'key' => $object->key,
                        'display_name' => $object->display_name,
                        'value' =>$object->value,
                        'type' =>$object->type,
                        'entity' =>$object->entity,
                        'user_id' =>$object->user_id,
                    ]);
                
            };
            \DB::table('sidebar_items')->delete();
            $objects = \DB::connection('seeds')->table('sidebar_items')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();

                \DB::table('sidebar_items')->insert([$odata]);

            };

            $objects = \DB::connection('seeds')->table('field_sections')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                
                \DB::table('field_sections')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('field_values')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                
                \DB::table('field_values')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('data_types')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                
                \DB::table('data_types')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('section_fields_sort')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('section_fields_sort')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('modules')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('modules')->insert([$odata]);
            };

            

            $objects = \DB::connection('seeds')->table('validators')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('validators')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('module_categories')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('module_categories')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('module_required_fields')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('module_required_fields')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('tabs')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('tabs')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('user_roles')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('user_roles')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('roles')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                
                \DB::table('roles')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('filters')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('filters')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('balances')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('balances')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('wialon_config')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('wialon_config')->insert([$odata]);
            };

            $objects = \DB::connection('seeds')->table('permissions')->get();
            foreach ($objects as $object) {
                $odata = collect($object)->toArray();
                unset($odata['id']);
                
                \DB::table('permissions')->insert([$odata]);
            };

            $user_source = \DB::connection('seeds')->table('users')->first();

            $pass = $data['password'];
            $admin_data = [
                'name' => null,
                'password' => Hash::make($pass),
                'tables' => $user_source->tables
            ];
            $user = User::create($admin_data);
            $admin_data['id'] = $user->id;
            $admin_data['is_new'] = 1;
            $admin_data['email'] = $data['email'];
            $admin_data['role_id'] = 1;
            $admin_data['is_admin'] = 1;
            $admin_data['name'] = 'Пользователь #1';
            \App\Models\History::saveForObject('users', [$admin_data]);
            unset($admin_data['id']);
            unset($admin_data['is_new']);
            $user->name = $admin_data['name'];
            $user->email = $admin_data['email'];
            $user->role_id = 1;
            $user->is_admin = 1;
            $user->tables = $user_source->tables;
            $user->saveQuietly();

            $result = $this->crudService->batch('users', [[
                'id' => 1,
                'user_id' => 1
            ]]);
            $menu = \DB::table('settings')->where(['type' => 'sidebar', 'user_id' => $user->id])->first();
            $menu = json_decode($menu->value, true);

            $token = $user->api_token;
            if(!$token) {
                $token = $user->generateToken();
            }

            // if($data['email'] == 'lerevolte@yandex.ru' || $data['email'] == 'plusmario@yandex.ru') {
            //     if($gibdd_queries) {
            //         foreach($gibdd_queries as $value => $item) {
            //             if($item->requisite == 'СТС') {
            //                 $result = $this->crudService->batch('cars', [[
            //                     'id' => 0,
            //                     'number' => $item->additional_value,
            //                     'sts_number' => $item->value,
            //                     'user_id' => 1
            //                 ]]);
            //             }
            //             if($item->requisite == 'Водительское удостоверение') {
            //                 $result = $this->crudService->batch('employees', [[
            //                     'id' => 0,
            //                     'driver_license' => $item->value,
            //                     'user_id' => 1
            //                 ]]);
            //             }

            //             if($item->requisite == 'ИНН') {
            //                 $result = $this->crudService->batch('companies', [[
            //                     'id' => 0,
            //                     'inn' => $item->value,
            //                     'kpp' => $item->additional_value,
            //                     'user_id' => 1
            //                 ]]);
            //             }
            //         };
                
            //         \Modules\Gibdd\Entities\Module::findFines();
            //         $sts_prefix = '124000000000';
            //         $license_prefix = '122000000000';
            //         $inn_prefix = '200';
            //         foreach($gibdd_queries as $value => $item) {
            //             if($item->requisite == 'УИН' && !\App\Models\GibddFine::where('number_doc', $item->value)->exists()) {
            //                 $fines = json_decode($item->result, true);
            //                 foreach($fines as $record) {
            //                     if(!$record['name'])
            //                         continue;
            //                     $fine = new \App\Models\GibddFine;
            //                     $fine->payer_identifier = $record['payer_identifier'];
            //                     $fine->wire_username = $record['wire_username'];
            //                     if(isset($record['additional_payer_identifier']))
            //                         $fine->additional_payer_identifier = $record['additional_payer_identifier'];
            //                     $fine->saveQuietly();
            //                     $history_text = 'Создана запись: '.$fine->id;
            //                     $history = new \App\Models\History(['entity' => 'fines_gibdd', 'event' => 'OBJECT_CREATED', 'entity_id' => $fine->id, 'user_id' => 1, 'text' => $history_text]);
            //                     $history->save();
            //                     $fine->name = $record['name'];
            //                     $record['id'] = $fine->id;
            //                     $record['user_id'] = null;
            //                     unset($record['wire_username']);
            //                     unset($record['payer_identifier']);
            //                     if(isset($record['additional_payer_identifier'])) {
            //                         if(strstr($record['additional_payer_identifier'], $sts_prefix)) {
            //                             $sts = str_replace($sts_prefix, '', $record['additional_payer_identifier']);
            //                             $arr = \DB::table('cars')->where('sts_number', $sts)->pluck('id');
            //                             if(count($arr)) {
            //                                 $record['car_id'] = array_pop($arr);
            //                             } else {
            //                                 $record['name'] = $record['name'].' СТС: '.$sts;
            //                             }
            //                         }
            //                         if(strstr($record['additional_payer_identifier'], $license_prefix)) {
            //                             $license = str_replace($license_prefix, '', $record['additional_payer_identifier']);
            //                             $arr = \DB::table('employees')->where('driver_license', $license)->pluck('id');
            //                             if(count($arr)) {
            //                                 $record['employee_id'] = array_pop($arr);
            //                             } else {
            //                                 $record['name'] = $record['name'].' Удостоверение: '.$license;
            //                             }
            //                         }
            //                         unset($record['additional_payer_identifier']);
            //                     }
            //                     $record['user_id'] = 1;
            //                     \App\Models\History::saveForObject('fines_gibdd', array($record));
            //                     $fine_id = $record['id'];
            //                     unset($record['id']);
            //                     \DB::table('fines_gibdd')->where('id', $fine_id)->update($record);
            //                 }
            //             }
            //         };
            //         $settings = \App\Models\Settings::get(true);
            //         foreach($gibdd_queries as $value => $item) {
            //             if($item->requisite == 'УИН' && !\App\Models\GibddFine::where('number_doc', $item->value)->exists()) {
            //                 $fines = json_decode($item->result, true);
            //                 foreach($fines as $record) {
            //                     $record['user_id'] = null;
            //                     unset($record['wire_username']);
            //                     unset($record['payer_identifier']);
            //                     if(isset($record['additional_payer_identifier'])) {
            //                         if(strstr($record['additional_payer_identifier'], $sts_prefix)) {
            //                             $sts = str_replace($sts_prefix, '', $record['additional_payer_identifier']);
            //                             $arr = \DB::table('cars')->where('sts_number', $sts)->pluck('id');
            //                             if(count($arr)) {
            //                                 $record['car_id'] = array_pop($arr);
            //                             } else {
            //                                 $record['name'] = $record['name'].' СТС: '.$sts;
            //                             }
            //                         }
            //                         if(strstr($record['additional_payer_identifier'], $license_prefix)) {
            //                             $license = str_replace($license_prefix, '', $record['additional_payer_identifier']);
            //                             $arr = \DB::table('employees')->where('driver_license', $license)->pluck('id');
            //                             if(count($arr)) {
            //                                 $record['employee_id'] = array_pop($arr);
            //                             } else {
            //                                 $record['name'] = $record['name'].' Удостоверение: '.$license;
            //                             }
            //                         }
            //                         unset($record['additional_payer_identifier']);
            //                     }
            //                     $record['user_id'] = 1;
            //                     \App\Models\History::saveForObject('fines_gibdd', array($record));
            //                     $fine_id = $record['id'];
            //                     unset($record['id']);
            //                     \DB::table('fines_gibdd')->where('id', $fine_id)->update($record);
            //                 }
            //             }
            //         };
            //     }
            // } else {
            //     if(isset($data['number']) || isset($data['sts_number'])) {
            //         $result = $this->crudService->batch('cars', [[
            //             'id' => 0,
            //             'number' => $data['number'] ?? null,
            //             'sts_number' => $data['sts_number'] ?? null,
            //             'user_id' => 1
            //         ]]);
            //     }

            //     if(isset($data['driver_license'])) {
            //         $result = $this->crudService->batch('employees', [[
            //             'id' => 0,
            //             'driver_license' => $data['driver_license'],
            //             'user_id' => 1
            //         ]]);
            //     }

            //     if(isset($data['inn'])) {
            //         $result = $this->crudService->batch('companies', [[
            //             'id' => 0,
            //             'inn' => $data['inn'],
            //             'kpp' => $data['kpp'],
            //             'user_id' => 1
            //         ]]);
            //     }

            //     if(isset($data['sts_number']) || isset($data['inn']) || isset($data['driver_license'])) {
            //         \Modules\Gibdd\Entities\Module::findFines();
            //     };

                
            //     if(isset($data['num_post'])) {
            //         $r = \Modules\Gibdd\Entities\Module::findByNum($data['num_post']);
            //     }
            // }

            if(isset($data['tariff'])) {
                \DB::table('settings')->where('key', 'tariff')->update(['value' => $data['tariff']]);
            }
            try {
                Mail::to($data['email'])->send(new AccountRegistered(
                    account: "https://$domain.compas.pro",
                    email: $data['email'],
                    password: $data['password']
                ));
            } catch (\Exception $e) {
                \Log::warning('Failed to send registration email: ' . $e->getMessage());
            }
            
            return [
                'success' => true, 
                'code' => 200, 
                'domain' => $domain, 
                'token' => $token, 
                'password' => $pass,
                'url' => isset($menu[0]['link']) ? $menu[0]['link'] : (isset($menu[0]['children'][0]['link']) ? $menu[0]['children'][0]['link'] : null)
            ];
        });

        return $res;
    }

    public function syncDatabase(Tenant $tenant)
    {
        tenancy()->initialize($tenant);
        
        // Выполнение миграций
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant_updates',  // Путь к миграциям для тенантов
            '--force' => true,
        ]);
    }
    public function syncDatabase2(Tenant $tenant)
    {
        tenancy()->initialize($tenant);
        
        // Выполнение миграций
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant_updates2',  // Путь к миграциям для тенантов
            '--force' => true,
        ]);
    }

    public function syncEntity($slug)
    {
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        if(!$entity)
            abort(404);

        $data_rows = \DB::table('data_rows')->where('data_type_id', $entity->id)->get();
        $menu = \DB::table('settings')->where([
            'key' => 'menu',
            'entity' =>$entity->slug,
            'user_id' => null
        ])->first();
        $field_sections = \DB::table('field_sections')->where('page', $entity->slug)->get();

        $tenants = \App\Models\Tenant::get();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant); 
            info('syncentity1');
            if (!Schema::hasTable('car_categories')) {
                Schema::create('car_categories', function (Blueprint $table) {
                    $table->unsignedInteger('id')->primary();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();
                    $table->timestamp('choosed_at')->nullable();
                    $table->timestamp('deleted_at')->nullable();
                    $table->integer('user_id')->nullable();
                    $table->binary('model_id')->nullable();
                    $table->string('name', 255)->nullable();
                });
                info('syncentity2');
                // Вставка начальных данных
                \DB::table('car_categories')->insert([
                    ['id' => 1, 'created_at' => '2022-10-27 09:21:00', 'updated_at' => '2022-10-27 09:21:00', 'name' => 'Легковые'],
                    ['id' => 2, 'created_at' => '2022-10-27 09:21:27', 'updated_at' => '2022-10-27 09:21:27', 'name' => 'Лёгкие коммерческие'],
                    ['id' => 3, 'created_at' => '2022-10-27 09:21:29', 'updated_at' => '2022-10-27 09:21:29', 'name' => 'Грузовики'],
                    ['id' => 4, 'created_at' => '2022-10-27 09:21:35', 'updated_at' => '2022-10-27 09:21:35', 'name' => 'Седельные тягачи'],
                    ['id' => 5, 'created_at' => '2022-10-27 09:21:37', 'updated_at' => '2022-10-27 09:21:37', 'name' => 'Автобусы'],
                    ['id' => 6, 'created_at' => '2022-10-27 09:21:41', 'updated_at' => '2022-10-27 09:21:41', 'name' => 'Строительная и дорожная'],
                    ['id' => 7, 'created_at' => '2022-10-27 09:21:50', 'updated_at' => '2022-10-27 09:21:50', 'name' => 'Погрузчики'],
                    ['id' => 8, 'created_at' => '2022-10-27 09:22:07', 'updated_at' => '2022-10-27 09:22:07', 'name' => 'Автокраны'],
                    ['id' => 9, 'created_at' => '2022-10-27 09:22:10', 'updated_at' => '2022-10-27 09:22:10', 'name' => 'Коммунальная'],
                    ['id' => 10, 'created_at' => '2022-10-27 09:22:14', 'updated_at' => '2022-10-27 09:22:14', 'name' => 'Мотоциклы'],
                    ['id' => 11, 'created_at' => '2022-10-27 09:22:53', 'updated_at' => '2022-10-27 09:22:53', 'name' => 'Скутеры'],
                ]);
            }
            info('tenant '.$tenant->id);
            info('slug '.$entity->slug);
            //синхронизируем сущность
            $tenant_entity = \DB::table('data_types')->where('slug', $entity->slug)->first();
            if($tenant_entity) {
                \DB::table('data_types')
                    ->where('slug', $entity->slug)
                    ->update([
                        'name' => $entity->name,
                        'slug' => $entity->slug,
                        'created_at' => $entity->updated_at,
                        'updated_at' => $entity->updated_at,
                        'title_singular' => $entity->title_singular,
                        'title_plural' => $entity->title_plural,
                        'icon' => $entity->icon,
                        'model_name' => $entity->model_name,
                        'policy_name' => $entity->policy_name,
                        'controller' => $entity->controller,
                        'description' => $entity->description,
                        'generate_permissions' => $entity->generate_permissions,
                        'server_side' => $entity->server_side,
                        'details' => $entity->details,
                        'color' => $entity->color,
                        'menu' => $entity->menu,
                        'enable' => $entity->enable,
                        'slug_singular' => $entity->slug_singular,
                        'hidden' => $entity->hidden,
                        'empty_text' => $entity->empty_text,
                    ]);
            } else {

                \DB::table('data_types')->insert([
                    'id' => $entity->id,
                    'name' => $entity->name,
                    'slug' => $entity->slug,
                    'created_at' => $entity->updated_at,
                    'updated_at' => $entity->updated_at,
                    'title_singular' => $entity->title_singular,
                    'title_plural' => $entity->title_plural,
                    'icon' => $entity->icon,
                    'model_name' => $entity->model_name,
                    'policy_name' => $entity->policy_name,
                    'controller' => $entity->controller,
                    'description' => $entity->description,
                    'generate_permissions' => $entity->generate_permissions,
                    'server_side' => $entity->server_side,
                    'details' => $entity->details,
                    'color' => $entity->color,
                    'menu' => $entity->menu,
                    'enable' => $entity->enable,
                    'slug_singular' => $entity->slug_singular,
                    'hidden' => $entity->hidden,
                    'empty_text' => $entity->empty_text,
                ]);
                $tenant_entity = \DB::table('data_types')->where('slug', $entity->slug)->first();
            }

            //синхронизируем поля
            foreach ($data_rows as $field) {
                $tenant_field = \DB::table('data_rows')->where([
                    'data_type_id' => $tenant_entity->id,
                    'field' => $field->field
                ])->first();

                if ($tenant_field) {
                    \DB::table('data_rows')
                        ->where([
                            'data_type_id' => $tenant_entity->id,
                            'field' => $field->field
                        ])
                        ->update([
                            'type' => $field->type,
                            'title' => $field->title,
                            'required' => $field->required,
                            'details' => $field->details,
                            'visible_always' => $field->visible_always,
                            'label_color' => $field->label_color,
                            'section_id' => $field->section_id,
                            'group_id' => $field->group_id,
                            'sort' => $field->sort,
                            'created_at' => $field->created_at,
                            'updated_at' => $field->updated_at,
                            'button_name' => $field->button_name,
                            'show_file_image' => $field->show_file_image,
                            'hide' => $field->hide,
                            'is_plural' => $field->is_plural,
                            'roles_read' => $field->roles_read,
                            'roles_write' => $field->roles_write,
                            'is_remove' => $field->is_remove,
                            'mobile_pages' => $field->mobile_pages,
                            'display_parent_name' => $field->display_parent_name,
                            'rules' => $field->rules,
                            'only_read' => $field->only_read,
                            'is_permanent' => $field->is_permanent,
                            'show_file_name' => $field->show_file_name,
                            'external_link' => $field->external_link,
                            'is_external_link' => $field->is_external_link,
                            'module' => $field->module,
                            'is_link' => $field->is_link,
                            'unit' => $field->unit,
                            'module_section_id' => $field->module_section_id,
                            'is_default' => $field->is_default,
                            'is_inactive' => $field->is_inactive,
                            'blocked_changes' => $field->blocked_changes,
                            'mask' => $field->mask,
                            'permanent_required' => $field->permanent_required,
                            'permanent_name' => $field->permanent_name,
                            'relation_table' => $field->relation_table,
                            'options' => $field->options,
                            'set_color' => $field->set_color,
                            'related_field' => $field->related_field,
                            'is_unique' => $field->is_unique,
                            'is_program' => $field->is_program,
                            'subfields' => $field->subfields,
                            'dependency_fields' => $field->dependency_fields
                        ]);
                } else {
                    // Если записи нет в базе тенанта, добавляем новую
                    \DB::table('data_rows')->insert([
                        'data_type_id' => $tenant_entity->id,
                        'field' => $field->field,
                        'type' => $field->type,
                        'title' => $field->title,
                        'required' => $field->required,
                        'details' => $field->details,
                        'visible_always' => $field->visible_always,
                        'label_color' => $field->label_color,
                        'section_id' => $field->section_id,
                        'group_id' => $field->group_id,
                        'sort' => $field->sort,
                        'created_at' => $field->created_at,
                        'updated_at' => $field->updated_at,
                        'button_name' => $field->button_name,
                        'show_file_image' => $field->show_file_image,
                        'hide' => $field->hide,
                        'is_plural' => $field->is_plural,
                        'roles_read' => $field->roles_read,
                        'roles_write' => $field->roles_write,
                        'is_remove' => $field->is_remove,
                        'mobile_pages' => $field->mobile_pages,
                        'display_parent_name' => $field->display_parent_name,
                        'rules' => $field->rules,
                        'only_read' => $field->only_read,
                        'is_permanent' => $field->is_permanent,
                        'show_file_name' => $field->show_file_name,
                        'external_link' => $field->external_link,
                        'is_external_link' => $field->is_external_link,
                        'module' => $field->module,
                        'is_link' => $field->is_link,
                        'unit' => $field->unit,
                        'module_section_id' => $field->module_section_id,
                        'is_default' => $field->is_default,
                        'is_inactive' => $field->is_inactive,
                        'blocked_changes' => $field->blocked_changes,
                        'mask' => $field->mask,
                        'permanent_required' => $field->permanent_required,
                        'permanent_name' => $field->permanent_name,
                        'relation_table' => $field->relation_table,
                        'options' => $field->options,
                        'set_color' => $field->set_color,
                        'related_field' => $field->related_field,
                        'is_unique' => $field->is_unique,
                        'is_program' => $field->is_program,
                        'subfields' => $field->subfields,
                        'dependency_fields' => $field->dependency_fields
                    ]);
                }
            }
//ALTER TABLE `data_rows` ADD `dependency_fields` TEXT NULL;
            //синхронизируем меню
            $tenant_menu = \DB::table('settings')->where([
                'key' => 'menu',
                'entity' =>$entity->slug,
                'user_id' => null
            ])->first();
            if($tenant_menu) {
                \DB::table('settings')
                    ->where([
                        'key' => 'menu',
                        'entity' =>$entity->slug,
                        'user_id' => null
                    ])
                    ->update([
                        'key' => $menu->key,
                        'display_name' => $menu->display_name,
                        'value' => $menu->value,
                        'type' => $menu->type,
                        'entity' => $menu->entity
                    ]);
            } else {
                info($entity->slug);
                \DB::table('settings')->insert([
                    'key' => $menu->key,
                    'display_name' => $menu->display_name,
                    'value' => $menu->value,
                    'type' => $menu->type,
                    'entity' => $menu->entity
                ]);
            }

            //синхронизируем разделы
            foreach ($field_sections as $field_section) {
                $tenant_field_section = \DB::table('field_sections')->where([
                    'name' => $field_section->name,
                    'page' => $field_section->page
                ])->first();

                if ($tenant_field_section) {
                    \DB::table('data_rows')
                        ->where([
                            'data_type_id' => $tenant_entity->id,
                            'field' => $field->field
                        ])
                        ->update([
                            'type' => $field->type,
                            'title' => $field->title,
                            'required' => $field->required,
                            'details' => $field->details,
                            'visible_always' => $field->visible_always,
                            'label_color' => $field->label_color,
                            'section_id' => $field->section_id,
                            'group_id' => $field->group_id,
                            'sort' => $field->sort,
                            'created_at' => $field->created_at,
                            'updated_at' => $field->updated_at,
                            'button_name' => $field->button_name,
                            'show_file_image' => $field->show_file_image,
                            'hide' => $field->hide,
                            'is_plural' => $field->is_plural,
                            'roles_read' => $field->roles_read,
                            'roles_write' => $field->roles_write,
                            'is_remove' => $field->is_remove,
                            'mobile_pages' => $field->mobile_pages,
                            'display_parent_name' => $field->display_parent_name,
                            'rules' => $field->rules,
                            'only_read' => $field->only_read,
                            'is_permanent' => $field->is_permanent,
                            'show_file_name' => $field->show_file_name,
                            'external_link' => $field->external_link,
                            'is_external_link' => $field->is_external_link,
                            'module' => $field->module,
                            'is_link' => $field->is_link,
                            'unit' => $field->unit,
                            'module_section_id' => $field->module_section_id,
                            'is_default' => $field->is_default,
                            'is_inactive' => $field->is_inactive,
                            'blocked_changes' => $field->blocked_changes,
                            'mask' => $field->mask,
                            'permanent_required' => $field->permanent_required,
                            'permanent_name' => $field->permanent_name,
                            'relation_table' => $field->relation_table,
                            'options' => $field->options,
                            'set_color' => $field->set_color,
                            'related_field' => $field->related_field,
                            'is_unique' => $field->is_unique,
                            'is_program' => $field->is_program,
                            'subfields' => $field->subfields,
                            'dependency_fields' => $field->dependency_fields
                        ]);
                } else {
                    // Если записи нет в базе тенанта, добавляем новую
                    \DB::table('data_rows')->insert([
                        'data_type_id' => $tenant_entity->id,
                        'field' => $field->field,
                        'type' => $field->type,
                        'title' => $field->title,
                        'required' => $field->required,
                        'details' => $field->details,
                        'visible_always' => $field->visible_always,
                        'label_color' => $field->label_color,
                        'section_id' => $field->section_id,
                        'group_id' => $field->group_id,
                        'sort' => $field->sort,
                        'created_at' => $field->created_at,
                        'updated_at' => $field->updated_at,
                        'button_name' => $field->button_name,
                        'show_file_image' => $field->show_file_image,
                        'hide' => $field->hide,
                        'is_plural' => $field->is_plural,
                        'roles_read' => $field->roles_read,
                        'roles_write' => $field->roles_write,
                        'is_remove' => $field->is_remove,
                        'mobile_pages' => $field->mobile_pages,
                        'display_parent_name' => $field->display_parent_name,
                        'rules' => $field->rules,
                        'only_read' => $field->only_read,
                        'is_permanent' => $field->is_permanent,
                        'show_file_name' => $field->show_file_name,
                        'external_link' => $field->external_link,
                        'is_external_link' => $field->is_external_link,
                        'module' => $field->module,
                        'is_link' => $field->is_link,
                        'unit' => $field->unit,
                        'module_section_id' => $field->module_section_id,
                        'is_default' => $field->is_default,
                        'is_inactive' => $field->is_inactive,
                        'blocked_changes' => $field->blocked_changes,
                        'mask' => $field->mask,
                        'permanent_required' => $field->permanent_required,
                        'permanent_name' => $field->permanent_name,
                        'relation_table' => $field->relation_table,
                        'options' => $field->options,
                        'set_color' => $field->set_color,
                        'related_field' => $field->related_field,
                        'is_unique' => $field->is_unique,
                        'is_program' => $field->is_program,
                        'subfields' => $field->subfields,
                        'dependency_fields' => $field->dependency_fields
                    ]);
                }
            }

        }
    }

    public function syncMarks()
    {
        $centralCarModels = tenancy()->central(function () {
            return \DB::table('car_models')->get();  // Получаем все данные из таблицы car_models
        });

        $centralCarMarks = tenancy()->central(function () {
            return \DB::table('car_marks')->get();  // Получаем все данные из таблицы car_marks
        });

        $tenants = \App\Models\Tenant::get();  // Получаем всех тенантов

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);  // Инициализируем подключение к базе данных тенанта
            
            // Синхронизируем таблицу car_models
            foreach ($centralCarModels as $centralCarModel) {
                $tenantCarModel = \DB::table('car_models')->where('id', $centralCarModel->id)->first();

                if ($tenantCarModel) {
                    // Если запись существует в базе тенанта, проверяем, изменились ли данные
                    if ($tenantCarModel != $centralCarModel) {
                        // Если данные изменились, обновляем запись
                        \DB::table('car_models')
                            ->where('id', $centralCarModel->id)
                            ->update([
                                'name' => $centralCarModel->name,  // Пример обновления, нужно учитывать все поля
                                'created_at' => $centralCarModel->updated_at,
                                'updated_at' => $centralCarModel->updated_at,
                                'deleted_at' => $centralCarModel->deleted_at,
                                'name' => $centralCarModel->name,
                                'mark_id' => $centralCarModel->mark_id,
                                'category_id' => $centralCarModel->category_id,
                                'osago_category' => $centralCarModel->osago_category,
                                'osago_code' => $centralCarModel->osago_code,
                                'photo' => $centralCarModel->photo
                            ]);
                    }
                } else {
                    // Если записи нет в базе тенанта, добавляем новую
                    \DB::table('car_models')->insert([
                        'id' => $centralCarModel->id,
                        'name' => $centralCarModel->name,  // Пример обновления, нужно учитывать все поля
                        'created_at' => $centralCarModel->updated_at,
                        'updated_at' => $centralCarModel->updated_at,
                        'deleted_at' => $centralCarModel->deleted_at,
                        'name' => $centralCarModel->name,
                        'mark_id' => $centralCarModel->mark_id,
                        'category_id' => $centralCarModel->category_id,
                        'osago_category' => $centralCarModel->osago_category,
                        'osago_code' => $centralCarModel->osago_code,
                        'photo' => $centralCarModel->photo
                    ]);
                }
            }

            // Синхронизируем таблицу car_marks
            foreach ($centralCarMarks as $centralCarMark) {
                $tenantCarMark = \DB::table('car_marks')->where('id', $centralCarMark->id)->first();

                if ($tenantCarMark) {
                    // Если запись существует в базе тенанта, проверяем, изменились ли данные
                    if ($tenantCarMark != $centralCarMark) {
                        // Если данные изменились, обновляем запись
                        \DB::table('car_marks')
                            ->where('id', $centralCarMark->id)
                            ->update([
                                'name' => $centralCarMark->name,  // Пример обновления
                                'created_at' => $centralCarMark->updated_at,
                                'updated_at' => $centralCarMark->updated_at,
                                'deleted_at' => $centralCarMark->deleted_at,
                                'name' => $centralCarMark->name,
                                'model_id' => $centralCarMark->model_id,
                                'osago_code' => $centralCarMark->osago_code,
                                'photo' => $centralCarModel->photo
                            ]);
                    }
                } else {
                    // Если записи нет в базе тенанта, добавляем новую
                    \DB::table('car_marks')->insert([
                        'id' => $centralCarMark->id,
                        'name' => $centralCarMark->name,  // Пример обновления
                        'created_at' => $centralCarMark->updated_at,
                        'updated_at' => $centralCarMark->updated_at,
                        'deleted_at' => $centralCarMark->deleted_at,
                        'name' => $centralCarMark->name,
                        'model_id' => $centralCarMark->model_id,
                        'osago_code' => $centralCarMark->osago_code,
                        'photo' => $centralCarModel->photo
                    ]);
                }
            }
        }
    }

}