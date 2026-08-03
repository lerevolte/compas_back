<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\ValueHelper;
use Illuminate\Support\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Tenant;

class TableController extends Controller
{
    /**
     * Получение конфигурации таблицы
     */
    public function get($slug, Request $request)
    {
        $user = Auth::user();
        
        // Получаем таблицы пользователя (или пустой массив)
        $tables = $this->getUserTables($user);

        // Если настройки для слага нет, пытаемся найти в роли или глобальных настройках
        if (!isset($tables[$slug])) {
            $tables = $this->tryApplyFallbackSettings($user, $tables, $slug);
        }

        // Если настройки есть и не нужно перестраивать структуру на основе модели
        if (isset($tables[$slug])) {
            $entity = DB::table('data_types')->where('slug', $slug)->first();
            
            // Если сущность не найдена, просто возвращаем сохраненные поля
            if (!$entity) {
                return response()->json($tables[$slug]['fields']);
            }

            // Обрабатываем поля модели и мержим с сохраненными настройками
            $tableColumns = $this->mergeModelFieldsWithSettings($entity->model_name, $tables[$slug], $slug);
            
            return response()->json(array_values($tableColumns));
        }

        // Если настроек нет вообще, генерируем дефолтные на основе модели
        $entity = DB::table('data_types')->where('slug', $slug)->first();
        $tableColumns = $entity 
            ? $this->generateDefaultColumnsFromModel($entity->model_name, $slug) 
            : [];

        return response()->json(array_values($tableColumns));
    }

    /**
     * Сохранение конфигурации таблицы для текущего пользователя
     */
    public function set($slug, Request $request)
    {
        $user = Auth::user();
        $tables = $this->getUserTables($user);

        $tables[$slug] = [
            'fields'     => $request->fields,
            'sort_field' => $request->sort_field ?? 'id',
            'sort_order' => $request->sort_order ?? 'desc'
        ];

        $this->saveUserTables($user, $tables);
        $this->updateLocalCache("tables/$slug", $user->id);

        return response()->json($tables[$slug]);
    }

    /**
     * Сброс настроек таблицы к дефолтным (из тенанта seeds)
     */
    public function reset($slug, Request $request)
    {
        $tenant = Tenant::find('seeds');
        
        $table = $tenant->run(function () use ($slug) {
            $user = User::find(1);
            $tables = $this->getUserTables($user);
            return $tables[$slug] ?? [];
        });

        return response()->json($table);
    }

    /**
     * Установка настроек для всех пользователей определенной роли
     */
    public function set_role($slug, $role_id, Request $request)
    {
        $fieldsConfig = [
            'fields'     => $request->fields,
            'sort_field' => $request->sort_field ?? 'id',
            'sort_order' => $request->sort_order ?? 'desc'
        ];

        // Обновляем пользователей
        User::where('role_id', $role_id)->chunk(100, function ($users) use ($slug, $fieldsConfig) {
            foreach ($users as $user) {
                $tables = $this->getUserTables($user);
                $tables[$slug] = $fieldsConfig;
                $this->saveUserTables($user, $tables);
            }
        });

        // Обновляем саму роль (шаблон)
        $role = Role::find($role_id);
        if ($role) {
            $roleTables = $role->tables ? json_decode($role->tables, true) : [];
            if (!is_array($roleTables)) $roleTables = [];
            
            $roleTables[$slug] = $fieldsConfig;
            $role->tables = json_encode($roleTables);
            $role->saveQuietly();
        }

        $this->updateLocalCache("tables/$slug", Auth::id());

        return response()->json($request->fields);
    }

