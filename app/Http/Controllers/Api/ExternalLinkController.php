<?
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalLink;
use App\Models\EntityObject;
use App\Models\Field;
use App\Models\Filter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ExternalLinkController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'model_slug' => 'required|string',
            'model_id' => 'required|integer',
            'expires_at' => 'nullable|date',
            'max_visits' => 'nullable|integer|min:1',
        ]);

        $link = ExternalLink::create([
            'token' => ExternalLink::generateToken(),
            'model_slug' => $request->model_slug,
            'model_id' => $request->model_id,
            'expires_at' => $request->expires_at,
            'max_visits' => $request->max_visits,
        ]);

        return response()->json([
            'url' => "/api/external/{$link->token}",
            'token' => $link->token,
            'expires_at' => $link->expires_at,
            'max_visits' => $link->max_visits,
        ]);
    }

    public function show($token, ObjectController $objectController, Request $request)
    {
        $link = ExternalLink::where('token', $token)->firstOrFail();

        if (!$link->isValid()) {
            abort(404, 'Link is expired or no longer available');
        }

        $link->incrementVisits();

        return $objectController->compose_show($link->model_slug, $link->model_id, new Request());
    }

    /**
     * Таблица привязанной сущности для внешней ссылки (без авторизации).
     * Безопасность: объём строк ограничивается СЕРВЕРОМ — берём id связанных
     * с родителем строк (как в compose_show/detail), клиентский filter для
     * ограничения не используем. $slug должен быть реальной привязанной
     * вкладкой родителя.
     */
    public function table($token, $slug, Request $request)
    {
        $link = ExternalLink::where('token', $token)->firstOrFail();
        if (!$link->isValid()) {
            abort(404, 'Link is expired or no longer available');
        }

        $settings = app('settings');
        $parentSlug = $link->model_slug;
        $parentId   = $link->model_id;

        // 1. $slug должен быть привязанной (plural relation) вкладкой родителя.
        //    Особый случай: задачи маршрута (routes -> logistic_tasks по route_id).
        $isRouteTasks = ($parentSlug === 'routes' && $slug === 'logistic_tasks');
        $isValidTab = false;
        foreach (($settings[$parentSlug]['fields'] ?? []) as $field) {
            if ($field->type === 'relation' && $field->is_plural && $field->relation_table === $slug) {
                $isValidTab = true;
                break;
            }
        }
        if (!$isValidTab && !$isRouteTasks) {
            abort(404, 'Tab is not available for this link');
        }

        // 2. Серверный scoping: id связанных строк родителя.
        $allowedIds = $this->relatedIds($settings, $parentSlug, $parentId, $slug);

        $scoped = new Request();
        if ($allowedIds !== null) {
            // Пустой набор -> заведомо пустой результат, но в форме таблицы.
            $scoped->merge(['ids' => $allowedIds ?: [-1]]);
        } elseif ($isRouteTasks) {
            $scoped->merge(['filter' => ['route_id' => $parentId]]);
        } else {
            abort(404, 'Relation cannot be resolved');
        }

        if ($request->page)     $scoped->merge(['page' => $request->page]);
        if ($request->per_page) $scoped->merge(['per_page' => $request->per_page]);

        // 3. Внутренние методы (Table::get/Field::list/EntityObject::list) требуют
        //    \Auth::user(). Назначаем системного админа на время запроса — объём
        //    данных уже ограничен whitelist'ом id, права админа на это не влияют.
        $this->actAsSystemUser();

        $list = EntityObject::list($slug, $scoped);
        if (isset($list['error'])) {
            return response()->json(['message' => $list['error']['message']], $list['error']['code']);
        }

        // Поля с ограничением видимости по ролям (roles_read) во внешней ссылке
        // показывать нельзя: внешний зритель анонимен (ролей нет), а запрос
        // выполняется от имени админа (actAsSystemUser), который иначе видит всё.
        // Поэтому вырезаем такие поля из колонок, схемы полей и значений строк.
        $restricted = $this->restrictedFields($slug);

        $table = array_values(array_filter(
            \App\Models\Table::get($slug),
            fn ($col) => !in_array($col['key'] ?? null, $restricted, true)
        ));

        $fields = Field::list($slug);
        foreach ($restricted as $rf) {
            unset($fields[$rf]);
        }

        if (!empty($restricted) && isset($list['data']) && is_array($list['data'])) {
            $list['data'] = array_map(function ($row) use ($restricted) {
                $row = (array) $row;
                foreach ($restricted as $rf) {
                    unset($row[$rf]);
                }
                return $row;
            }, $list['data']);
        }

        // Задачи маршрута выводим в порядке маршрута (по полю sort), а не в
        // порядке по умолчанию (id) — иначе во внешней ссылке порядок точек не
        // совпадал с маршрутом (8579). Сортируем по последовательности id из
        // relation tasks() (она уже orderBy('sort')).
        if ($isRouteTasks && isset($list['data']) && is_array($list['data'])) {
            $orderedIds = \App\Models\Route::find($parentId)?->tasks()->pluck('id')->toArray() ?? [];
            if (!empty($orderedIds)) {
                $pos = array_flip($orderedIds);
                $data = $list['data'];
                usort($data, function ($a, $b) use ($pos) {
                    $a = (array) $a;
                    $b = (array) $b;
                    return ($pos[$a['id'] ?? null] ?? PHP_INT_MAX) <=> ($pos[$b['id'] ?? null] ?? PHP_INT_MAX);
                });
                $list['data'] = array_values($data);
            }
        }

        return response()->json([
            'list'        => $list,
            'table'       => $table,
            'fields'      => $fields,
            'entities'    => DB::table('data_types')->select(['slug', 'title_singular', 'title_plural', 'color'])->get(),
            'filters'     => Filter::list($slug),
            'categories'  => [],
            'permissions' => ['read_p' => 'A'],
            'tabs'        => [],
            // Настройки полей «Маршрут списком» — те же, что внутри портала,
            // чтобы внешняя ссылка показывала идентичный набор/порядок колонок (8579).
            'route_tasks_view' => $isRouteTasks ? \App\Http\Controllers\Api\RouteController::getTasksViewFields() : [],
        ]);
    }

    /**
     * Имена полей сущности с непустым roles_read (ограничение видимости по
     * ролям). Во внешней ссылке такие поля не отдаём.
     */
    private function restrictedFields(string $slug): array
    {
        $type = DB::table('data_types')
            ->where('slug', $slug)->orWhere('name', $slug)
            ->first();
        if (!$type) {
            return [];
        }

        return DB::table('data_rows')
            ->where('data_type_id', $type->id)
            ->whereNotNull('roles_read')
            ->whereNotIn('roles_read', ['', '[]', '0'])
            ->pluck('field')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * id строк дочерней сущности, связанных с родителем через его plural relation
     * (та же логика, что в EntityObject::detail). null — связь не разрешилась.
     */
    private function relatedIds($settings, $parentSlug, $parentId, $childSlug)
    {
        $entity = $settings['models'][$parentSlug] ?? null;
        if (!$entity) {
            return null;
        }
        $class = $entity->model_name;
        $parent = $class::withTrashed()->where('id', $parentId)->first();
        if (!$parent) {
            return [];
        }
        try {
            // childSlug == relation_table == имя relation на родителе.
            $relation = $parent->{$childSlug};
            if ($relation !== null) {
                return collect($relation)->pluck('id')->map(fn ($i) => (int) $i)->values()->all();
            }
        } catch (\Throwable $e) {
            // нет такой relation — разрешим другим веткам (route_id и т.п.)
        }
        return null;
    }

    private function actAsSystemUser(): void
    {
        $admin = \App\Models\User::where('is_admin', true)->first() ?: \App\Models\User::find(1);
        if ($admin) {
            Auth::setUser($admin);
        }
    }

    public function revoke($token)
    {
        $link = ExternalLink::where('token', $token)->firstOrFail();
        $link->update(['is_active' => false]);

        return response()->json(['message' => 'Link has been revoked']);
    }

    public function list(Request $request)
    {
        $query = ExternalLink::query();

        if ($request->model_slug) {
            $query->where('model_slug', $request->model_slug);
        }

        if ($request->model_id) {
            $query->where('model_id', $request->model_id);
        }

        return response()->json($query->paginate());
    }
}