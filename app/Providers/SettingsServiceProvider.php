<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Field;
use App\Helpers\ValueHelper;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

class SettingsServiceProvider extends ServiceProvider
{
    // const CACHE_SECTIONS = [
    //     'core',
    //     'modules',
    //     'models_meta',
    //     'field_data',
    //     'model_settings',
    //     'entities',
    //     'app_settings',
    //     'list_values'
    // ];
    const CACHE_SECTIONS = [
        'main',       // Основные настройки (70% данных)
        'models',     // Данные моделей
        'fields',     // Данные полей
        'relations'   // Отношения и списки
    ];

    // public function register()
    // {
    //     $this->app->singleton('user_settings', function($app) {
    //         $user = \Auth::user() ?: User::find(1);
    //         $cacheKey = tenant('id').':user_settings:'.($user ? $user->id : 1);
            
    //         $settings = $this->getCachedSettings($cacheKey);
            
    //         if (empty($settings)) {
    //             $settings = $this->buildSettings($user);
    //             $this->cacheSettings($cacheKey, $settings);
    //         }
            
    //         return $settings;
    //     });
    // }
    // public function register()
    // {
    //     $this->app->singleton('user_settings', function($app) {
    //         $user = \Auth::user() ?: User::find(1);
    //         $cacheKey = tenant('id').':settings:'.($user ? $user->id : 1);
            
    //         // Пытаемся получить все секции за один запрос
    //         $cached = cache()->getMemcached()->getMulti(
    //             array_map(fn($s) => "{$cacheKey}:{$s}", self::CACHE_SECTIONS)
    //         );
            
    //         if (count($cached) === count(self::CACHE_SECTIONS)) {
    //             $settings = [];
    //             foreach ($cached as $key => $value) {
    //                 $section = explode(':', $key)[2];
    //                 $settings[$section] = unserialize(gzuncompress($value));
    //             }
    //             return $this->mergeSections($settings);
    //         }
            
    //         // Полная загрузка если кэш неполный
    //         $settings = $this->buildSettings($user);
    //         $this->cacheSettings($cacheKey, $settings);
    //         return $settings;
    //     });
    // }

    // protected function getCachedSettings(string $cacheKey): array
    // {
    //     $settings = [];
    //     $memcached = cache()->getMemcached();
        
    //     foreach (self::CACHE_SECTIONS as $section) {
    //         $cached = $memcached->get("{$cacheKey}:{$section}");
    //         if ($cached) {
    //             $settings[$section] = unserialize(gzuncompress($cached));
    //         } else {
    //             return [];
    //         }
    //     }
        
    //     // Восстанавливаем полную структуру из секций
    //     return $this->mergeSections($settings);
    // }

    // protected function cacheSettings(string $cacheKey, array $settings): void
    // {
    //     $memcached = cache()->getMemcached();
    //     $ttl = 60 * 60; // 1 час
        
    //     // Разбиваем на секции перед кэшированием
    //     $sections = $this->splitIntoSections($settings);
        
    //     foreach ($sections as $section => $data) {
    //         $memcached->set(
    //             "{$cacheKey}:{$section}",
    //             gzcompress(serialize($data)),
    //             $ttl
    //         );
    //     }
    // }

    // protected function buildSettings(User $user): array
    // {
    //     $settings = [
    //         'is_admin' => $user->isAdmin(),
    //         'field_values' => [],
    //         'models' => [],
    //         'modules' => [],
    //         'entities' => ['perms' => []],
    //         'settings' => ['sidebar_items' => []],
    //         'list_values' => []
    //     ];

    //     // Загрузка модулей
    //     $settings['modules'] = DB::table('modules')
    //         ->select('id', 'name', 'slug', 'enabled')
    //         ->get()
    //         ->keyBy('slug')
    //         ->toArray();

    //     $modulesConfig = Module::all();

