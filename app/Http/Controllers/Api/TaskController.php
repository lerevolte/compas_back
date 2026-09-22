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

    public function set_payment_invoice_products($id, Request $request)
    {
        return $this->saveProductsFor('payment_invoices', \App\Models\PaymentInvoice::class, $id, $request);
    }

    public function set_expense_invoice_products($id, Request $request)
    {
        return $this->saveProductsFor('expense_invoices', \App\Models\ExpenseInvoice::class, $id, $request);
    }

    public function set_product_return_products($id, Request $request)
    {
        return $this->saveProductsFor('product_returns', \App\Models\ProductReturn::class, $id, $request);
    }

    public function set_receipt_invoice_products($id, Request $request)
    {
        return $this->saveProductsFor('receipt_invoices', \App\Models\ReceiptInvoice::class, $id, $request);
    }

    public function set_pickup_products($id, Request $request)
    {
        return $this->saveProductsFor('pickups', \App\Models\Pickup::class, $id, $request);
    }

    public function set_address_products($id, Request $request)
    {
        return $this->saveProductsFor('addresses', \App\Models\Address::class, $id, $request);
    }

    public function set_supplier_order_products($id, Request $request)
    {
        return $this->saveProductsFor('supplier_orders', \App\Models\SupplierOrder::class, $id, $request);
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
            $count = $product['product_count'] ?? null;
            if ($count === null || $count === '' || (is_numeric($count) && (float) $count <= 0)) {
                continue;
            }
            $products[] = array(
                'id' => $product['id'] ?? null,
                'name' => $product['product_name'] ?? ($product['name'] ?? ''),
                'price' => $product['product_price'] ?? null,
                'purchase_price' => $product['product_purchase_price'] ?? ($product['purchase_price'] ?? null),
                'count' => $product['product_count'] ?? null,
                'weight' => $product['product_weight'] ?? null,
                'volume' => $product['product_volume'] ?? 0,
                'sum' => $product['product_sum'] ?? null,
                'nds' => $product['product_nds'] ?? ($product['nds'] ?? null),
                'nds_included' => $product['product_nds_included'] ?? ($product['nds_included'] ?? null),
            );
        }
        $errors = \App\Services\ShipmentService::withFamilyLock($slug, (int) $id, function () use ($class, $slug, $id, $products) {
            $object = $class::find($id);
            if (!$object) {
                return null;
            }
            $errors = array_merge(
                \App\Services\ShipmentService::validateAgainstParent($slug, (int) $id, $products),
                \App\Services\ShipmentService::validateAgainstChildren($slug, (int) $id, $products)
            );
            if (count($errors)) {
                return $errors;
            }
            $object->setProducts($products);
            if (\App\Services\ShipmentService::isSource($slug)) {
                \App\Services\ShipmentService::recalcForSource($slug, (int) $id);
            }
            if ($slug === 'deals') {
                \App\Services\ShipmentService::recalcDealShipped((int) $id);
            }
            if ($slug === \App\Services\ShipmentService::SUPPLIER) {
                \App\Services\ShipmentService::recalcSupplierReceived((int) $id);
            }

            return [];
        });
        if ($errors === null) {
            return response()->json(['error' => 404, 'text' => 'Задача не найдена'], 404);
        }
        if (count($errors)) {
            return response()->json([
                'message' => 'Расхождение по составу со связанными документами — сохранение запрещено',
                'errors' => $errors,
            ], 422);
        }

        $ids = array_values(array_filter(array_map(function ($p) { return (int) $p['id']; }, $products)));
        if (count($ids)) {
            \DB::table('products')->whereIntegerInRaw('id', $ids)->update(['choosed_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}