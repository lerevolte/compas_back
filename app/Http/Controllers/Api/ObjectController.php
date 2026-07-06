<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CrudService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ObjectExport;
use App\Models\EntityObject;
use App\Models\Table;
use App\Models\Field;
use App\Models\Menu;
use App\Models\History;
use App\Models\Role;
use App\Models\Filter;
use App\Models\Permission;
use App\Models\Module;
use App\Models\Settings;

class ObjectController extends Controller
{
    private CrudService $crudService;
    private SearchService $searchService;
 
    public function __construct(CrudService $crudService, SearchService $searchService)
    {
        $this->crudService = $crudService;
        $this->searchService = $searchService;
    }

    public function list($slug, Request $request): JsonResponse
    {
        $data = EntityObject::list($slug, $request);
        return response()->json($data);
    }

    public function compose_list($slug, Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // 1. Получение и проверка прав (вынесено в отдельный метод)
        $permissions = $this->getPermissions($user, $slug);
        
        // Проверка на запрет чтения
        if (!$user->is_admin && isset($permissions['read_p']) && $permissions['read_p'] === 'N') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 2. Получение настроек таблицы
        $table = Table::get($slug);
        if (isset($table['error'])) {
            return response()->json(['message' => $table['error']['message']], $table['error']['code']);
        }

        // 3. Подготовка параметров запроса
        if (isset($permissions['read_p']) && $permissions['read_p'] === 'Y' && !$user->is_admin) {
            $request->merge(['filter' => array_merge($request->input('filter', []), ['user_id' => $user->id])]);
        }

        // 4. Получение списка объектов
        $list = EntityObject::list($slug, $request);
        if (isset($list['error'])) {
            return response()->json(['message' => $list['error']['message']], $list['error']['code']);
        }

        // 5. Загрузка категорий (Замена if/else на маппинг)
        $categories = $this->getCategoriesForSlug($slug);

        // 6. Сборка финального ответа
        // Оптимизация: параллельные запросы здесь не сделать в PHP синхронно без доп. библиотек,
        // но код стал чище.
        return response()->json([
            'list'        => $list,
            'table'       => $table,
            'fields'      => Field::list($slug),
            'entities'    => DB::table('data_types')->select(['slug', 'title_singular', 'title_plural', 'color'])->get(),
            'filters'     => Filter::list($slug),
            'categories'  => $categories,
            'permissions' => $permissions,
            'tabs'        => Menu::tabs($slug),
            // 'sidebar' можно не передавать, если фронт запрашивает его отдельно, или раскомментировать ниже
            // 'sidebar'     => $user->getSidebar(), 
        ]);
    }

