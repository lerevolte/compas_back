<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SidebarItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Settings; // Предполагаю наличие модели, иначе DB::table
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Table; // Добавил импорт

class EntityController extends Controller
{
    // public function list(Request $request)
    // {
    //     // Выбираем только нужные поля сразу
    //     $entities = DB::table('data_types')
    //         ->select(['slug', 'title_singular', 'title_plural', 'color'])
    //         ->get();

    //     return response()->json($entities);
    // }

    public function compose_list(Request $request)
    {
        $entities = DB::table('data_types')
            ->select(['id', 'title_singular', 'title_plural', 'enable'])
            ->where('hidden', 0)
            ->get();

        // Используем коллекции для трансформации данных
        $list = $entities->map(function ($entity) {
            return [
                'id' => $entity->id,
                'name' => $entity->title_plural,
                'enable' => $entity->enable
            ];
        });

        $table = Table::entities();

        $data = [
            'list' => $list,
            'table' => $table,
        ];
        
        // info($data); // Логирование лучше убрать на продакшене

        return response()->json($data);
    }

    public function get_menu($slug, Request $request)
    {
        $userId = Auth::id();
        
        // Оптимизация: Пытаемся найти или создать запись одним выражением (если есть модель Settings)
        // Если модели нет, используем логику DB, но оптимизированную
        $item = DB::table('settings')->where([
            'entity' => $slug,
            'type' => 'menu',
            'user_id' => $userId
        ])->first();

        if (!$item) {
            // Ищем дефолтную настройку
            $defaultItem = DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
            ])->first();