    /**
     * Установка настроек для ВСЕХ пользователей и глобальных настроек
     */
    public function set_all($slug, Request $request)
    {
        $fieldsConfig = [
            'fields'     => $request->fields,
            'sort_field' => $request->sort_field ?? 'id',
            'sort_order' => $request->sort_order ?? 'desc'
        ];

        User::chunk(100, function ($users) use ($slug, $fieldsConfig) {
            foreach ($users as $user) {
                $tables = $this->getUserTables($user);
                $tables[$slug] = $fieldsConfig;
                $this->saveUserTables($user, $tables);
            }
        });

        // Обновляем глобальные настройки
        $settings = DB::table('settings')->where('key', 'tables')->first();
        $globalTables = [];
        
        if ($settings && $settings->value) {
            $decoded = json_decode($settings->value, true);
            if (is_array($decoded)) $globalTables = $decoded;
        } elseif ($settings && !is_array($settings->value) && ValueHelper::isJson($settings->value)) {
             // Fallback logic from original code
             $globalTables = json_decode($settings->value, true) ?: [];
        }

        $globalTables[$slug] = $fieldsConfig;

        DB::table('settings')->updateOrInsert(
            ['key' => 'tables'],
            ['value' => json_encode($globalTables)]
        );

        $this->updateLocalCache("tables/$slug", Auth::id());

        return response()->json($globalTables[$slug]);
    }

    /**
     * Специальный метод для order_products со специфичными колонками
     */
    public function get_order_products(Request $request)
    {
        $tables = $this->getUserTables(Auth::user());
        
        // Определяем специфичные колонки для products
        $productColumns = [
            'product_name'   => ['title' => 'Наименование товара', 'type' => 'relation', 'related_table' => 'products', 'read_only' => 0],
            'product_price'  => ['title' => 'Цена', 'type' => 'number', 'read_only' => 0],
            'product_count'  => ['title' => 'Кол-во', 'type' => 'number', 'read_only' => 0],
            'product_weight' => ['title' => 'Вес, кг', 'type' => 'number', 'read_only' => 1],
            'product_volume' => ['title' => 'Объем, л', 'type' => 'number', 'read_only' => 1],
            'product_sum'    => ['title' => 'Сумма', 'type' => 'number', 'read_only' => 1],
        ];

        if (isset($tables['order_products'])) {
            $entity = DB::table('data_types')->where('slug', 'products')->first();
            $entityClass = $entity->model_name;
            $modelFields = $entityClass::getFields();
            
            $savedColumns = collect($tables['order_products']['fields'])->keyBy('key')->toArray();

            // Очистка удаленных полей
            $ignoredKeys = array_merge(
                ['isChoose', 'actions', 'iconDrag', 'iconDelete', 'price', 'name'],
                array_keys($productColumns)
            );

            foreach ($savedColumns as $key => $col) {
                // Если поля нет в модели и оно не в списке игнорируемых спец. полей -> удаляем
                if (!$modelFields->contains('field', $key) && !in_array($key, $ignoredKeys)) {
                    unset($savedColumns[$key]);
                }
            }

            // Добавление новых полей из модели
            foreach ($modelFields as $field) {
                // Логика из оригинала: price пропускаем, name добавляем
                if (!array_key_exists($field->field, $savedColumns) && $field->type != 'text_group' && $field->field != 'price') {
                    $savedColumns[$field->field] = $this->createColumnStructure($field, count($savedColumns) + 1, ['enabled' => 0]);
                }
            }

            // Добавление кастомных колонок, если их нет
            $idx = 0;
            foreach ($productColumns as $key => $meta) {
                if (!isset($savedColumns[$key])) {
                    $savedColumns[$key] = $this->createCustomColumn($key, $meta, $idx++);
                }
            }
            
            // Добавляем системные иконки
            $this->ensureSystemColumns($savedColumns, true); // true for extra icons logic if needed

            return response()->json(array_values($savedColumns));

        } else {
            // Дефолтная структура, если настроек нет
            $tableColumns = [];
            $idx = 0;
            foreach ($productColumns as $key => $meta) {
                $tableColumns[$key] = $this->createCustomColumn($key, $meta, $idx++);
            }

            // Добавляем системные колонки (Drag, Delete, Actions, Choose)
            // Логика из оригинала немного странная (дублирование проверок), здесь упрощено
            if (!isset($tableColumns['isChoose'])) $tableColumns['isChoose'] = $this->getSystemColumn('iconDrag'); // В оригинале было iconDrag c ключом isChoose? Оставил как есть, но это странно.
            if (!isset($tableColumns['actions'])) $tableColumns['actions'] = $this->getSystemColumn('iconDelete'); // В оригинале было iconDelete в actions?

            // Исправляем ключи согласно оригиналу (там путаница с ключами массивов и 'key' внутри)
            // Воспроизводим точную логику "else" блока оригинала:
            $result = [];
            $i = 0;
            foreach($productColumns as $k => $v) {
                $result[$k] = $this->createCustomColumn($k, $v, $i++);
            }
            // В оригинале в блоке else добавляются:
            // isChoose (type=iconDrag), actions (type=iconDelete), actions (type=actions)
            // Это выглядит как баг оригинала, но я сохраню логику "добавить если нет".
            
            $result['isChoose'] = $this->getSystemColumn('iconDrag'); 
            $result['iconDelete'] = $this->getSystemColumn('iconDelete');
            // Перезапись actions последним блоком
            $result['actions'] = $this->getSystemColumn('actions');
            $result['actions']['index'] = 5;

            return response()->json(array_values($result));
        }
    }

