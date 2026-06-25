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
            ->get();

        // Карта статусов (field_values) и пользователей (для отображения).
        $statusIds = $tasks->pluck('point_status')->filter()->unique()->values()->all();
        $statuses = $statusIds
            ? DB::table('field_values')->whereIn('id', $statusIds)->get()->keyBy('id')
            : collect();

        $userIds = $tasks->pluck('user_id')->filter()->unique()->values()->all();
        $users = $userIds
            ? DB::table('users')->whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $result = [];
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
            if (!$has) {
                continue;
            }

            $status = $statuses[$task->point_status] ?? null;
            $user = $users[$task->user_id] ?? null;

            $result[] = [
                'id'            => $task->id,
                'status'        => $status ? ($status->value ?? '') : '',
                'status_color'  => $status ? ($status->color ?? '#ccc') : '#ccc',
                'name'          => $this->taskName($task),
                'products_html' => $task->getHtmlProducts(),
                'product_qty'   => $qty,
                'responsible'   => $user ? ($user->name ?? '') : '',
                'comment'       => is_string($task->comment) ? $task->comment : '',
            ];
        }

        return response()->json($result);
    }
}