    //     // Загрузка моделей
    //     $dataTypes = DB::table('data_types')->get();
    //     $settings['models'] = $dataTypes->keyBy('name')->toArray();
    //     $models = $dataTypes->pluck('name', 'id')->toArray();

    //     // Загрузка полей
    //     $allFields = DB::table('data_rows')->where(['is_remove' => 0])->orderBy('id')->get();
    //     $allFieldsModels = Field::get()->keyBy('id');
    //     $groups = $allFields->groupBy('data_type_id')->toArray();

    //     // Загрузка статусов
    //     $statuses = DB::table('field_values')
    //         ->orderBy('is_hidden', 'asc')
    //         ->orderBy('sort')
    //         ->get()
    //         ->groupBy('field_id')
    //         ->toArray();

    //     // Обработка прав доступа
    //     $userRoles = [$user->role_id];
    //     $permissions = [];
    //     $fieldValues = [];

    //     foreach ($allFields as $field) {
    //         $dataTypeId = $field->data_type_id;
            
    //         if (!isset($permissions[$dataTypeId])) {
    //             $permissions[$dataTypeId] = ['read' => [], 'write' => []];
    //         }
            
    //         $permissions[$dataTypeId]['read'][$field->field] = $this->checkFieldAccess($field->roles_read, $userRoles);
    //         $permissions[$dataTypeId]['write'][$field->field] = $this->checkFieldAccess($field->roles_write, $userRoles);
    //     }

    //     // Обработка специальных полей
    //     $specialFields = DB::table('data_rows')
    //         ->where('type', 'relation')
    //         ->orWhere('type', 'select_dropdown')
    //         ->get();

    //     foreach ($specialFields as $field) {
    //         if ($field->details && $details = json_decode($field->details, true)) {
    //             $fieldValues[$field->id] = $this->processFieldDetails($field, $details, $settings['models']);
    //         }
    //     }
    //     $settings['field_values'] = $fieldValues;

    //     // Обработка групп полей для каждой модели
    //     foreach ($groups as $modelId => $group) {
    //         if (!isset($models[$modelId])) continue;
            
    //         $modelName = $models[$modelId];
    //         $settings[$modelName] = [
    //             'perms' => [],
    //             'colors' => [],
    //             'options' => [],
    //             'fields' => [],
    //             'field_data' => [],
    //             'buttons' => [],
    //             'used_in_modules' => []
    //         ];

    //         // Обработка изменений из модулей
    //         foreach ($modulesConfig as $module) {
    //             $changesEntity = $module->get('changes_entities');
    //             if ($changesEntity) {
    //                 foreach ($changesEntity as $change) {
    //                     if (isset($change['buttons']) && $change['entity'] == $modelName) {
    //                         $settings[$modelName]['buttons'] = array_merge(
    //                             $settings[$modelName]['buttons'],
    //                             $change['buttons']
    //                         );
    //                     }
    //                 }
    //             }
    //         }

    //         // Обработка полей группы
    //         foreach ($group as $field) {
    //             $this->processModelField($field, $modelName, $modelId, $settings, $statuses, $fieldValues, $permissions, $allFieldsModels);
    //         }
    //     }

    //     // Дополнительные настройки
    //     $settings['settings']['sidebar_items'] = \App\Models\SidebarItem::defaultOrder()
    //         ->where('enabled', 1)
    //         ->get()
    //         ->toTree()
    //         ->toArray();

    //     $settings['list_values'] = $this->prepareListValues($fieldValues);

    //     return $settings;
    // }

    public function register()
    {
        // $this->app->singleton('user_settings', function($app) {
        //     $user = \Auth::user() ?: User::find(1);
        //     $cacheKey = tenant('id').':settings:'.($user ? $user->id : 1);
            
        //     // Быстрая проверка "горячего" кэша
        //     if ($cached = $app['cache']->get($cacheKey)) {
        //         return $cached;
        //     }
            
        //     // Параллельная загрузка
        //     $settings = $this->buildSettings($user);
            
        //     // Сохраняем без блокировки основного потока
        //     $app['cache']->put($cacheKey, $settings, now()->addHours(12));
            
        //     return $settings;
        // });
    }

