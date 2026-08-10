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
        return $this->saveProductsFor('logistic_tasks', Task::class, $id, $request);
    }

    public function set_deal_products($id, Request $request)
    {
        return $this->saveProductsFor('deals', \App\Models\Deal::class, $id, $request);
    }

    private function saveProductsFor($slug, $class, $id, Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->is_admin) {
            $settings = app('settings');
            $perms = $settings[$slug]['perms']['products'] ?? null;
            if (!$user || ($perms && (!$perms['read'] || !$perms['write']))) {
                return response()->json(['message' => 'Нет прав на изменение состава'], 403);
            }
        }
        $products = array();
        foreach ($request->products as $product) {
            $products[] = array(
                'id' => $product['id'] ?? null,
                'name' => $product['product_name'] ?? ($product['name'] ?? ''),
                'price' => $product['product_price'] ?? null,
                'count' => $product['product_count'] ?? null,
                'weight' => $product['product_weight'] ?? null,
                'volume' => $product['product_volume'] ?? 0,
                'sum' => $product['product_sum'] ?? null,
            );
        }
        $object = $class::find($id);
        if(!$object) {
            return response()->json(['error' => 404, 'text' => 'Задача не найдена']);
        }
        $object->setProducts($products);

        $ids = array_values(array_filter(array_map(function ($p) { return (int) $p['id']; }, $products)));
        if (count($ids)) {
            \DB::table('products')->whereIntegerInRaw('id', $ids)->update(['choosed_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}