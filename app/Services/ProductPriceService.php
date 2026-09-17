<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductPriceService
{
    public const DOCUMENT_SLUGS = ['logistic_tasks', 'pickups'];
    public const LIMIT = 30;

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('products') && Schema::hasColumn('products', 'price');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function productIds(...$productsJsons): array
    {
        $ids = [];
        foreach ($productsJsons as $raw) {
            foreach (ShipmentService::decode($raw) as $product) {
                if (is_array($product) && is_numeric($product['id'] ?? null) && (int) $product['id'] > 0) {
                    $ids[(int) $product['id']] = true;
                }
            }
        }

        return array_keys($ids);
    }

    public static function recalcFromChange($newProducts, $oldProducts = null): void
    {
        try {
            $ids = self::productIds($newProducts, $oldProducts);
            if (count($ids)) {
                self::recalc($ids);
            }
        } catch (\Throwable $e) {
            \Log::warning('product sale price recalc failed: ' . $e->getMessage());
        }
    }

    public static function recalc(array $productIds): int
    {
        if (!self::ready()) {
            return 0;
        }
        $updated = 0;
        foreach (array_unique(array_map('intval', $productIds)) as $productId) {
            if ($productId <= 0) {
                continue;
            }
            $average = self::averagePrice($productId);
            if ($average === null) {
                continue;
            }
            $formatted = rtrim(rtrim(number_format($average, 2, '.', ''), '0'), '.');
            $updated += DB::table('products')
                ->where('id', $productId)
                ->where(fn ($q) => $q->whereNull('price')->orWhereRaw('CAST(price AS DECIMAL(20,2)) <> CAST(? AS DECIMAL(20,2))', [$formatted]))
                ->update(['price' => $formatted]);
        }

        return $updated;
    }

    public static function averagePrice(int $productId): ?float
    {
        $entries = [];
        foreach (self::DOCUMENT_SLUGS as $slug) {
            if (!Schema::hasTable($slug) || !Schema::hasColumn($slug, 'products')) {
                continue;
            }
            $query = DB::table($slug)
                ->where(function ($q) use ($productId) {
                    $q->where('products', 'like', '%"id":' . $productId . ',%')
                        ->orWhere('products', 'like', '%"id":' . $productId . '}%')
                        ->orWhere('products', 'like', '%"id":"' . $productId . '"%');
                })
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::LIMIT);
            if (Schema::hasColumn($slug, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            foreach ($query->get(['id', 'created_at', 'products']) as $row) {
                $price = self::linePrice($row->products, $productId);
                if ($price !== null) {
                    $entries[] = ['at' => (string) $row->created_at, 'price' => $price];
                }
            }
        }
        if (!count($entries)) {
            return null;
        }
        usort($entries, fn ($a, $b) => strcmp($b['at'], $a['at']));
        $entries = array_slice($entries, 0, self::LIMIT);

        return array_sum(array_column($entries, 'price')) / count($entries);
    }

    private static function linePrice($raw, int $productId): ?float
    {
        $prices = [];
        foreach (ShipmentService::decode($raw) as $product) {
            if (!is_array($product) || (int) ($product['id'] ?? 0) !== $productId) {
                continue;
            }
            $price = $product['price'] ?? null;
            if ($price === null || $price === '' || !is_numeric($price)) {
                continue;
            }
            $prices[] = (float) $price;
        }

        return count($prices) ? array_sum($prices) / count($prices) : null;
    }
}
