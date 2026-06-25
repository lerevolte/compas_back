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

    // Сводка: товар, в скольких заказах встречается, суммарное количество.
    public function products(Request $request)
    {
        $date = $request->delivery_date;
        if (!$date) {
            return response()->json([]);
        }

        $tasks = Task::where('delivery_date', $date)
            ->whereNull('deleted_at')
            ->whereNotNull('products')
            ->get(['id', 'products']);

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
                if (!isset($seen[$name])) {
                    $stats[$name]['order_count']++;
                    $seen[$name] = true;
                }
            }
        }

        $result = array_values($stats);
        usort($result, fn ($a, $b) => $b['total_count'] <=> $a['total_count']);

        return response()->json($result);
    }

    // Задачи логистики, содержащие выбранный товар на дату доставки.
    public function tasks(Request $request)
    {
        $date = $request->delivery_date;
        $product = $request->product;
        if (!$date || $product === null || $product === '') {
            return response()->json([]);
        }

        $tasks = Task::where('delivery_date', $date)
            ->whereNull('deleted_at')
            ->whereNotNull('products')
            ->orderBy('id')
            ->get(['id', 'products']);

        $qtyById = [];
        foreach ($tasks as $task) {
            $products = json_decode($task->products, true);
            if (!is_array($products)) {
                continue;
            }
            $qty = 0;
            $has = false;
            foreach ($products as $p) {
                if ($this->productName($p) === $product) {
                    $has = true;
                    $qty += (float) ($p['count'] ?? 0);
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

        return response()->json([
            'list'  => ['data' => array_values($rows)],
            'table' => $this->buildColumns(),
        ]);
    }

    /**
     * Заголовок нижней таблицы: нужные поля задачи логистики (взятые из
     * Table::get, чтобы тип/опции совпадали со страницей объектов) + столбец
     * «Кол-во, шт» выбранного товара.
     */
    private function buildColumns(): array
    {
        $all = collect(\App\Models\Table::get('logistic_tasks'))->keyBy('key');

        $pick = function ($key, $title) use ($all) {
            $col = $all->get($key);
            if (!$col) {
                return null;
            }
            $col = (array) $col;
            $col['title'] = $title;
            $col['enabled'] = true;
            $col['read_only'] = 1;
            $col['fixed'] = '';
            return $col;
        };

        $productsColumn = [
            'id'         => 'products',
            'title'      => 'Состав заказа',
            'key'        => 'products',
            'width'      => '260px',
            'enabled'    => true,
            'sort_order' => '',
            'type'       => 'json',
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

        $columns = array_filter([
            $pick('point_status', 'Статус задачи'),
            $pick('name', 'Название задачи'),
            $productsColumn,
            $qtyColumn,
            $pick('user_id', 'Ответственный'),
            $pick('comment', 'Примечание'),
        ]);

        return array_values($columns);
    }
}