    protected function getCachedSections(string $cacheKey): array
    {
        $keys = array_map(fn($s) => "{$cacheKey}:{$s}", self::CACHE_SECTIONS);
        $result = [];
        
        try {
            $cached = cache()->getMemcached()->getMulti($keys);
            foreach ($cached as $key => $value) {
                $section = explode(':', $key)[2];
                $result[$section] = unserialize(gzuncompress($value));
            }
        } catch (\Exception $e) {
            logger()->error('Memcached multi-get failed', ['error' => $e->getMessage()]);
        }
        
        return $result;
    }

    protected function buildAndCacheSettings(User $user, string $cacheKey): array
    {
        $settings = $this->buildSettings($user);
        
        // Постепенное кэширование тяжелых частей
        $this->cacheInBackground($cacheKey, [
            'main' => [
                'is_admin' => $settings['is_admin'],
                'modules' => $settings['modules'],
                'entities' => $settings['entities'],
                'settings' => $settings['settings']
            ],
            'models' => $settings['models']
        ]);
        
        $this->cacheInBackground($cacheKey, [
            'fields' => $this->extractFieldsData($settings),
            'relations' => [
                'field_values' => $settings['field_values'],
                'list_values' => $settings['list_values']
            ]
        ]);
        
        return $settings;
    }

    protected function cacheInBackground(string $cacheKey, array $sections): void
    {
        dispatch(function() use ($cacheKey, $sections) {
            try {
                $memcached = cache()->getMemcached();
                foreach ($sections as $section => $data) {
                    $memcached->set(
                        "{$cacheKey}:{$section}",
                        gzcompress(serialize($data)),
                        3600
                    );
                }
            } catch (\Exception $e) {
                logger()->error('Background cache failed', ['error' => $e->getMessage()]);
            }
        });
    }

    protected function cacheSettings($cacheKey, $settings): void
    {
        $sections = [
            'main' => [
                'is_admin' => $settings['is_admin'],
                'modules' => $settings['modules'],
                'entities' => $settings['entities'],
                'settings' => $settings['settings']
            ],
            'models' => $settings['models'],
            'fields' => $this->extractFieldsData($settings),
            'relations' => [
                'field_values' => $settings['field_values'],
                'list_values' => $settings['list_values']
            ]
        ];
        
        cache()->getMemcached()->setMulti(
            array_map(
                fn($s, $d) => ["{$cacheKey}:{$s}" => gzcompress(serialize($d))],
                array_keys($sections),
                $sections
            ),
            3600
        );
    }
    protected function buildSettings(User $user): array
    {
        $start = microtime(true);
        
        // Параллельная загрузка основных данных
        $modules = DB::table('modules')
            ->select('id', 'name', 'slug', 'enabled')
            ->get()->keyBy('slug');
        
        $dataTypes = DB::table('data_types')->get();
        $allFields = DB::table('data_rows')->where('is_remove', 0)->orderBy('id')->get();
        
        // Основная структура
        $settings = [
            'is_admin' => $user->isAdmin(),
            'field_values' => [],
            'models' => $dataTypes->keyBy('name')->toArray(),
            'modules' => $modules->toArray(),
            'entities' => ['perms' => []],
            'settings' => ['sidebar_items' => \App\Models\SidebarItem::defaultOrder()->where('enabled', 1)->get()->toTree()->toArray()],
            'list_values' => []
        ];

        // Оптимизированная обработка прав
        $permissions = $this->loadPermissions($allFields, $user);
        
        // Загрузка отношений (самая тяжелая часть)
        $settings['field_values'] = $this->loadFieldValues(
            $allFields->whereIn('type', ['relation', 'select_dropdown']),
            $settings['models']
        );
        
        // Группировка полей по моделям
        $this->processModelFields($allFields->groupBy('data_type_id'), $settings, $permissions);
        
        logger('Settings build time: '. (microtime(true) - $start));
        return $settings;
    }

