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

    public function get($slug, Request $request)
    {
        $user = Auth::user();

        $tables = $this->getUserTables($user);

        if (!isset($tables[$slug])) {
            $tables = $this->tryApplyFallbackSettings($user, $tables, $slug);
        }

        if (isset($tables[$slug])) {
            $entity = DB::table('data_types')->where('slug', $slug)->first();

            if (!$entity) {
                return response()->json($tables[$slug]['fields']);
            }

            $tableColumns = $this->mergeModelFieldsWithSettings($entity->model_name, $tables[$slug], $slug);

            return response()->json(array_values($tableColumns));
        }

        $entity = DB::table('data_types')->where('slug', $slug)->first();
        $tableColumns = $entity
            ? $this->generateDefaultColumnsFromModel($entity->model_name, $slug)
            : [];

        return response()->json(array_values($tableColumns));
    }

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

    public function set_role($slug, $role_id, Request $request)
    {
        $fieldsConfig = [
            'fields'     => $request->fields,
            'sort_field' => $request->sort_field ?? 'id',
            'sort_order' => $request->sort_order ?? 'desc'
        ];

        User::where('role_id', $role_id)->chunk(100, function ($users) use ($slug, $fieldsConfig) {
            foreach ($users as $user) {
                $tables = $this->getUserTables($user);
                $tables[$slug] = $fieldsConfig;
                $this->saveUserTables($user, $tables);
            }
        });

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

        $settings = DB::table('settings')->where('key', 'tables')->first();
        $globalTables = [];

        if ($settings && $settings->value) {
            $decoded = json_decode($settings->value, true);
            if (is_array($decoded)) $globalTables = $decoded;
        } elseif ($settings && !is_array($settings->value) && ValueHelper::isJson($settings->value)) {

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

    public function get_order_products(Request $request)
    {
        $tables = $this->getUserTables(Auth::user());

        $productColumns = [
            'product_name'   => ['title' => 'Наименование товара', 'type' => 'relation', 'related_table' => 'products', 'read_only' => 0],
            'product_price'  => ['title' => 'Цена продажи', 'type' => 'number', 'read_only' => 0],
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

            $ignoredKeys = array_merge(
                ['isChoose', 'actions', 'iconDrag', 'iconDelete', 'price', 'name'],
                array_keys($productColumns)
            );

            foreach ($savedColumns as $key => $col) {

                if (!$modelFields->contains('field', $key) && !in_array($key, $ignoredKeys)) {
                    unset($savedColumns[$key]);
                }
            }

            foreach ($modelFields as $field) {

                if (!array_key_exists($field->field, $savedColumns) && $field->type != 'text_group' && $field->field != 'price') {
                    $savedColumns[$field->field] = $this->createColumnStructure($field, count($savedColumns) + 1, ['enabled' => 0]);
                }
            }

            $idx = 0;
            foreach ($productColumns as $key => $meta) {
                if (!isset($savedColumns[$key])) {
                    $savedColumns[$key] = $this->createCustomColumn($key, $meta, $idx++);
                }
            }

            $this->ensureSystemColumns($savedColumns, true);

            return response()->json(array_values($savedColumns));

        } else {

            $tableColumns = [];
            $idx = 0;
            foreach ($productColumns as $key => $meta) {
                $tableColumns[$key] = $this->createCustomColumn($key, $meta, $idx++);
            }

            if (!isset($tableColumns['isChoose'])) $tableColumns['isChoose'] = $this->getSystemColumn('iconDrag');
            if (!isset($tableColumns['actions'])) $tableColumns['actions'] = $this->getSystemColumn('iconDelete');

            $result = [];
            $i = 0;
            foreach($productColumns as $k => $v) {
                $result[$k] = $this->createCustomColumn($k, $v, $i++);
            }

            $result['isChoose'] = $this->getSystemColumn('iconDrag');
            $result['iconDelete'] = $this->getSystemColumn('iconDelete');

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
            ['updated_at' => $now, 'created_at' => $now]
        );
    }

    private function tryApplyFallbackSettings($user, &$tables, $slug)
    {

        $role = Role::find($user->role_id);
        if ($role && $role->tables) {
            $roleTables = json_decode($role->tables, true);
            if (is_array($roleTables) && isset($roleTables[$slug])) {
                $tables[$slug] = $roleTables[$slug];
                $this->saveUserTables($user, $tables);
                return $tables;
            }
        }

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

        $columns['isChoose'] = $this->getSystemColumn('checkbox');
        $columns['actions']  = $this->getSystemColumn('actions');

        $settings = app('settings');
        $fieldColors = [];

        foreach ($modelFields as $field) {
            if ($field->type == 'text_group' || $field->type == 'password') continue;

            $columns[$field->field] = $this->createColumnStructure($field, count($columns) + 1);

            $perms = $settings[$slug]['perms'][$field->field] ?? null;
            $columns[$field->field]['can_edit'] = (isset($perms) && !$perms['write']) ? 1 : 0;

            if (isset($settings['list_values'][$field->id])) {
                $vals = $settings['list_values'][$field->id];
                if ($field->type == 'relation') {
                    $vals = array_slice($vals, 0, 10, true);
                }
                $columns[$field->field]['options'] = $vals;
            }

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

        foreach ($columns as $key => $col) {
            $isSystem = in_array($key, ['isChoose', 'actions', 'iconDrag', 'iconDelete']);

            $isRelated = strpos((string) $key, 'rel__') === 0;
            if (!$modelFields->contains('field', $key) && !$isSystem && !$isRelated) {
                unset($columns[$key]);
            }
        }

        if (!isset($columns['isChoose'])) $columns['isChoose'] = $this->getSystemColumn('checkbox');
        if (!isset($columns['actions']))  $columns['actions'] = $this->getSystemColumn('actions');

        foreach ($modelFields as $field) {

            $fieldValues = [];
            if (isset($settings['list_values'][$field->id])) {
                $fieldValues = $settings['list_values'][$field->id];
                if ($field->type == 'relation') {
                    $fieldValues = array_slice($fieldValues, 0, 10, true);
                }
            }

            if (!array_key_exists($field->field, $columns) && $field->type != 'text_group' && $field->type != 'password') {
                $columns[$field->field] = $this->createColumnStructure($field, count($columns) + 1);
            }

            if (isset($columns[$field->field])) {
                $col = &$columns[$field->field];

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
            'can_edit' => 1,
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

                return [
                    'id' => null, 'title' => 'Удаление', 'key' => 'iconDelete',
                    'width' => '40px', 'enabled' => 1, 'sort_order' => '', 'type' => 'iconDelete',
                    'fixed' => '', 'index' => 1, 'fixTarget' => '0px', 'read_only' => 1, 'mask' => ''
                ];
        }
        return [];
    }
}