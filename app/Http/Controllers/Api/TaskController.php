<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use App\Models\Task;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class TaskController extends Controller
{
    public function set_products($id, Request $request)
    {
        info('set produts');
        info($id);
        info($request->products);
        $user = Auth::user();
        if (!$user || !$user->is_admin) {
            $settings = app('settings');
            $perms = $settings['logistic_tasks']['perms']['products'] ?? null;
            if (!$user || ($perms && (!$perms['read'] || !$perms['write']))) {
                return response()->json(['message' => 'Нет прав на изменение состава'], 403);
            }
        }
        $products = array();
        foreach ($request->products as $product) {
            $products[] = array(
                'id' => $product['id'],
                'name' => $product['product_name'],
                'price' => $product['product_price'],
                'count' => $product['product_count'],
                'weight' => $product['product_weight'],
                'volume' => $product['product_volume'] ?? 0,
                'sum' => $product['product_sum'],
            );
        }
        $task = Task::find($id);
        if(!$task) {
            return response()->json(['error' => 404, 'text' => 'Задача не найдена']);
        }
        $task->setProducts($products);

        return response()->json(['success' => true]);
    }
}