    protected function loadPermissions($fields, $user): array
    {
        $permissions = [];
        $userRoles = [$user->role_id];
        
        foreach ($fields as $field) {
            $typeId = $field->data_type_id;
            $permissions[$typeId]['read'][$field->field] = $this->checkFieldAccess($field->roles_read, $userRoles);
            $permissions[$typeId]['write'][$field->field] = $this->checkFieldAccess($field->roles_write, $userRoles);
        }
        
        return $permissions;
    }

    protected function loadFieldValues($fields, $models): array
    {
        $values = [];
        $tables = [];
        
        foreach ($fields as $field) {
            if (!$details = json_decode($field->details, true)) continue;
            
            if (isset($details['table'])) {
                $table = $details['table'];
                if (!isset($tables[$table])) {
                    $tables[$table] = $this->loadTableData($table, $models);
                }
                foreach ($tables[$table] as $i => $item) {
                    $values[$field->id][$item->id] = $this->formatItem($field, $item, $i);
                }
            } elseif (isset($details['options'])) {
                $values[$field->id] = $this->processOptions($details['options']);
            }
        }
        
        return $values;
    }

    protected function loadTableData(string $table, array $models)
    {
        // Оптимизация для часто используемых таблиц
        static $cache = [];
        if (isset($cache[$table])) {
            return $cache[$table];
        }

        // Та же защита, что и в getItemsForTable: в центральном контексте нет
        // тенантских таблиц — не валим запрос, отдаём пустой набор.
        if (!\Schema::hasTable($table)) {
            return $cache[$table] = collect();
        }

        if (isset($models[$table])) {
            $model = app($models[$table]->model_name);
            $query = $model->newQuery()
                ->orderBy('choosed_at', 'DESC')
                ->orderBy('name', 'ASC')
                ->whereNull('deleted_at');
            
            // Оптимизация для тяжелых таблиц
            // if (in_array($table, ['cars', 'employees', 'clients'])) {
            //     $query->select(['id', 'name', 'color', 'avatar', 'photo', 'icon', 'title', 'first_name', 'last_name', 'display_name']);
            // }
            
            $cache[$table] = $query->get();
        } else {
            $cache[$table] = DB::table($table)
                ->orderBy('choosed_at', 'DESC')
                ->orderBy('name', 'ASC')
                ->whereNull('deleted_at')
                ->get();
        }

        return $cache[$table];
    }

    protected function formatItem($field, $item, int $index): array
    {
        // Быстрое получение аватара
        $avatar = $item->avatar ?? $item->photo ?? $item->icon ?? '';
        if ($avatar && is_string($avatar)) {
            $avatar = json_decode($avatar, true)[0]['url'] ?? '';
        }

        // Оптимизированное получение имени
        $text = $this->getItemText($item);

        return [
            'label' => [
                'id' => $item->id,
                'sort' => $index,
                'file' => $avatar,
                'is_hidden' => 0,
                'field_id' => $field->id,
                'color' => $item->color ?? '',
                'text' => $text
            ],
            'value' => $item->id
        ];
    }

    protected function getItemText($item): string
    {
        // Проверяем все возможные варианты имен с приведением к строке
        if (isset($item->title)) {
            return (string)$item->title;
        }
        
        if (isset($item->name)) {
            $name = ValueHelper::isJson($item->name) 
                ? (json_decode($item->name, true)['value']) ?? '' 
                : (string)$item->name;
                
            if (isset($item->last_name)) {
                $lastName = ValueHelper::isJson($item->last_name)
                    ? (json_decode($item->last_name, true)['value']) ?? ''
                    : (string)$item->last_name;
                return trim("$name $lastName");
            }
            return $name;
        }
        
        if (isset($item->first_name)) {
            $firstName = (string)$item->first_name;
            $lastName = isset($item->last_name) ? (string)$item->last_name : '';
            return trim("$firstName $lastName");
        }
        
        if (isset($item->display_name)) {
            if (ValueHelper::isJson($item->display_name)) {
                return (string)(json_decode($item->display_name, true)['value']) ?? '';
            }
            return (string)$item->display_name;
        }
        
        // Гарантированный возврат строки
        return isset($item->id) ? (string)$item->id : '';
    }

