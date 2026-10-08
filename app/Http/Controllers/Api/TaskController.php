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

    public function set_specification_products($id, Request $request)
    {
        return $this->saveProductsFor('specifications', \App\Models\Specification::class, $id, $request);
    }

    public function set_supplier_order_products($id, Request $request)
    {
        return $this->saveProductsFor('supplier_orders', \App\Models\SupplierOrder::class, $id, $request);
    }

    public function set_production_order_products($id, Request $request)
    {
        return $this->saveProductsFor(\App\Services\ProductionService::ORDER, \App\Models\ProductionOrder::class, $id, $request);
    }

    public function set_production_products($id, Request $request)
    {
        return $this->saveProductsFor(\App\Services\ProductionService::DOC, \App\Models\Production::class, $id, $request);
    }

    public function set_production_order_materials($id, Request $request)
    {
        return $this->saveMaterialsFor(\App\Services\ProductionService::ORDER, $id, $request);
    }

    public function set_production_materials($id, Request $request)
    {
        return $this->saveMaterialsFor(\App\Services\ProductionService::DOC, $id, $request);
    }

    public function fill_production_materials($slug, $id)
    {
        if (!\App\Services\ProductionService::isEntity($slug)) {
            return response()->json(['message' => 'Not found'], 404);
        }
        if (!$this->canWriteComposition($slug, \App\Services\ProductionService::MATERIALS_FIELD)) {
            return response()->json(['message' => 'Нет прав на изменение материалов'], 403);
        }
        $lines = \App\Services\ProductionService::previewMaterials($slug, (int) $id);
        if ($lines === null) {
            return response()->json(['message' => 'Не найдено'], 404);
        }
        $rows = count($lines) ? \App\Models\EntityObject::list('products', new Request([
            'order_id' => (int) $id,
            'order_entity' => \App\Services\ProductionService::materialsSlug($slug),
            'products_override' => $lines,
        ])) : [];

        return response()->json(['success' => true, 'count' => count($lines), 'rows' => $rows['data'] ?? []]);
    }

    private function canWriteComposition(string $slug, string $field): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }
        $settings = app('settings');
        $perms = $settings[$slug]['perms'][$field] ?? null;

        return !$perms || ($perms['read'] && $perms['write']);
    }

    private function saveMaterialsFor(string $slug, $id, Request $request)
    {
        if (!$this->canWriteComposition($slug, \App\Services\ProductionService::MATERIALS_FIELD)) {
            return response()->json(['message' => 'Нет прав на изменение материалов'], 403);
        }
        $class = \App\Services\ProductionService::MODELS[$slug];
        $materials = $this->compositionLines($slug, (array) ($request->products ?? []));
        $errors = \App\Services\ShipmentService::withFamilyLock($slug, (int) $id, function () use ($class, $slug, $id, $materials) {
            $object = $class::find($id);
            if (!$object) {
                return null;
            }
            $errors = \App\Services\ProductionService::materialErrors($slug, (int) $id, $materials);
            if (count($errors)) {
                return $errors;
            }
            $object->setMaterials($materials);

            return [];
        });
        if ($errors === null) {
            return response()->json(['error' => 404, 'text' => 'Не найдено'], 404);
        }
        if (count($errors)) {
            return response()->json([
                'message' => 'Материалы не соответствуют спецификациям продукции — сохранение запрещено',
                'errors' => $errors,
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    private function compositionLines($slug, array $rows): array
    {
        $products = array();
        foreach ($rows as $product) {
            $count = $product['product_count'] ?? null;
            if (is_string($count)) {
                $count = str_replace(',', '.', trim($count));
            }
            if ($count === null || $count === '' || (is_numeric($count) && (float) $count <= 0)) {
                continue;
            }
            $line = array(
                'id' => $product['id'] ?? null,
                'name' => $product['product_name'] ?? ($product['name'] ?? ''),
                'price' => $product['product_price'] ?? null,
                'purchase_price' => $product['product_purchase_price'] ?? ($product['purchase_price'] ?? null),
                'count' => $count,
                'weight' => $product['product_weight'] ?? null,
                'volume' => $product['product_volume'] ?? 0,
                'sum' => $product['product_sum'] ?? null,
                'nds' => $product['product_nds'] ?? ($product['nds'] ?? null),
                'nds_included' => $product['product_nds_included'] ?? ($product['nds_included'] ?? null),
            );
            $outputCount = $product['product_output_count'] ?? null;
            if ($outputCount !== null && $outputCount !== '') {
                $line['output_count'] = is_string($outputCount) ? str_replace(',', '.', trim($outputCount)) : $outputCount;
            }
            if (\App\Services\ProductionService::isEntity((string) $slug)) {
                $specificationId = \App\Services\ProductionService::specificationId($product[\App\Services\ProductionService::SPEC_KEY] ?? null);
                if ($specificationId) {
                    $line['specification_id'] = $specificationId;
                }
            }
            $parts = \App\Services\ShipmentService::lineParts($line, \App\Services\ShipmentService::linePriceKey((string) $slug));
            $line['sum'] = round($parts['net'], 2);
            $line['nds_sum'] = round($parts['vat'], 2);
            $line['total'] = round($parts['gross'], 2);
            $products[] = $line;
        }

        return $products;
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
        $products = $this->compositionLines($slug, (array) ($request->products ?? []));
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
            if ($slug === \App\Services\ProductionService::ORDER) {
                \App\Services\ProductionService::recalcOrder((int) $id);
            }
            if ($slug === \App\Services\ProductionService::DOC) {
                \App\Services\ProductionService::recalcForDocument((int) $id);
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