            if ($defaultItem) {
                $newItemId = DB::table('settings')->insertGetId([
                    'key' => $defaultItem->key,
                    'title' => $defaultItem->title,
                    'value' => $defaultItem->value,
                    'entity' => $slug,
                    'type' => 'menu',
                    'user_id' => $userId
                ]);
                $item = DB::table('settings')->find($newItemId);
            }
        }

        if (!$item) {
            return response()->json([]); // Обработка случая, если меню вообще нет
        }

        $menu = json_decode($item->value, true) ?? [];
        
        // Находим максимальный ID в текущем меню
        $max_id = collect($menu)->max('id') ?? 0;

        $settingsApp = app('settings');
        $fields = $settingsApp[$slug]['fields'] ?? [];
        $count_new = 0;

        // Превращаем меню в коллекцию для удобного поиска
        $menuCollection = collect($menu);

        foreach ($fields as $field) {
            // Проверка условий
            if ($field->type === 'relation' && $field->is_plural && !in_array($field->field, ['role_id', 'category_id'])) {
                
                // Проверяем наличие таба
                $exists = $menuCollection->contains('tab', $field->field);

                if (!$exists) {
                    $details = json_decode($field->details, true);
                    if (isset($details['table'])) {
                        $max_id++;
                        $newItem = [
                            'title' => $field->title,
                            'tab' => $field->field,
                            'slug' => $details['table'],
                            'sort' => $max_id,
                            'enabled' => 1,
                            'id' => $max_id
                        ];
                        $menu[] = $newItem;
                        $menuCollection->push($newItem); // Добавляем в коллекцию для последующих итераций
                        $count_new++;
                    }
                }
            }
        }

        if ($count_new > 0) {
            DB::table('settings')
                ->where('id', $item->id)
                ->update(['value' => json_encode($menu)]);
        }

        return response()->json($menu);
    }

    public function set_menu($slug, Request $request)
    {
        $userId = Auth::id();
        $menuJson = json_encode($request->menu, JSON_UNESCAPED_UNICODE);

        DB::table('settings')->updateOrInsert(
            ['entity' => $slug, 'type' => 'menu', 'user_id' => $userId],
            ['value' => $menuJson] // Тут можно добавить key/title, если создается новая
        );

        $this->updateLocalCache($userId, "entities/$slug/menu");

        return response()->json($request->menu);
    }

    public function reset_menu($slug, Request $request)
    {
        $tenant = Tenant::find('seeds');
        
        // Использование run для переключения контекста БД
        $menuValue = $tenant->run(function () use ($slug) {
            $menu = DB::table('settings')->where([
                'entity' => $slug,
                'type' => 'menu',
                'user_id' => 1 // ID админа/дефолтного юзера
            ])->first();

            return $menu ? json_decode($menu->value, true) : [];
        });

        return response()->json($menuValue);
    }

    public function set_menu_role($slug, $role_id, Request $request)
    {
        $menuJson = json_encode($request->menu, JSON_UNESCAPED_UNICODE);
        
        // Получаем ID всех пользователей с этой ролью
        // ОПТИМИЗАЦИЯ: Не загружаем модели User полностью, берем только ID
        $userIds = User::where('role_id', $role_id)->pluck('id');

        // Массовое обновление настроек сложно сделать одним запросом, так как нужно updateOrInsert
        // Но мы можем ускорить кеш
        foreach ($userIds as $uid) {
             DB::table('settings')->updateOrInsert(
                ['entity' => $slug, 'type' => 'menu', 'user_id' => $uid],
                ['value' => $menuJson, 'key' => 'menu'] 
            );
            $this->updateLocalCache($uid, "entities/$slug/menu");
        }

        // Обновляем роль
        $role = Role::find($role_id);
        if ($role) {
            $menus = json_decode($role->menus, true) ?? [];
            $menus[$slug] = $request->menu;
            $role->menus = json_encode($menus);
            $role->saveQuietly();
        }

        return response()->json($request->menu);
    }

    public function set_menu_all($slug, Request $request)
    {
        // ВНИМАНИЕ: Эта операция может быть тяжелой при большом кол-ве пользователей.
        // Рекомендуется перенести в Job (очередь).
        
        $menuJson = json_encode($request->menu, JSON_UNESCAPED_UNICODE);

        // 1. Обновляем настройки для ВСЕХ пользователей, у которых уже есть эта настройка
        DB::table('settings')
            ->where('entity', $slug)
            ->where('type', 'menu')
            ->whereNotNull('user_id')
            ->update(['value' => $menuJson]);

        // 2. Обновляем "дефолтную" настройку (где user_id null)
        DB::table('settings')->updateOrInsert(
            ['entity' => $slug, 'type' => 'menu', 'user_id' => null],
            ['value' => $menuJson, 'key' => 'menu']
        );
        
        // 3. Обновляем кеш. Это всё еще самая тяжелая часть.
        // Берем ID чанками, чтобы не забить память
        User::chunk(100, function ($users) use ($slug) {
            foreach ($users as $user) {
                $this->updateLocalCache($user->id, "entities/$slug/menu");
            }
        });

        return response()->json($request->menu);
    }

    public function update(Request $request)
    {
        if (!$request->rows) {
             return response()->json(['success' => false]);
        }

        $rowIds = array_column($request->rows, 'id');
        
        // Загружаем сущности одним запросом
        $entities = DB::table('data_types')
            ->whereIntegerInRaw('id', $rowIds)
            ->get()
            ->keyBy('id');

        // ОПТИМИЗАЦИЯ: Загружаем роли ОДИН раз за пределами цикла
        $roles = Role::all();

        foreach ($request->rows as $row) {
            $id = $row['id'];
            $enable = $row['enable'];

            // Обновляем статус
            DB::table('data_types')->where('id', $id)->update(['enable' => $enable]);

            if ($enable) {
                // Работа с правами
                // Можно оптимизировать insert, собрав массив
                $permissionsToInsert = [];
                
                foreach ($roles as $role) {
                    // Проверяем существование, чтобы не дублировать
                    // Лучше использовать updateOrInsert или firstOrCreate, но для массовости:
                    $exists = Permission::where(['entity_id' => $id, 'role_id' => $role->id])->exists();
                    
                    if (!$exists) {
                        $access = ($role->id == 1) ? 'A' : 'N';
                        $permissionsToInsert[] = [
                            'role_id' => $role->id,
                            'entity_id' => $id,
                            'read_p' => $access,
                            'create_p' => $access,
                            'update_p' => $access,
                            'delete_p' => $access,
                            'export_p' => $access,
                            'import_p' => $access,
                            // created_at / updated_at если нужны
                        ];
                    }
                }

                if (!empty($permissionsToInsert)) {
                    Permission::insert($permissionsToInsert);
                }

                // Sidebar Logic
                if (isset($entities[$id]) && !$entities[$id]->hidden) {
                    SidebarItem::firstOrCreate(
                        ['slug' => $entities[$id]->slug],
                        [
                            'name' => $entities[$id]->title_plural,
                            'link' => "/objects/" . $entities[$id]->slug,
                            'enabled' => 1
                        ]
                    );
                }

            } else {
                // Отключение
                Permission::where('entity_id', $id)->delete();
                
                if (isset($entities[$id])) {
                    SidebarItem::where('slug', $entities[$id]->slug)->delete();
                }
            }
        }

        // Финальные действия
        SidebarItem::fixTree();
        
        $userId = Auth::id();
        $this->updateLocalCache($userId, 'sidebar');
        $this->updateLocalCache($userId, 'entities');

        Settings::clear_cache(); // Убедитесь, что метод статический
        
        $data = [
            'list' => DB::table('data_types')->select(['id', 'slug', 'title_singular', 'title_plural', 'color'])->get(),
            'table' => Table::entities(),
        ];

        return response()->json($data);
    }

    public function enable(Request $request)
    {
        return $this->toggleStatus($request->ids, true);
    }

    public function disable(Request $request)
    {
        return $this->toggleStatus($request->ids, false);
    }

    // Вспомогательный метод для enable/disable, чтобы не дублировать код
    private function toggleStatus($ids, $enable)
    {
        DB::table('data_types')->whereIntegerInRaw('id', $ids)->update(['enable' => $enable ? 1 : 0]);
        
        $entities = DB::table('data_types')->whereIntegerInRaw('id', $ids)->get();

        foreach ($entities as $entity) {
            if ($enable) {
                SidebarItem::firstOrCreate(
                    ['slug' => $entity->name], // В update использовался slug, тут name. Проверьте БД! Обычно slug надежнее.
                    [
                        'name' => $entity->title_plural,
                        'link' => "/objects/" . $entity->name,
                    ]
                );
            } else {
                SidebarItem::where('link', '/objects/' . $entity->name)->delete();
            }
        }

        SidebarItem::fixTree();

        $userId = Auth::id();
        $this->updateLocalCache($userId, 'sidebar');
        $this->updateLocalCache($userId, 'entities');

        if (cache()->getStore() instanceof \Illuminate\Cache\MemcachedStore) {
             cache()->getMemcached()->flush();
        }

        return response()->json(['success' => true, 'code' => 200]);
    }

    public function last_modified()
    {
        $tables = ['articles', 'faq', 'knowledge', 'guides'];
        $last_modified = [];

        foreach ($tables as $table) {
            $record = DB::table($table)
                ->select('updated_at')
                ->orderBy('updated_at', 'DESC')
                ->first();
            
            if ($record) {
                $last_modified[$table] = $record->updated_at;
            }
        }

        return response()->json($last_modified);
    }

    /**
     * Унифицированный метод обновления локального кеша
     */
    private function updateLocalCache($userId, $url)
    {
        $now = Carbon::now();
        
        DB::table('local_cache')->updateOrInsert(
            ['url' => $url, 'user_id' => $userId],
            ['updated_at' => $now, 'created_at' => $now] // created_at проигнорируется при update, но нужен при insert, Laravel updateOrInsert обрабатывает это хитро, лучше указывать timestamp явно если поля стандартные
        );
        
        // Если структура таблицы local_cache строгая и created_at не заполняется при updateOrInsert (в старых версиях):
        // Можно оставить raw query или логику: if exists update else insert. 
        // Но updateOrInsert - наиболее чистый способ в Laravel.
    }
}