    protected function processOptions(array $options): array
    {
        $result = [];
        foreach ($options as $k => $option) {
            $key = is_array($option) && isset($option['value']) ? $option['value'] : $k;
            $result[$key] = $option;
        }
        return $result;
    }

    protected function splitIntoSections(array $settings): array
    {
        return [
            'core' => [
                'is_admin' => $settings['is_admin'],
                'field_values' => $settings['field_values']
            ],
            'modules' => $settings['modules'],
            'models_meta' => $settings['models'],
            'field_data' => $this->extractFieldData($settings),
            'model_settings' => $this->extractModelSettings($settings),
            'entities' => $settings['entities'],
            'app_settings' => $settings['settings'],
            'list_values' => $settings['list_values']
        ];
    }

    protected function mergeSections(array $sections): array
    {
        return [
            'is_admin' => $sections['main']['is_admin'],
            'field_values' => $sections['relations']['field_values'],
            'models' => $sections['models'],
            'modules' => $sections['main']['modules'],
            'entities' => $sections['main']['entities'],
            'settings' => $sections['main']['settings'],
            'list_values' => $sections['relations']['list_values'],
        ] + $this->restoreModelFields($sections['fields']);
    }


    protected function extractFieldsData(array $settings): array
    {
        $fieldsData = [];
        foreach ($settings['models'] as $modelName => $model) {
            if (isset($settings[$modelName]['fields'])) {
                $fieldsData[$modelName] = [
                    'fields' => $settings[$modelName]['fields'],
                    'field_data' => $settings[$modelName]['field_data'] ?? []
                ];
            }
        }
        return $fieldsData;
    }

    protected function extractModelSettings(array $settings): array
    {
        $modelSettings = [];
        foreach ($settings['models'] as $modelName => $model) {
            if (isset($settings[$modelName])) {
                $modelSettings[$modelName] = [
                    'perms' => $settings[$modelName]['perms'] ?? [],
                    'colors' => $settings[$modelName]['colors'] ?? [],
                    'options' => $settings[$modelName]['options'] ?? [],
                    'field_data' => $settings[$modelName]['field_data'] ?? [],
                    'buttons' => $settings[$modelName]['buttons'] ?? [],
                    'used_in_modules' => $settings[$modelName]['used_in_modules'] ?? []
                ];
            }
        }
        return $modelSettings;
    }

    protected function restoreModelFields(array $fieldsData): array
    {
        $result = [];
        foreach ($fieldsData as $modelName => $data) {
            $result[$modelName] = [
                'fields' => $data['fields'],
                'field_data' => $data['field_data']
            ];
        }
        return $result;
    }


    public static function flushCache(?int $userId = null, ?string $section = null): void
    {
        $user = $userId ? User::find($userId) : \Auth::user();
        $cacheKey = tenant('id').':user_settings:'.($user ? $user->id : 1);
        $memcached = cache()->getMemcached();
        
        if ($section) {
            $memcached->delete("{$cacheKey}:{$section}");
        } else {
            foreach (self::CACHE_SECTIONS as $section) {
                $memcached->delete("{$cacheKey}:{$section}");
            }
        }
        
        app()->forgetInstance('user_settings');
    }