    public function compose_show($slug, $id, Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user(); // Получаем юзера корректно для API
        $isExternalAccess = !$user;

        $entity = DB::table('data_types')->where('slug', $slug)->first();
        if (!$entity) {
            return response()->json(['message' => 'Entity not found'], 404);
        }

        // 1. Логика прав доступа
        $permissions = [];
        if ($user && $user->role_id) {
            $permissions = $this->getPermissions($user, $slug, $entity->id);
        } elseif ($isExternalAccess) {
            $permissions = ['read_p' => 'A'];
        }

        // Специфичная логика для Users
        if ($user && ($slug === 'users' && $id == $user->id || $user->is_admin)) {
            $permissions['update_p'] = 'A';
        }

        // Проверка запрета
        $isForbidden = isset($permissions['read_p']) && $permissions['read_p'] === 'N';
        $isSelfUser = $slug === 'users' && $user && $user->id == $id;

        if ($isForbidden && !$isSelfUser && !($user && $user->is_admin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 2. Параметры запроса
        if ($user && isset($permissions['read_p']) && $permissions['read_p'] === 'Y' && !$user->is_admin) {
            $request->merge(['user_id' => $user->id]);
        }
        if ($request->is_copy) {
            $request->merge(['is_copy' => 1]);
        }

        // 3. Получение деталей
        $detail = EntityObject::detail($slug, $id, $request);
        if (isset($detail['error'])) {
            return response()->json(['message' => $detail['error']['message']], $detail['error']['code']);
        }

        // Создание нового объекта: id ещё нет (0/'0') либо это копия.
        $isCreate = empty($id) || $id === '0' || $id === 0 || (bool) $request->is_copy;

        if ($user && !$user->is_admin) {
            $canUpdate = true;
            if ($isCreate) {
                // При СОЗДАНИИ редактируемость зависит от права на создание
                // (create_p), а НЕ от update_p. Иначе при «Изменение: только
                // свои» новый объект (у него ещё нет владельца) блокировался
                // целиком, хотя право на создание есть (8563).
                $cp = $permissions['create_p'] ?? null;
                $canUpdate = $cp !== 'N';
            } else {
                $up = $permissions['update_p'] ?? null;
                if ($up === 'N') {
                    $canUpdate = false;
                } elseif ($up === 'Y') {
                    $ownerId = \Schema::hasColumn($slug, 'user_id')
                        ? DB::table($slug)->where('id', $id)->value('user_id')
                        : null;
                    $canUpdate = $ownerId !== null && (int) $ownerId === (int) $user->id;
                }
            }
            if (!$canUpdate) {
                $detail['readonly'] = true;
                if (isset($detail['columns']) && is_array($detail['columns'])) {
                    foreach ($detail['columns'] as $ck => $col) {
                        foreach ($col as $si => $section) {
                            if (!empty($section['fields'])) {
                                foreach ($section['fields'] as $fi => $f) {
                                    $detail['columns'][$ck][$si]['fields'][$fi]['can_edit'] = 0;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Поле «Пароль» в карточке пользователя не-админ видит только у себя.
        // В чужих карточках поле типа password скрываем целиком (8548).
        if ($slug === 'users' && $user && !$user->is_admin && !$isSelfUser
            && isset($detail['columns']) && is_array($detail['columns'])) {
            foreach ($detail['columns'] as $ck => $col) {
                foreach ($col as $si => $section) {
                    if (!empty($section['fields'])) {
                        $detail['columns'][$ck][$si]['fields'] = array_values(array_filter(
                            $section['fields'],
                            fn ($f) => ($f['type'] ?? null) !== 'password'
                        ));
                    }
                }
            }
        }

        if ($isExternalAccess) {
            $detail['readonly'] = true;
            $restricted = DB::table('data_rows')
                ->where('data_type_id', $entity->id)
                ->whereNotNull('roles_read')
                ->whereNotIn('roles_read', ['', '[]', '0'])
                ->pluck('field')
                ->filter()
                ->values()
                ->all();
            $filterFields = function ($fields) use ($restricted) {
                $fields = array_values(array_filter(
                    is_array($fields) ? $fields : [],
                    fn ($f) => !in_array(is_array($f) ? ($f['key'] ?? null) : null, $restricted, true)
                ));
                foreach ($fields as $fi => $f) {
                    $fields[$fi]['can_edit'] = 0;
                }
                return $fields;
            };
            if (isset($detail['columns']) && is_array($detail['columns'])) {
                foreach ($detail['columns'] as $ck => $col) {
                    foreach ($col as $si => $section) {
                        if (!empty($section['fields'])) {
                            $detail['columns'][$ck][$si]['fields'] = $filterFields($section['fields']);
                        }
                        if (!empty($section['children']) && is_array($section['children'])) {
                            foreach ($section['children'] as $ci => $child) {
                                if (!empty($child['fields'])) {
                                    $detail['columns'][$ck][$si]['children'][$ci]['fields'] = $filterFields($child['fields']);
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($restricted) && isset($detail['hidden_fields']) && is_array($detail['hidden_fields'])) {
                $detail['hidden_fields'] = array_values(array_filter(
                    $detail['hidden_fields'],
                    fn ($f) => !in_array(is_array($f) ? ($f['key'] ?? null) : null, $restricted, true)
                ));
            }
        }

        $canDelete = true;
        if ($user) {
            $dp = is_array($permissions) ? ($permissions['delete_p'] ?? null) : null;
            if ($dp === 'N') {
                $canDelete = false;
            } elseif ($dp === 'Y') {
                $ownerId = \Schema::hasColumn($slug, 'user_id')
                    ? DB::table($slug)->where('id', $id)->value('user_id')
                    : null;
                $canDelete = $ownerId !== null && (int) $ownerId === (int) $user->id;
            }
        }
        if (is_array($permissions)) {
            $permissions['can_delete'] = $canDelete;
        } elseif (is_object($permissions)) {
            $permissions->can_delete = $canDelete;
        }

        // 4. Дополнительные данные (продукты, история)
        $products = [];
        $tableKeys = [];
        
        if ($slug === 'logistic_tasks') {
            $tableKeys = Table::get_order_products();
            if ($id) {
                $products = EntityObject::list('products', new Request(['order_id' => $id]));
            }
        }

        $history_events = [];
        $history_fields = [];

        if ($id && !$request->is_copy) {
            // Оптимизация: запрос истории только если это не копирование и есть ID
            $history_events = History::list($slug, $id, null, new Request(['filter' => 'events']));
            $history_fields = History::list($slug, $id, null);
        }

        return response()->json([
            'detail'         => $detail,
            'table'          => [
                'tableKeys' => $tableKeys,
                'tableBody' => $products
            ],
            'history_events' => $history_events,
            'history_fields' => $history_fields,
            'tabs'           => Menu::get($slug),
            'permissions'    => $permissions
        ]);
    }

    public function compose_show_module($slug, $id, $module, Request $request): JsonResponse
    {
        // Базовые данные
        $detail = EntityObject::detail_module($slug, $id, $module, new Request());
        if (isset($detail['error'])) {
            return response()->json(['message' => $detail['error']['message']], $detail['error']['code']);
        }

        // Данные для specific cases
        $products = [];
        if ($slug === 'logistic_tasks') {
            $products = EntityObject::list('products', new Request(['order_id' => $id]));
        }

        // Проверка прав (используем уже существующего юзера из Auth)
        $user = Auth::user();
        $dataTypeId = DB::table('data_types')->where('slug', $slug)->value('id'); // Используем value для оптимизации
        
        // Получаем permissions только если есть ID типа данных
        $permissions = [];
        if ($dataTypeId && $user) {
            $permissions = $user->role->permissions()
                ->select(['read_p', 'create_p', 'update_p', 'delete_p', 'export_p', 'import_p'])
                ->where('entity_id', $dataTypeId)
                ->first();
             
             if ($permissions) {
                 $permissions = $permissions->toArray();
             }
        }

        // Связанные сущности модуля
        $moduleModel = Module::where('slug', $module)->firstOrFail();
        
        return response()->json([
            'detail'           => $detail,
            'products'         => $products,
            'table'            => Table::get_order_products(),
            'history_events'   => History::list($slug, $id, $module, new Request(['filter' => 'events'])),
            'history_fields'   => History::list($slug, $id, $module),
            'menu'             => Menu::get($slug),
            'related_entities' => $moduleModel->statusesEntities($slug, $id),
            'permissions'      => $permissions
        ]);
    }

    public function batch($slug, Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user && !$user->is_admin) {
            $perms = $this->getPermissions($user, $slug);
            $hasCreate = false;
            $hasUpdate = false;
            foreach (($request->rows ?? []) as $row) {
                if (empty($row['id']) || !empty($row['copy'])) {
                    $hasCreate = true;
                } else {
                    $hasUpdate = true;
                }
            }
            if ($hasCreate && isset($perms['create_p']) && $perms['create_p'] === 'N') {
                return response()->json(['message' => 'Нет прав на создание'], 403);
            }
            if ($hasUpdate && isset($perms['update_p']) && $perms['update_p'] === 'N') {
                return response()->json(['message' => 'Нет прав на редактирование'], 403);
            }
            if ($hasUpdate && isset($perms['update_p']) && $perms['update_p'] === 'Y'
                && \Schema::hasColumn($slug, 'user_id')) {
                foreach (($request->rows ?? []) as $row) {
                    if (empty($row['id']) || !empty($row['copy'])) {
                        continue;
                    }
                    $owner = DB::table($slug)->where('id', $row['id'])->value('user_id');
                    if ($owner !== null && (int) $owner !== (int) $user->id) {
                        return response()->json(['message' => 'Можно редактировать только свои записи'], 403);
                    }
                }
            }
        }

        $result = $this->crudService->batch($slug, $request->rows);
        return response()->json($result, $result['status']);
    }

    public function delete($slug, Request $request): JsonResponse
    {
        $user = Auth::user();

        // Пользователя с id=1 (системный/владелец портала) удалять нельзя (8588).
        if ($slug === 'users' && in_array(1, array_map('intval', (array) $request->ids), true)) {
            return response()->json(['message' => 'Этого пользователя нельзя удалить'], 403);
        }

        if ($user && !$user->is_admin) {
            $perms = $this->getPermissions($user, $slug);
            if (isset($perms['delete_p']) && $perms['delete_p'] === 'N') {
                return response()->json(['message' => 'Нет прав на удаление'], 403);
            }
            if (isset($perms['delete_p']) && $perms['delete_p'] === 'Y'
                && \Schema::hasColumn($slug, 'user_id')) {
                $foreign = DB::table($slug)->whereIn('id', (array) $request->ids)
                    ->where(function ($q) use ($user) {
                        $q->whereNull('user_id')->orWhere('user_id', '!=', $user->id);
                    })
                    ->exists();
                if ($foreign) {
                    return response()->json(['message' => 'Можно удалять только свои записи'], 403);
                }
            }
        }

        $result = $this->crudService->delete($slug, $request->ids);
        return response()->json($result, $result['status']);
    }

    public function restore($slug, Request $request): JsonResponse
    {
        $result = $this->crudService->restore($slug, $request->ids);
        return response()->json($result, $result['status']);
    }

    public function restore_single($slug, $id): JsonResponse
    {
        $settings = app('settings');
        if (!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json(['message' => 'Entity not found'], 404);
        }

        $entityClass = $settings['models'][$slug]->model_name;
        $item = $entityClass::withTrashed()->findOrFail($id); // Fail сразу, если не найден

        // Логика восстановления
        $item->restore();

        // Запись истории
        $history = new History([
            'entity'    => $slug,
            'entity_id' => $item->id,
            'user_id'   => Auth::id(),
            'text'      => 'Восстановлена запись: ' . $item->id,
            'event'     => 'OBJECT_RESTORED'
        ]);
        $history->save();

        Settings::clear_cache();
        $freshSettings = Settings::get(true);
        $data = $item->getData([], $freshSettings);

        // Событие
        \App\Events\ObjectUpdated::dispatch('ObjectRestored', $data, tenant('id'));

        // Формирование ответа истории через существующий метод
        $historyResponse = [History::getDataList([$history])];

        return response()->json([
            'success'        => true,
            'details'        => $data['viewDetail'],
            'history_events' => $historyResponse
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->searchService->find($request->all());
        return response()->json($result);
    }

    public function export($slug, Request $request): JsonResponse
    {
        // Убрал info(), если они нужны для дебага, можно вернуть
        $user = Auth::user();
        
        // Логика получения настроек таблиц
        $tables = $this->getUserTables($user, $slug);

        $fieldsForExport = [];
        $headings = [];

        // Получение полей модели
        $settings = app('settings');
        if (!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
             return response()->json(['message' => 'Entity not found'], 404);
        }
        
        $modelFields = collect($settings[$slug]['fields']);
        
        // Логика фильтрации колонок
        if (isset($tables[$slug]) && !$request->fields) {
            // Если есть сохраненные настройки таблицы у юзера (оставляем без изменений)
            $tableColumns = collect($tables[$slug])->keyBy('key');
            
            $systemKeys = ['isChoose', 'actions', 'iconDrag', 'iconDelete'];
            $filteredColumns = $tableColumns->filter(function($val, $key) use ($modelFields, $systemKeys) {
                 return $modelFields->contains('field', $key) || in_array($key, $systemKeys);
            });
            
            foreach ($filteredColumns as $column) {
                if (isset($column['title']) && !in_array($column['key'], $systemKeys)) {
                    $headings[] = $column['title'];
                    $fieldsForExport[] = $column['key'];
                }
            }
        } else {
            // ИЗМЕНЕННАЯ ЛОГИКА ЗДЕСЬ

            if ($request->fields && is_array($request->fields)) {
                // ВАРИАНТ 1: Если переданы конкретные поля, соблюдаем их порядок
                
                // Преобразуем modelFields в ассоциативный массив для быстрого поиска по ключу
                $modelFieldsMap = $modelFields->keyBy('field');

                foreach ($request->fields as $requestedFieldKey) {
                    // Ищем описание поля в настройках
                    $fieldData = $modelFieldsMap->get($requestedFieldKey);

                    // Если поле найдено в настройках и не запрещено
                    if ($fieldData && !in_array($fieldData->type, ['text_group', 'password'])) {
                        
                        $title = $fieldData->display_parent_name 
                            ? "$fieldData->display_parent_name, $fieldData->title" 
                            : $fieldData->title;

                        // Добавляем в том порядке, в котором они пришли в $request->fields
                        $headings[] = $title;
                        $fieldsForExport[] = $requestedFieldKey;
                    }
                    
                    // Если нужно экспортировать системные поля (id, created_at), которых может не быть в $settings[$slug]['fields'],
                    // но они есть в модели, добавьте проверку здесь. 
                    // Обычно id и даты есть в $modelFields, так что код выше их подхватит.
                }

            } else {
                // ВАРИАНТ 2: Дефолтная логика (если fields не переданы), берем порядок из модели
                $sortedFields = $modelFields->filter(function($field) {
                     return !in_array($field->type, ['text_group', 'password']);
                });

                foreach ($sortedFields as $field) {
                    $title = $field->display_parent_name 
                        ? "$field->display_parent_name, $field->title" 
                        : $field->title;

                    $headings[] = $title;
                    $fieldsForExport[] = $field->field;
                }
            }
        }

        $params = $request->all();
        $params['headings'] = [
            'names'  => $headings,
            'fields' => $fieldsForExport
        ];

        $filename = 'export' . time() . '.xlsx';
        Excel::store(new ObjectExport($slug, $params), $filename, 'public'); 

        $url = Storage::disk('public')->url($filename);
        
        if (tenant('id')) {
             $url = 'https://'.tenant('id').'.compas.pro/storage/tenant'.tenant('id').'/app/public/'.$filename;
        }

        return response()->json(['link' => $url]);
    }

    // --- Private Helpers (Вынесенная логика) ---

    /**
     * Получает права доступа пользователя к сущности
     */
    private function getPermissions($user, $slug, $dataTypeId = null): array
    {
        if (!$dataTypeId) {
            $dataType = DB::table('data_types')->where('slug', $slug)->first();
            if (!$dataType) return [];
            $dataTypeId = $dataType->id;
        }

        $permissions = [];
        if ($user && $user->role_id) {
            $permissions = $user->role->permissions()
                ->select(['read_p', 'create_p', 'update_p', 'delete_p', 'export_p', 'import_p'])
                ->where('entity_id', $dataTypeId)
                ->first();

            // Если прав нет, пытаемся инициализировать (старая логика)
            if (!$permissions) {
                $this->initializePermissions($user);
                $permissions = $user->role->permissions()
                    ->select(['read_p', 'create_p', 'update_p', 'delete_p', 'export_p', 'import_p'])
                    ->where('entity_id', $dataTypeId)
                    ->first();
            }
        }
        
        return $permissions ? $permissions->toArray() : [];
    }

    /**
     * Инициализация прав, если их нет (Legace logic refactored)
     */
    private function initializePermissions($user)
    {
        $existingPermissions = Permission::where('role_id', $user->role_id)
            ->whereNotNull('entity_id')
            ->pluck('entity_id')
            ->toArray();

        $dataTypes = DB::table('data_types')
            ->where('enable', 1)
            ->where('hidden', 0)
            ->pluck('id');

        $toInsert = [];
        foreach ($dataTypes as $id) {
            if (!in_array($id, $existingPermissions)) {
                $toInsert[] = ['entity_id' => $id, 'role_id' => $user->role_id];
            }
        }

        if (!empty($toInsert)) {
            DB::table('permissions')->insert($toInsert);
        }
    }

    /**
     * Маппинг категорий вместо if/elseif
     */
    private function getCategoriesForSlug($slug): array
    {
        $categoryModels = [
            'products'     => \Modules\Products\Entities\Category::class,
            'instructions' => \Modules\Instructions\Entities\Category::class,
            'faq'          => \App\Models\FaqCategory::class,
            'knowledge'    => \App\Models\KnowledgeCategory::class,
            'articles'     => \App\Models\BlogCategory::class,
            'guides'       => \App\Models\GuideCategory::class,
        ];

        if (!isset($categoryModels[$slug])) {
            return [];
        }

        $modelClass = $categoryModels[$slug];
        if (!class_exists($modelClass)) {
            return [];
        }

        $tree = $modelClass::get()->toTree()->toArray();

        // Рекурсивная обработка имен (вынесена, чтобы не дублировать)
        return $this->processCategoryNames($tree);
    }

    private function processCategoryNames(array $categories): array
    {
        foreach ($categories as $k => $category) {
            $name = json_decode($category['name'], true);
            if (isset($name['value'])) {
                $categories[$k]['name'] = $name['value'];
            }

            if (!empty($category['children'])) {
                $categories[$k]['children'] = $this->processCategoryNames($category['children']);
            }
        }
        return $categories;
    }

    /**
     * Получение таблиц пользователя (для export)
     */
    private function getUserTables($user, $slug)
    {
        $userTables = $user->tables ? json_decode($user->tables, true) : [];

        // Если у пользователя уже есть настройка для этого слага, возвращаем
        if (isset($userTables[$slug])) {
            return $userTables;
        }

        // Проверяем роль
        $role = $user->role;
        $roleTables = $role && $role->tables ? json_decode($role->tables, true) : [];
        
        if (isset($roleTables[$slug])) {
            $userTables[$slug] = $roleTables[$slug];
            $user->tables = json_encode($userTables);
            $user->saveQuietly();
            return $userTables;
        }

        // Проверяем глобальные настройки
        $globalTablesJson = DB::table('settings')->where('key', 'tables')->value('value');
        $globalTables = $globalTablesJson ? json_decode($globalTablesJson, true) : [];

        if (isset($globalTables[$slug])) {
            $userTables[$slug] = $globalTables[$slug];
            $user->tables = json_encode($userTables);
            $user->saveQuietly();
        }

        return $userTables;
    }
}