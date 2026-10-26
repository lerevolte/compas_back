<?php

namespace App\Services;

use App\Models\ObjectRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransferService
{
    public const ORDER = 'transfer_orders';
    public const DOC = 'transfers';
    public const DATE_FIELD = 'transfer_date';
    public const DATE_TITLE = 'Дата перемещения';

    public static function isEntity(string $slug): bool
    {
        return $slug === self::ORDER || $slug === self::DOC;
    }

    public static function orderUsage(int $orderId): array
    {
        $result = ['id' => [], 'name' => [], 'price_id' => [], 'price_name' => []];
        if (!ObjectRelation::ready() || !Schema::hasTable(self::DOC)) {
            return $result;
        }
        $docIds = ObjectRelation::where('source_slug', self::ORDER)
            ->where('source_id', $orderId)
            ->where('target_slug', self::DOC)
            ->pluck('target_id')
            ->map(fn ($v) => (int) $v)
            ->all();
        $taskIds = ObjectRelation::where('source_slug', self::ORDER)
            ->where('source_id', $orderId)
            ->where('target_slug', 'logistic_tasks')
            ->pluck('target_id')
            ->all();
        if (count($taskIds)) {
            $docIds = array_merge($docIds, ObjectRelation::where('source_slug', 'logistic_tasks')
                ->whereIn('source_id', $taskIds)
                ->where('target_slug', self::DOC)
                ->pluck('target_id')
                ->map(fn ($v) => (int) $v)
                ->all());
        }
        $docIds = array_values(array_unique($docIds));
        if (!count($docIds)) {
            return $result;
        }
        $query = DB::table(self::DOC)->whereIn('id', $docIds);
        if (Schema::hasColumn(self::DOC, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        foreach ($query->pluck('products') as $products) {
            foreach (ShipmentService::decode($products) as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $count = (float) ($product['count'] ?? 0);
                $id = (int) ($product['id'] ?? 0);
                if ($id) {
                    $result['id'][$id] = ($result['id'][$id] ?? 0) + $count;
                }
                $name = ShipmentService::nameKey($product['name'] ?? '');
                if ($name !== '') {
                    $result['name'][$name] = ($result['name'][$name] ?? 0) + $count;
                }
            }
        }

        return $result;
    }

    public static function recalcOrder(int $orderId): void
    {
        if (!$orderId || !Schema::hasTable(self::ORDER) || !Schema::hasColumn(self::ORDER, 'shipment_status')) {
            return;
        }
        try {
            $order = \App\Models\TransferOrder::withTrashed()->find($orderId);
            if (!$order) {
                return;
            }
            ShipmentService::updateShipmentStatus(self::ORDER, $order, ShipmentService::decode($order->products), self::orderUsage($orderId));
        } catch (\Throwable $e) {
            \Log::warning('transfers: статус заказа на перемещение не обновлён', ['order_id' => $orderId, 'error' => $e->getMessage()]);
        }
    }

    public static function recalcAround(string $slug, int $id): void
    {
        if ($slug === self::ORDER) {
            self::recalcOrder($id);
            return;
        }
        if ($slug !== self::DOC || !ObjectRelation::ready()) {
            return;
        }
        $parents = ObjectRelation::whereIn('source_slug', [self::ORDER, 'logistic_tasks'])
            ->where('target_slug', self::DOC)
            ->where('target_id', $id)
            ->get(['source_slug', 'source_id']);
        foreach ($parents as $parent) {
            if ($parent->source_slug === self::ORDER) {
                self::recalcOrder((int) $parent->source_id);
            } else {
                ShipmentService::recalcForSource('logistic_tasks', (int) $parent->source_id);
            }
        }
    }
}