    protected function processFieldDetails($field, array $details, array $modelsByName): array
    {
        $values = [];
        
        if (isset($details['table'])) {
            $tableName = $details['table'];
            $items = $this->getItemsForTable($tableName, $modelsByName);
            
            foreach ($items as $i => $item) {
                $values[$item->id] = $this->formatFieldValue($field, $item, $i);
            }
        } elseif (isset($details['options'])) {
            foreach ($details['options'] as $k => $option) {
                $values[is_array($option) && isset($option['value']) ? $option['value'] : $k] = $option;
            }
        }
        
        return $values;
    }

    protected function getItemsForTable(string $tableName, array $modelsByName)
    {
        // В центральном контексте (admin.compas.pro, БД admin_compas_main) нет
        // тенантских таблиц вроде `routes`. Если relation-поле ссылается на
        // отсутствующую таблицу — отдаём пустой набор, а не валим весь запрос
        // ошибкой "Base table or view not found".
        if (!\Schema::hasTable($tableName)) {
            return collect();
        }

        if (isset($modelsByName[$tableName])) {
            $model = $modelsByName[$tableName]->model_name;
            return $model::orderBy('choosed_at', 'DESC')
                ->orderBy('name', 'ASC')
                ->whereNull('deleted_at')
                ->get();
        }

        return DB::table($tableName)
            ->orderBy('choosed_at', 'DESC')
            ->orderBy('name', 'ASC')
            ->whereNull('deleted_at')
            ->get();
    }

    protected function processModelField(
        $field, 
        string $modelName, 
        $modelId, 
        array &$settings, 
        array $statuses,
        array $fieldValues,
        array $permissions,
        $allFieldsModels
    ): void {
        // Обработка статусов
        if ($field->type == 'status') {
            $settings[$modelName]['options'][$field->field] = [];
            
            if (isset($statuses[$field->id])) {
                foreach ($statuses[$field->id] as $status) {
                    $settings[$modelName]['options'][$field->field][] = [
                        'label' => [
                            'id' => $status->id,
                            'sort' => $status->sort,
                            'file' => $status->file,
                            'is_hidden' => $status->is_hidden,
                            'field_id' => $status->field_id,
                            'color' => $status->color,
                            'text' => $status->value
                        ],
                        'value' => $status->id
                    ];
                }
            }
            $settings['list_values'][$field->id] = $settings[$modelName]['options'][$field->field];
        }
        
        // Обработка выпадающих списков и отношений
        if ($field->type == 'select_dropdown' || $field->type == 'relation') {
            $settings['list_values'][$field->id] = $fieldValues[$field->id] ?? null;
        }
        
        // Основные свойства поля
        $settings[$modelName]['colors'][$field->field] = $field->label_color ?? '';
        $settings[$modelName]['perms'][$field->field] = [
            'read' => $permissions[$modelId]['read'][$field->field] ?? 0,
            'write' => $permissions[$modelId]['write'][$field->field] ?? 0,
        ];
        
        // Модули, использующие поле
        $settings[$modelName]['used_in_modules'][$field->field] = [];
        if ($field->module) {
            $usedInModules = json_decode($field->module, true);
            foreach ($usedInModules as $moduleSlug) {
                if (isset($settings['modules'][$moduleSlug])) {
                    $settings[$modelName]['used_in_modules'][$field->field][] = $settings['modules'][$moduleSlug];
                }
            }
        }
        
        // Данные поля
        if (isset($settings[$modelName]['perms'][$field->field]['read']) && 
            $settings[$modelName]['perms'][$field->field]['read'] != 'disabled') {
            
            if (\Schema::hasColumn($modelName, $field->field)) {
                $settings[$modelName]['fields'][$field->field] = $field;
                $settings[$modelName]['field_data'][$field->field] = 
                    $allFieldsModels[$field->id]->getData();
            }

            // Обработка отношений
            if ($field->type == 'relation' && $field->details) {
                $details = json_decode($field->details, true);
                if ($details && isset($details['unique'])) {
                    $choosed = DB::table($details['table'])
                        ->whereNotNull($details['relation_field'])
                        ->whereNotNull('choosed_at')
                        ->pluck('id')
                        ->toArray();
                        
                    if (isset($settings[$modelName]['fields'][$field->field])) {
                        $settings[$modelName]['fields'][$field->field]->choosed = $choosed;
                    }
                }
            }
        }
    }

