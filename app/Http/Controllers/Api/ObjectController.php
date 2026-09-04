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

        if (!$user->is_admin && ($request->trashed || $request->with_trashed)) {
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
        if (isset($permissions['read_p']) && $permissions['read_p'] === 'E' && !$user->is_admin
            && $this->hasEmployeeBinding($slug)) {
            $employeeIds = \App\Models\Employee::idsForUser($user);
            $request->merge(['filter' => array_merge($request->input('filter', []), ['employee_id' => $employeeIds ?: -1])]);
        }

        // 4. Получение списка объектов
        $list = EntityObject::list($slug, $request);
        if (isset($list['error'])) {
            return response()->json(['message' => $list['error']['message']], $list['error']['code']);
        }

        if ($slug === 'addresses') {
            if ($user && $user->is_admin) {
                $permissions['create_task_p'] = 'A';
            } else {
                $taskPermissions = $this->getPermissions($user, 'logistic_tasks');
                $permissions['create_task_p'] = $taskPermissions['create_p'] ?? 'A';
            }
        }

        $permissions['external_link_read_p'] = $this->externalLinkRoleReadP(
            DB::table('data_types')->where('slug', $slug)->value('id')
        );

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

    public function attach_employee($slug, $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $entity = DB::table('data_types')->where('slug', $slug)->first();
        if (!$entity || !\Schema::hasTable($slug)) {
            return response()->json(['message' => 'Entity not found'], 404);
        }

        $permissions = $this->getPermissions($user, $slug, $entity->id);
        if (($permissions['read_p'] ?? 'A') === 'N' && !$user->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $field = DB::table('data_rows')
            ->where('data_type_id', $entity->id)
            ->where('type', 'relation')
            ->where('relation_table', 'employees')
            ->where('is_plural', 1)
            ->where('is_remove', 0)
            ->first();
        if (!$field || !\Schema::hasColumn($slug, $field->field)) {
            return response()->json(['message' => 'У сущности нет множественного поля «Сотрудник»'], 422);
        }

        $query = DB::table($slug)->where('id', (int) $id);
        if (\Schema::hasColumn($slug, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        $object = $query->first();
        if (!$object) {
            return response()->json(['message' => 'Объект не найден'], 404);
        }

        $employeeId = \App\Models\Employee::idsForUser($user)[0] ?? null;
        if (!$employeeId) {
            return response()->json(['message' => 'У вашего пользователя нет привязанного сотрудника'], 422);
        }

        $current = json_decode((string) $object->{$field->field}, true);
        if (!is_array($current)) {
            $current = array_filter([$object->{$field->field}]);
        }
        $current = array_values(array_map('intval', array_filter($current, 'is_numeric')));

        if (in_array((int) $employeeId, $current, true)) {
            return response()->json(['ok' => true, 'attached' => false]);
        }

        $current[] = (int) $employeeId;
        $result = app(\App\Services\CrudService::class)->batch($slug, [[
            'id' => (int) $id,
            $field->field => $current,
        ]]);
        if (isset($result['status']) && $result['status'] >= 400) {
            return response()->json(['message' => $result['message'] ?? 'Не удалось привязать сотрудника'], $result['status']);
        }

        return response()->json(['ok' => true, 'attached' => true]);
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
            $permissions = $this->externalLinkPermissions($entity->id) ?? ['read_p' => 'A'];
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

        if ($user && !$user->is_admin && $id && $id !== '0'
            && isset($permissions['read_p']) && $permissions['read_p'] === 'E'
            && $this->hasEmployeeBinding($slug)) {
            if (!$this->isUserEmployeeObject($slug, $id, $user)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        if ($id && $id !== '0' && !($user && $user->is_admin)
            && \Schema::hasColumn($slug, 'deleted_at')
            && DB::table($slug)->where('id', $id)->whereNotNull('deleted_at')->exists()) {
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
                } elseif ($up === 'E') {
                    $canUpdate = $this->hasEmployeeBinding($slug)
                        && $this->isUserEmployeeObject($slug, $id, $user);
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

        if ($isExternalAccess && $this->isExternalRoleUser()) {
            if (($permissions['update_p'] ?? 'N') !== 'A') {
                $detail['readonly'] = true;
                if (isset($detail['columns']) && is_array($detail['columns'])) {
                    foreach ($detail['columns'] as $ck => $col) {
                        foreach ($col as $si => $section) {
                            if (!empty($section['fields'])) {
                                foreach ($section['fields'] as $fi => $f) {
                                    $detail['columns'][$ck][$si]['fields'][$fi]['can_edit'] = 0;
                                    if (is_array($f) && ($f['type'] ?? null) == 'text_group' && !empty($f['fields'])) {
                                        foreach ($f['fields'] as $gi => $gf) {
                                            $detail['columns'][$ck][$si]['fields'][$fi]['fields'][$gi]['can_edit'] = 0;
                                        }
                                    }
                                }
                            }
                            if (!empty($section['children']) && is_array($section['children'])) {
                                foreach ($section['children'] as $ci => $child) {
                                    if (!empty($child['fields'])) {
                                        foreach ($child['fields'] as $fi => $f) {
                                            $detail['columns'][$ck][$si]['children'][$ci]['fields'][$fi]['can_edit'] = 0;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($isExternalAccess) {
            $detail['readonly'] = true;
            $restricted = DB::table('data_rows')
                ->where('data_type_id', $entity->id)
                ->whereNotNull('roles_read')
                ->whereNotIn('roles_read', ['', '[]', '0'])
                ->pluck('field')
                ->filter()
                ->values()
                ->all();
            $filterFields = null;
            $filterFields = function ($fields) use ($restricted, &$filterFields) {
                $fields = array_values(array_filter(
                    is_array($fields) ? $fields : [],
                    fn ($f) => !in_array(is_array($f) ? ($f['key'] ?? null) : null, $restricted, true)
                ));
                foreach ($fields as $fi => $f) {
                    $fields[$fi]['can_edit'] = 0;
                    if (is_array($f) && ($f['type'] ?? null) == 'text_group') {
                        $fields[$fi]['fields'] = $filterFields($f['fields'] ?? []);
                    }
                }
                return array_values(array_filter(
                    $fields,
                    fn ($f) => !(is_array($f) && ($f['type'] ?? null) == 'text_group' && empty($f['fields']))
                ));
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
            $permissions['external_link_read_p'] = $this->externalLinkRoleReadP($entity->id);
        } elseif (is_object($permissions)) {
            $permissions->can_delete = $canDelete;
            $permissions->external_link_read_p = $this->externalLinkRoleReadP($entity->id);
        }

        $basedCreate = $this->basedCreatePermissions($user, $slug);
        if ($basedCreate !== null) {
            if (is_array($permissions)) {
                $permissions['based_create'] = $basedCreate;
            } elseif (is_object($permissions)) {
                $permissions->based_create = $basedCreate;
            }
        }

        // 4. Дополнительные данные (продукты, история)
        $products = [];
        $tableKeys = [];

        if (in_array($slug, ['logistic_tasks', 'pickups', 'deals', 'payment_invoices', 'expense_invoices', 'product_returns', 'addresses'], true)) {
            $productsPerms = $this->getProductsFieldPerms($user, $entity->id, $isExternalAccess, $slug);
            if ($productsPerms['read']) {
                $tableKeys = Table::get_order_products($slug);
                if (!$productsPerms['write']) {
                    foreach ($tableKeys as $tk => $tableKey) {
                        $tableKeys[$tk]['read_only'] = 1;
                        $tableKeys[$tk]['can_edit'] = 0;
                    }
                }
                if ($id) {
                    $products = EntityObject::list('products', new Request(['order_id' => $id, 'order_entity' => $slug]));
                }
            }
        }

        $eventsVisibility = self::eventsVisibilitySettings($slug);
        $eventsVisible = $this->eventsVisibleFor($user, $eventsVisibility);

        $history_events = [];
        $history_fields = [];

        if ($id && !$request->is_copy) {
            // Оптимизация: запрос истории только если это не копирование и есть ID
            if ($eventsVisible) {
                $history_events = History::list($slug, $id, null, new Request(['filter' => 'events']));
            }
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
            'events_visibility' => $eventsVisibility + ['visible' => $eventsVisible],
            'permissions'    => $permissions
        ]);
    }

    public static function eventsVisibilitySettings($slug): array
    {
        $row = DB::table('settings')
            ->where(['type' => 'events_visibility', 'entity' => $slug])
            ->whereNull('user_id')
            ->first();
        $value = $row && $row->value ? json_decode($row->value, true) : [];
        $roles = array_values(array_map('intval', (array) ($value['roles_read'] ?? [])));

        return [
            'has_roles_read' => (bool) ($value['has_roles_read'] ?? false) && count($roles) > 0,
            'roles_read' => $roles,
        ];
    }

    private function eventsVisibleFor($user, array $settings): bool
    {
        if (!$settings['has_roles_read']) {
            return true;
        }
        if ($user) {
            if ($user->is_admin) {
                return true;
            }
            return in_array((int) $user->role_id, $settings['roles_read'], true);
        }
        $roleId = DB::table('roles')->where('name', 'external_link')->whereNull('deleted_at')->value('id');

        return $roleId ? in_array((int) $roleId, $settings['roles_read'], true) : false;
    }

    public function compose_show_module($slug, $id, $module, Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($id && $id !== '0' && !($user && $user->is_admin)
            && \Schema::hasColumn($slug, 'deleted_at')
            && DB::table($slug)->where('id', $id)->whereNotNull('deleted_at')->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Базовые данные
        $detail = EntityObject::detail_module($slug, $id, $module, new Request());
        if (isset($detail['error'])) {
            return response()->json(['message' => $detail['error']['message']], $detail['error']['code']);
        }

        $dataTypeId = DB::table('data_types')->where('slug', $slug)->value('id');

        $products = [];
        $tableKeys = [];
        if ($slug === 'logistic_tasks') {
            $productsPerms = $this->getProductsFieldPerms($user, $dataTypeId, !$user);
            if ($productsPerms['read']) {
                $products = EntityObject::list('products', new Request(['order_id' => $id]));
                $tableKeys = Table::get_order_products($slug);
                if (!$productsPerms['write']) {
                    foreach ($tableKeys as $tk => $tableKey) {
                        $tableKeys[$tk]['read_only'] = 1;
                        $tableKeys[$tk]['can_edit'] = 0;
                    }
                }
            }
        } else {
            $tableKeys = Table::get_order_products($slug);
        }

        $permissions = [];
        if ($dataTypeId && $user) {
            $permissions = $this->getPermissions($user, $slug, $dataTypeId);
        }

        $lockEdit = function () use (&$detail) {
            $detail['readonly'] = true;
            if (isset($detail['columns']) && is_array($detail['columns'])) {
                foreach ($detail['columns'] as $ck => $col) {
                    foreach ($col as $si => $section) {
                        if (!empty($section['fields'])) {
                            foreach ($section['fields'] as $fi => $f) {
                                $detail['columns'][$ck][$si]['fields'][$fi]['can_edit'] = 0;
                                if (is_array($f) && ($f['type'] ?? null) == 'text_group' && !empty($f['fields'])) {
                                    foreach ($f['fields'] as $gi => $gf) {
                                        $detail['columns'][$ck][$si]['fields'][$fi]['fields'][$gi]['can_edit'] = 0;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        };

        if (!$user) {
            $lockEdit();
        } elseif (!$user->is_admin && $id && $id !== '0') {
            $canUpdate = true;
            $up = $permissions['update_p'] ?? null;
            if ($up === 'N') {
                $canUpdate = false;
            } elseif ($up === 'Y') {
                $ownerId = \Schema::hasColumn($slug, 'user_id')
                    ? DB::table($slug)->where('id', $id)->value('user_id')
                    : null;
                $canUpdate = $ownerId !== null && (int) $ownerId === (int) $user->id;
            } elseif ($up === 'E') {
                $canUpdate = $this->hasEmployeeBinding($slug)
                    && $this->isUserEmployeeObject($slug, $id, $user);
            }
            if (!$canUpdate) {
                $lockEdit();
            }
        }

        // Связанные сущности модуля
        $moduleModel = Module::where('slug', $module)->firstOrFail();

        $eventsVisibility = self::eventsVisibilitySettings($slug);
        $eventsVisible = $this->eventsVisibleFor($user, $eventsVisibility);

        return response()->json([
            'detail'           => $detail,
            'products'         => $products,
            'table'            => $tableKeys,
            'history_events'   => $eventsVisible ? History::list($slug, $id, $module, new Request(['filter' => 'events'])) : [],
            'history_fields'   => History::list($slug, $id, $module),
            'menu'             => Menu::get($slug),
            'events_visibility' => $eventsVisibility + ['visible' => $eventsVisible],
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
            if ($hasUpdate && isset($perms['update_p']) && $perms['update_p'] === 'E'
                && $this->hasEmployeeBinding($slug)) {
                foreach (($request->rows ?? []) as $row) {
                    if (empty($row['id']) || !empty($row['copy'])) {
                        continue;
                    }
                    if (!$this->isUserEmployeeObject($slug, $row['id'], $user)) {
                        return response()->json(['message' => 'Можно редактировать только записи своего сотрудника'], 403);
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

        $permissions = $this->getPermissions($user, $slug);
        if (!$user->is_admin && isset($permissions['read_p']) && $permissions['read_p'] === 'N') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if (!$user->is_admin && isset($permissions['export_p']) && $permissions['export_p'] === 'N') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if (!$user->is_admin && ($request->trashed || $request->with_trashed)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if (isset($permissions['read_p']) && $permissions['read_p'] === 'Y' && !$user->is_admin) {
            $request->merge(['filter' => array_merge($request->input('filter', []), ['user_id' => $user->id])]);
        }
        if (isset($permissions['read_p']) && $permissions['read_p'] === 'E' && !$user->is_admin
            && $this->hasEmployeeBinding($slug)) {
            $employeeIds = \App\Models\Employee::idsForUser($user);
            $request->merge(['filter' => array_merge($request->input('filter', []), ['employee_id' => $employeeIds ?: -1])]);
        }

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
    private function basedCreatePermissions($user, string $slug): ?array
    {
        $targets = [
            'deals' => ['logistic_tasks', 'pickups', 'payment_invoices'],
            'logistic_tasks' => ['expense_invoices', 'product_returns'],
            'pickups' => ['expense_invoices', 'product_returns'],
            'addresses' => ['logistic_tasks'],
        ][$slug] ?? null;
        if ($targets === null) {
            return null;
        }

        $result = [];
        foreach ($targets as $target) {
            $targetEntity = DB::table('data_types')->where('slug', $target)->where('enable', 1)->first();
            if (!$targetEntity) {
                $result[$target] = false;
                continue;
            }
            if ($user && $user->is_admin) {
                $result[$target] = true;
                continue;
            }
            if (!$user) {
                $result[$target] = false;
                continue;
            }
            $targetPermissions = $this->getPermissions($user, $target, $targetEntity->id);
            $result[$target] = ($targetPermissions['create_p'] ?? 'A') !== 'N';
        }

        return $result;
    }

    private function getProductsFieldPerms($user, $entityId, $isExternalAccess = false, $slug = 'logistic_tasks'): array
    {
        if ($user && $user->is_admin) {
            return ['read' => true, 'write' => true];
        }
        if ($isExternalAccess || !$user) {
            $rolesRead = DB::table('data_rows')
                ->where('data_type_id', $entityId)
                ->where('field', 'products')
                ->value('roles_read');
            $restricted = $rolesRead && !in_array(trim((string) $rolesRead), ['', '[]', '0'], true);
            return ['read' => !$restricted, 'write' => false];
        }
        $settings = app('settings');
        $perms = $settings[$slug]['perms']['products'] ?? null;
        if (!$perms) {
            return ['read' => true, 'write' => true];
        }
        return ['read' => (bool) $perms['read'], 'write' => (bool) $perms['write']];
    }

    private static $employeePivots = [
        'routes' => ['table' => 'route_employee', 'fk' => 'route_id'],
        'logistic_tasks' => ['table' => 'logistic_task_employee', 'fk' => 'logistic_task_id'],
    ];

    private function hasEmployeeBinding($slug): bool
    {
        if (isset(self::$employeePivots[$slug]) && \Schema::hasTable(self::$employeePivots[$slug]['table'])) {
            return true;
        }
        return \Schema::hasColumn($slug, 'employee_id');
    }

    private function isUserEmployeeObject($slug, $id, $user): bool
    {
        $employeeIds = \App\Models\Employee::idsForUser($user);
        if (!$employeeIds || !$id) {
            return false;
        }
        $pivot = self::$employeePivots[$slug] ?? null;
        if ($pivot && \Schema::hasTable($pivot['table'])) {
            $linked = DB::table($pivot['table'])
                ->where($pivot['fk'], $id)
                ->whereIn('employee_id', $employeeIds)
                ->exists();
            if ($linked) {
                return true;
            }
        }
        if (!\Schema::hasColumn($slug, 'employee_id')) {
            return false;
        }
        $raw = DB::table($slug)->where('id', $id)->value('employee_id');
        if ($raw === null || $raw === '') {
            return false;
        }
        if (is_numeric($raw)) {
            return in_array((int) $raw, $employeeIds, true);
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return false;
        }
        if (array_key_exists('value', $decoded)) {
            $decoded = is_array($decoded['value']) ? $decoded['value'] : [$decoded['value']];
        }
        foreach ($decoded as $v) {
            if (is_numeric($v) && in_array((int) $v, $employeeIds, true)) {
                return true;
            }
        }
        return false;
    }

    private function getPermissions($user, $slug, $dataTypeId = null): array
    {
        if ($user && $user->is_admin) {
            return [
                'read_p'   => 'A',
                'create_p' => 'A',
                'update_p' => 'A',
                'delete_p' => 'A',
                'export_p' => 'A',
                'import_p' => 'A',
            ];
        }

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

    private function externalLinkRoleReadP($entityId): string
    {
        if (!$entityId) {
            return 'A';
        }
        $roleId = DB::table('roles')->where('name', 'external_link')->whereNull('deleted_at')->value('id');
        if (!$roleId) {
            return 'A';
        }
        $readP = DB::table('permissions')
            ->where('role_id', $roleId)
            ->where('entity_id', $entityId)
            ->value('read_p');

        return $readP ?? 'A';
    }

    private function isExternalRoleUser(): bool
    {
        $authUser = Auth::user();
        return $authUser && !$authUser->exists && $authUser->role_id;
    }

    private function externalLinkPermissions($entityId): ?array
    {
        if (!$this->isExternalRoleUser()) {
            return null;
        }
        $roleId = Auth::user()->role_id;
        $perm = DB::table('permissions')
            ->where('role_id', $roleId)
            ->where('entity_id', $entityId)
            ->first();
        if (!$perm) {
            DB::table('permissions')->insert([
                'entity_id' => $entityId,
                'role_id' => $roleId,
                'read_p' => 'A',
                'create_p' => 'N',
                'update_p' => 'N',
                'delete_p' => 'N',
                'export_p' => 'N',
                'import_p' => 'N',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $perm = DB::table('permissions')
                ->where('role_id', $roleId)
                ->where('entity_id', $entityId)
                ->first();
        }
        return $perm
            ? collect((array) $perm)->only(['read_p', 'create_p', 'update_p', 'delete_p', 'export_p', 'import_p'])->all()
            : null;
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
        $isAdminRole = (bool) ($user->role->is_admin ?? false);
        foreach ($dataTypes as $id) {
            if (!in_array($id, $existingPermissions)) {
                $toInsert[] = Permission::newEntityRow((int) $id, (int) $user->role_id, $isAdminRole);
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
            'products'     => \App\Models\Category::class,
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