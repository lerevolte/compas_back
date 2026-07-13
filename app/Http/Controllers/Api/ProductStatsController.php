<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Task;

/**
 * Статистика по товарам в логистике: агрегация товаров из задач логистики
 * (logistic_tasks.products — JSON [{name, count, ...}]) за дату доставки.
 */
class ProductStatsController extends Controller
{
    private function productName($p): string
    {
        $name = $p['name'] ?? null;
        if (is_array($name)) {
            $name = $name[0] ?? '';
        }
        return is_string($name) ? trim($name) : '';
    }

    private function taskName(Task $task): string
    {
        $name = $task->name;
        if (is_string($name)) {
            $decoded = json_decode($name, true);
            if (is_array($decoded) && array_key_exists('value', $decoded)) {
                return (string) ($decoded['value'] ?? '');
            }
        }
        return is_string($name) ? $name : '';
    }

    public function products(Request $request)
    {
        $date = $request->delivery_date;
        if (!$date) {
            return response()->json(['products' => [], 'all_order_count' => 0, 'all_total_count' => 0]);
        }

        $tasks = Task::where('delivery_date', $date)
            ->whereNull('deleted_at')
            ->get(['id', 'products']);

        $allOrderCount = $tasks->count();
        $allTotalCount = 0;
        $stats = [];
        foreach ($tasks as $task) {
            $products = json_decode($task->products, true);
            if (!is_array($products)) {
                continue;
            }
            $seen = [];
            foreach ($products as $p) {
                $name = $this->productName($p);
                if ($name === '') {
                    continue;
                }
                if (!isset($stats[$name])) {
                    $stats[$name] = ['name' => $name, 'order_count' => 0, 'total_count' => 0];
                }
                $stats[$name]['total_count'] += (float) ($p['count'] ?? 0);
                $allTotalCount += (float) ($p['count'] ?? 0);
                if (!isset($seen[$name])) {
                    $stats[$name]['order_count']++;
                    $seen[$name] = true;
                }
            }
        }

        $result = array_values($stats);
        usort($result, fn ($a, $b) => $b['total_count'] <=> $a['total_count']);

        return response()->json([
            'products' => $result,
            'all_order_count' => $allOrderCount,
            'all_total_count' => $allTotalCount,
        ]);
    }

    public function tasks(Request $request)
    {
        $date = $request->delivery_date;
        $product = $request->product;
        $all = filter_var($request->all, FILTER_VALIDATE_BOOLEAN);
        if (!$date || (!$all && ($product === null || $product === ''))) {
            return response()->json([]);
        }

        $query = Task::where('delivery_date', $date)
            ->whereNull('deleted_at')
            ->orderBy('id');
        if (!$all) {
            $query->whereNotNull('products');
        }
        $tasks = $query->get(['id', 'products']);

        $qtyById = [];
        foreach ($tasks as $task) {
            $products = json_decode($task->products, true);
            $qty = 0;
            $has = $all;
            if (is_array($products)) {
                foreach ($products as $p) {
                    if ($all || $this->productName($p) === $product) {
                        $has = true;
                        $qty += (float) ($p['count'] ?? 0);
                    }
                }
            }
            if ($has) {
                $qtyById[$task->id] = $qty;
            }
        }

        $ids = array_keys($qtyById);
        if (empty($ids)) {
            return response()->json(['list' => ['data' => []], 'table' => $this->buildColumns()]);
        }

        // Форматированные строки задач логистики — как на странице объектов.
        $listed = \App\Models\EntityObject::list('logistic_tasks', new Request(['ids' => $ids, 'per_page' => count($ids)]));
        $rows = $listed['data'] ?? [];
        foreach ($rows as &$row) {
            $row['product_qty'] = $qtyById[$row['id']] ?? 0;
        }
        unset($row);
        $rows = array_values($rows);

        $sortField = $listed['sort_field'] ?? null;
        $sortOrder = $listed['sort_order'] ?? null;

        $tables = \Auth::user() && \Auth::user()->tables ? json_decode(\Auth::user()->tables, true) : null;
        if (($tables['logistic_tasks']['sort_field'] ?? null) === 'product_qty') {
            $savedOrder = strtolower((string) ($tables['logistic_tasks']['sort_order'] ?? 'asc'));
            $savedOrder = in_array($savedOrder, ['asc', 'desc'], true) ? $savedOrder : 'asc';
            usort($rows, fn ($a, $b) => $savedOrder === 'asc'
                ? ($a['product_qty'] <=> $b['product_qty'])
                : ($b['product_qty'] <=> $a['product_qty']));
            $sortField = 'product_qty';
            $sortOrder = $savedOrder;
        }

        return response()->json([
            'list'  => [
                'data'       => $rows,
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
            ],
            'table' => $this->buildColumns(),
        ]);
    }

    /**
     * Заголовок нижней таблицы. Берём ПОЛНЫЙ набор столбцов задачи логистики
     * из Table::get — те же типы/опции/настройки и редактируемость, что и на
     * /objects/logistic_tasks (8557). Дополнительно добавляем служебный столбец
     * «Кол-во, шт» выбранного товара (только для чтения).
     */
    private function buildColumns(): array
    {
        $columns = collect(\App\Models\Table::get('logistic_tasks'))
            ->map(fn ($col) => (array) $col)
            ->values()
            ->all();

        $qtyColumn = [
            'id'         => 'product_qty',
            'title'      => 'Кол-во, шт',
            'key'        => 'product_qty',
            'width'      => '120px',
            'enabled'    => true,
            'sort_order' => '',
            'type'       => 'number',
            'is_plural'  => 0,
            'is_link'    => 0,
            'required'   => 0,
            'fixed'      => '',
            'fixTarget'  => '0px',
            'read_only'  => 1,
            'unit'       => '',
            'mask'       => null,
            'is_hidden'  => 0,
            'options'    => [],
        ];

        // Вставляем «Кол-во, шт» перед служебным столбцом действий (actions),
        // если он есть, иначе в конец.
        $actionsIndex = null;
        foreach ($columns as $i => $col) {
            if (($col['key'] ?? null) === 'actions') {
                $actionsIndex = $i;
                break;
            }
        }
        if ($actionsIndex !== null) {
            array_splice($columns, $actionsIndex, 0, [$qtyColumn]);
        } else {
            $columns[] = $qtyColumn;
        }

        return $columns;
    }
}