    protected function formatFieldValue($field, $item, int $index): array
    {
        $avatar = $item->avatar ?? $item->photo ?? $item->icon ?? '';
        if (is_string($avatar) && ValueHelper::isJson($avatar)) {
            $decoded = json_decode($avatar, true);
            $avatar = is_array($decoded) ? ($decoded[0]['url'] ?? '') : '';
        }
        
        return [
            'label' => [
                'id' => $item->id ?? 0,
                'sort' => $index,
                'file' => (string)$avatar,
                'is_hidden' => 0,
                'field_id' => $field->id ?? 0,
                'color' => $this->getItemColor($item),
                'text' => $this->getItemDisplayName($item)
            ],
            'value' => $item->id ?? 0
        ];
    }

    protected function getItemColor($item): string
    {
        if (isset($item->color)) {
            return (string)$item->color;
        }
        
        $objArr = is_object($item) ? $item->toArray() : [];
        return (string)($objArr['color'] ?? '');
    }

    protected function getItemDisplayName($item): string
    {
        if (isset($item->title)) {
            return (string)$item->title;
        }
        if (isset($item->first_name)) {
            return trim((string)$item->first_name . ' ' . ($item->last_name ?? ''));
        }
        if (isset($item->display_name)) {
            return ValueHelper::isJson($item->display_name) 
                ? (string)(json_decode($item->display_name, true)['value'] ?? '')
                : (string)$item->display_name;
        }
        if (isset($item->name)) {
            $name = ValueHelper::isJson($item->name) 
                ? (string)(json_decode($item->name, true)['value'] ?? '')
                : (string)$item->name;
                
            if (isset($item->last_name)) {
                $lastName = ValueHelper::isJson($item->last_name) 
                    ? (string)(json_decode($item->last_name, true)['value'] ?? '')
                    : (string)$item->last_name;
                $name .= $lastName ? ' ' . $lastName : '';
            }
            
            return $name;
        }
        
        return (string)($item->id ?? '');
    }

    protected function prepareListValues(array $fieldValues): array
    {
        $listValues = [];
        
        foreach ($fieldValues as $fieldId => $values) {
            $listValues[$fieldId] = $values;
        }
        
        return $listValues;
    }

    protected function checkFieldAccess(?string $rolesJson, array $userRoles): int
    {
        if (!$rolesJson) return 1;
        
        $roles = json_decode($rolesJson);
        return $roles && count(array_intersect($roles, $userRoles)) ? 1 : 0;
    }
    protected function processModelFields(\Illuminate\Support\Collection $groups, array &$settings, array $permissions): void
    {
        $models = DB::table('data_types')->pluck('name', 'id')->toArray();
        $statuses = DB::table('field_values')
            ->orderBy('is_hidden', 'asc')
            ->orderBy('sort')
            ->get()
            ->groupBy('field_id')
            ->toArray();

        foreach ($groups as $modelId => $groupFields) {
            if (!isset($models[$modelId])) {
                continue;
            }

            $modelName = $models[$modelId];
            $settings[$modelName] = [
                'perms' => [],
                'colors' => [],
                'options' => [],
                'fields' => [],
                'field_data' => [],
                'buttons' => [],
                'used_in_modules' => []
            ];

            // Обработка кнопок из модулей
            $this->processModuleButtons($modelName, $settings);

            foreach ($groupFields as $field) {
                $this->processSingleField(
                    $field,
                    $modelName,
                    $modelId,
                    $settings,
                    $statuses,
                    $permissions
                );
            }
        }
    }

