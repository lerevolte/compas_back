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
        $products = array();
        foreach ($request->products as $product) {
            $products[] = array(
                'id' => $product['id'],
                'name' => $product['product_name'],
                'price' => $product['product_price'],
                'count' => $product['product_count'],
                'weight' => $product['product_weight'],
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