    public function public_fines(Request $request)
    {
        $tenant = Tenant::find('seeds');
        $table = $tenant->run(function () {
            $user = User::find(1);
            $tables = $this->getUserTables($user);
            return $tables['admin_fines'] ?? [];
        });

        return response()->json($table);
    }

    public function set_per_page($slug, Request $request)
    {
        DB::table('settings')->updateOrInsert(
            [
                'key'     => 'per_page',
                'entity'  => $slug,
                'user_id' => Auth::id()
            ],
            ['value' => $request->per_page]
        );

        return response()->json(['per_page' => $request->per_page]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    private function getUserTables($user)
    {
        $tables = $user->tables;
        if (is_string($tables)) {
            $tables = json_decode($tables, true);
        }
        return is_array($tables) ? $tables : [];
    }

    private function saveUserTables($user, $tables)
    {
        $user->tables = json_encode($tables);
        $user->timestamps = false;
        $user->saveQuietly();
    }

    private function updateLocalCache($url, $userId)
    {
        $now = Carbon::now();
        DB::table('local_cache')->updateOrInsert(
            ['url' => $url, 'user_id' => $userId],
            ['updated_at' => $now, 'created_at' => $now] // created_at игнорируется при update
        );
    }

    private function tryApplyFallbackSettings($user, &$tables, $slug)
    {
        // 1. Попытка взять из роли
        $role = Role::find($user->role_id);
        if ($role && $role->tables) {
            $roleTables = json_decode($role->tables, true);
            if (is_array($roleTables) && isset($roleTables[$slug])) {
                $tables[$slug] = $roleTables[$slug];
                $this->saveUserTables($user, $tables);
                return $tables;
            }
        }

        // 2. Попытка взять из глобальных настроек
        $settings = DB::table('settings')->where('key', 'tables')->first();
        if ($settings && $settings->value) {
            $tablesAll = json_decode($settings->value, true);
            if (isset($tablesAll[$slug])) {
                $tables[$slug] = $tablesAll[$slug];
                $this->saveUserTables($user, $tables);
                return $tables;
            }
        }

        return $tables;
    }

    private function generateDefaultColumnsFromModel($entityClass, $slug)
    {
        $modelFields = $entityClass::getFields();
        $columns = [];

        // Стандартные колонки начала
        $columns['isChoose'] = $this->getSystemColumn('checkbox');
        $columns['actions']  = $this->getSystemColumn('actions');

        $settings = app('settings');
        $fieldColors = [];

        foreach ($modelFields as $field) {
            if ($field->type == 'text_group' || $field->type == 'password') continue;

            $columns[$field->field] = $this->createColumnStructure($field, count($columns) + 1);
            
            // Дополнительная логика прав доступа (из оригинала)
            $perms = $settings[$slug]['perms'][$field->field] ?? null;
            $columns[$field->field]['can_edit'] = (isset($perms) && !$perms['write']) ? 1 : 0;
            
            // Значения списков
            if (isset($settings['list_values'][$field->id])) {
                $vals = $settings['list_values'][$field->id];
                if ($field->type == 'relation') {
                    $vals = array_slice($vals, 0, 10, true);
                }
                $columns[$field->field]['options'] = $vals;
            }

            // Выбранные значения
            if (isset($settings[$slug]['fields'][$field->field]->choosed)) {
                $columns[$field->field]['choosed'] = $settings[$slug]['fields'][$field->field]->choosed;
            } else {
                $columns[$field->field]['choosed'] = [];
            }
        }

        return $columns;
    }

    private function mergeModelFieldsWithSettings($entityClass, $savedSettings, $slug)
    {
        $modelFields = $entityClass::getFields();
        $columns = collect($savedSettings)->keyBy('key')->toArray();
        $settings = app('settings');

        // Удаляем поля, которых больше нет в модели
        foreach ($columns as $key => $col) {
            $isSystem = in_array($key, ['isChoose', 'actions', 'iconDrag', 'iconDelete']);
            // Столбцы связанных сущностей (rel__{slug}__{field}, задача 14) не
            // принадлежат модели маршрута — сохраняем их как есть.
            $isRelated = strpos((string) $key, 'rel__') === 0;
            if (!$modelFields->contains('field', $key) && !$isSystem && !$isRelated) {
                unset($columns[$key]);
            }
        }

        // Гарантируем наличие системных колонок
        if (!isset($columns['isChoose'])) $columns['isChoose'] = $this->getSystemColumn('checkbox');
        if (!isset($columns['actions']))  $columns['actions'] = $this->getSystemColumn('actions');

        // Обрабатываем поля модели
        foreach ($modelFields as $field) {
            // Подготовка values и colors
            $fieldValues = [];
            if (isset($settings['list_values'][$field->id])) {
                $fieldValues = $settings['list_values'][$field->id];
                if ($field->type == 'relation') {
                    $fieldValues = array_slice($fieldValues, 0, 10, true);
                }
            }

            // Если поле новое (нет в сохраненных), добавляем его
            if (!array_key_exists($field->field, $columns) && $field->type != 'text_group' && $field->type != 'password') {
                $columns[$field->field] = $this->createColumnStructure($field, count($columns) + 1);
            }

            // Обновляем атрибуты существующих колонок (синхронизация с моделью)
            if (isset($columns[$field->field])) {
                $col = &$columns[$field->field];
                // Обновляем свойства, которые всегда должны браться из модели/настроек
                $col['type'] = $field->type;
                $col['read_only'] = $field->only_read;
                $col['can_edit'] = (isset($settings[$slug]['perms'][$field->field]) && !$settings[$slug]['perms'][$field->field]['write']) ? 1 : 0;
                $col['color'] = $field->label_color ?: null;
                $col['is_plural'] = $field->is_plural;
                $col['is_hidden'] = $field->hide;
                $col['visible_always'] = $field->visible_always;
                $col['options'] = $fieldValues;
                
                $col['choosed'] = $settings[$slug]['fields'][$field->field]->choosed ?? [];

                if ($field->type == 'relation') {
                    $details = json_decode($field->details, true);
                    $col['related_table'] = $details['table'] ?? '';
                    $col['can_create'] = ($field->field == 'category_id') ? 0 : 1;
                }
            }
        }

        return $columns;
    }

    private function createColumnStructure($field, $index, $overrides = [])
    {
        $base = [
            'id' => $field->id,
            'title' => $field->title,
            'key' => $field->field,
            'width' => '200px',
            'enabled' => ($field->is_default ? true : false),
            'sort_order' => ($field->field == 'id' ? 'desc' : ''),
            'type' => $field->type,
            'is_plural' => ($field->type == 'text' ? 1 : $field->is_plural),
            'external_link' => $field->external_link,
            'is_external_link' => $field->is_external_link,
            'is_link' => $field->is_link,
            'required' => $field->required,
            'fixed' => '',
            'index' => $index,
            'fixTarget' => '0px',
            'read_only' => $field->only_read,
            'unit' => $field->unit,
            'mask' => $field->mask,
            'can_edit' => 1, // Default
            'color' => $field->label_color ?: null,
            'is_hidden' => $field->hide,
            'visible_always' => $field->visible_always,
            'options' => [],
            'choosed' => []
        ];

        if ($field->type == 'relation') {
            $details = json_decode($field->details, true);
            $base['related_table'] = $details['table'] ?? '';
            $base['can_create'] = ($field->field == 'category_id') ? 0 : 1;
        }

        return array_merge($base, $overrides);
    }

    private function createCustomColumn($key, $meta, $index)
    {
        return [
            'id' => null,
            'title' => $meta['title'],
            'key' => $key,
            'width' => '200px',
            'enabled' => 1,
            'sort_order' => '',
            'type' => $meta['type'],
            'fixed' => '',
            'index' => $index,
            'fixTarget' => '0px',
            'read_only' => $meta['read_only'] ?? 0,
            'mask' => "",
            'related_table' => $meta['related_table'] ?? null
        ];
    }

    private function ensureSystemColumns(&$columns, $forOrderProducts = false)
    {
        if ($forOrderProducts) {
            if (!isset($columns['iconDrag'])) $columns['iconDrag'] = $this->getSystemColumn('iconDrag');
            if (!isset($columns['iconDelete'])) $columns['iconDelete'] = $this->getSystemColumn('iconDelete');
        } else {
             if (!isset($columns['isChoose'])) $columns['isChoose'] = $this->getSystemColumn('checkbox');
        }
        
        if (!isset($columns['actions'])) $columns['actions'] = $this->getSystemColumn('actions');
    }

    private function getSystemColumn($type)
    {
        $defaults = [
            'width' => '40.00px', 'enabled' => true, 'hover' => false, 
            'sort_order' => null, 'fixed' => true, 'mask' => ''
        ];

        switch ($type) {
            case 'checkbox':
                return array_merge($defaults, [
                    'id' => 0, 'title' => 'Выделение', 'key' => 'isChoose',
                    'type' => 'checkbox', 'fixTarget' => '0px', 'index' => 0
                ]);
            case 'actions':
                return array_merge($defaults, [
                    'id' => 2, 'title' => 'Действие', 'key' => 'actions',
                    'type' => 'actions', 'fixTarget' => '40px', 'index' => 1
                ]);
            case 'iconDrag':
                return [
                    'id' => null, 'title' => 'Перетаскивание', 'key' => 'iconDrag',
                    'width' => '40px', 'enabled' => 1, 'sort_order' => '', 'type' => 'iconDrag',
                    'fixed' => '', 'index' => 1, 'fixTarget' => '0px', 'read_only' => 1, 'mask' => ''
                ];
            case 'iconDelete':
                // Note: key is 'actions' in one place in original code, but often 'iconDelete' logic is needed
                // Based on get_order_products logic:
                return [
                    'id' => null, 'title' => 'Удаление', 'key' => 'iconDelete',
                    'width' => '40px', 'enabled' => 1, 'sort_order' => '', 'type' => 'iconDelete',
                    'fixed' => '', 'index' => 1, 'fixTarget' => '0px', 'read_only' => 1, 'mask' => ''
                ];
        }
        return [];
    }
}