    protected function processModuleButtons(string $modelName, array &$settings): void
    {
        foreach (Module::all() as $module) {
            $changesEntity = $module->get('changes_entities');
            if (!$changesEntity) {
                continue;
            }

            foreach ($changesEntity as $change) {
                if (isset($change['buttons']) && $change['entity'] == $modelName) {
                    $settings[$modelName]['buttons'] = array_merge(
                        $settings[$modelName]['buttons'] ?? [],
                        $change['buttons']
                    );
                }
            }
        }
    }

    protected function processSingleField(
        $field,
        string $modelName,
        int $modelId,
        array &$settings,
        array $statuses,
        array $permissions
    ): void {
        // Обработка статусов
        if ($field->type === 'status') {
            $this->processStatusField($field, $modelName, $settings, $statuses);
        }

        // Обработка отношений и выпадающих списков
        if (in_array($field->type, ['relation', 'select_dropdown'])) {
            $settings['list_values'][$field->id] = $settings['field_values'][$field->id] ?? null;
        }

        // Основные свойства поля
        $settings[$modelName]['colors'][$field->field] = $field->label_color ?? '';
        $settings[$modelName]['perms'][$field->field] = [
            'read' => $permissions[$modelId]['read'][$field->field] ?? 0,
            'write' => $permissions[$modelId]['write'][$field->field] ?? 0,
        ];

        // Модули, использующие поле
        $this->processFieldModules($field, $modelName, $settings);

        // Данные поля
        if (($settings[$modelName]['perms'][$field->field]['read'] ?? 1) != 'disabled') {
            $this->processFieldData($field, $modelName, $settings);
        }
    }

    protected function processStatusField($field, string $modelName, array &$settings, array $statuses): void
    {
        $settings[$modelName]['options'][$field->field] = [];
        
        if (isset($statuses[$field->id])) {
            foreach ($statuses[$field->id] as $status) {
                $settings[$modelName]['options'][$field->field][] = [
                    'label' => [
                        'id' => $status->id,
                        'sort' => $status->sort,
                        'file' => $status->file,
                        'is_hidden' => $status->is_hidden,
                        'field_id' => $status->field_id,
                        'color' => $status->color,
                        'text' => $status->value
                    ],
                    'value' => $status->id
                ];
            }
        }
        
        $settings['list_values'][$field->id] = $settings[$modelName]['options'][$field->field];
    }

    protected function processFieldModules($field, string $modelName, array &$settings): void
    {
        $settings[$modelName]['used_in_modules'][$field->field] = [];
        
        if ($field->module) {
            $usedInModules = json_decode($field->module, true) ?: [];
            foreach ($usedInModules as $moduleSlug) {
                if (isset($settings['modules'][$moduleSlug])) {
                    $settings[$modelName]['used_in_modules'][$field->field][] = $settings['modules'][$moduleSlug];
                }
            }
        }
    }

    protected function processFieldData($field, string $modelName, array &$settings): void
    {
        if (!\Schema::hasColumn($modelName, $field->field)) {
            return;
        }

        $fieldModel = Field::find($field->id);
        if (!$fieldModel) {
            return;
        }

        $settings[$modelName]['fields'][$field->field] = $field;
        $settings[$modelName]['field_data'][$field->field] = $fieldModel->getData();

        // Обработка отношений
        if ($field->type === 'relation' && $field->details) {
            $this->processRelationField($field, $modelName, $settings);
        }
    }

    protected function processRelationField($field, string $modelName, array &$settings): void
    {
        $details = json_decode($field->details, true);
        if (!$details || !isset($details['unique'])) {
            return;
        }

        $choosed = DB::table($details['table'])
            ->whereNotNull($details['relation_field'])
            ->whereNotNull('choosed_at')
            ->pluck('id')
            ->toArray();

        if (isset($settings[$modelName]['fields'][$field->field])) {
            $settings[$modelName]['fields'][$field->field]->choosed = $choosed;
        }
    }

    public function provides()
    {
        return ['user_settings'];
    }
}
