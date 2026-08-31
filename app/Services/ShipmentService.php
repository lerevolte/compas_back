<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\ExpenseInvoice;
use App\Models\ObjectRelation;
use Illuminate\Support\Facades\Schema;

class ShipmentService
{
    public const SOURCE = 'deals';
    public const DOCUMENT = 'expense_invoices';

    public static function ready(): bool
    {
        try {
            return ObjectRelation::ready()
                && Schema::hasTable(self::SOURCE) && Schema::hasColumn(self::SOURCE, 'products')
                && Schema::hasTable(self::DOCUMENT) && Schema::hasColumn(self::DOCUMENT, 'products');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function dealIdFor(int $documentId): ?int
    {
        if (!self::ready()) {
            return null;
        }
        $dealId = ObjectRelation::where('source_slug', self::SOURCE)
            ->where('target_slug', self::DOCUMENT)
            ->where('target_id', $documentId)
            ->orderBy('id')
            ->value('source_id');

        return $dealId ? (int) $dealId : null;
    }

    public static function recalcForDocument(int $documentId): bool
    {
        $dealId = self::dealIdFor($documentId);

        return $dealId ? self::recalcForDeal($dealId) : false;
    }

    public static function recalcForDeal(int $dealId): bool
    {
        if (!self::ready()) {
            return false;
        }

        try {
            $deal = Deal::withTrashed()->find($dealId);
            if (!$deal) {
                return false;
            }
            $products = self::decode($deal->products);
            if (!count($products)) {
                return false;
            }

            $shipped = self::shippedByDeal($dealId);
            $changed = false;
            foreach ($products as $i => $product) {
                if (!is_array($product)) {
                    continue;
                }
                $value = self::lookup($shipped, $product);
                if (!array_key_exists('shipped', $product) && $value == 0) {
                    continue;
                }
                if (abs((float) ($product['shipped'] ?? 0) - $value) > 0.0001 || !array_key_exists('shipped', $product)) {
                    $products[$i]['shipped'] = $value == (int) $value ? (int) $value : $value;
                    $changed = true;
                }
            }
            if (!$changed) {
                return false;
            }

            $deal->products = json_encode($products, JSON_UNESCAPED_UNICODE);
            $deal->timestamps = false;
            $deal->saveQuietly();
            $deal->timestamps = true;

            try {
                \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $deal->getData(['products']));
            } catch (\Throwable $e) {
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function shippedByDeal(int $dealId): array
    {
        $result = ['id' => [], 'name' => []];

        $documentIds = ObjectRelation::where('source_slug', self::SOURCE)
            ->where('source_id', $dealId)
            ->where('target_slug', self::DOCUMENT)
            ->pluck('target_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if (!count($documentIds)) {
            return $result;
        }

        foreach (ExpenseInvoice::whereIn('id', $documentIds)->get(['id', 'products']) as $document) {
            foreach (self::decode($document->products) as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $count = (float) ($product['count'] ?? 0);
                if ($count <= 0) {
                    continue;
                }
                $id = (int) ($product['id'] ?? 0);
                if ($id) {
                    $result['id'][$id] = ($result['id'][$id] ?? 0) + $count;
                }
                $name = self::nameKey($product['name'] ?? '');
                if ($name !== '') {
                    $result['name'][$name] = ($result['name'][$name] ?? 0) + $count;
                }
            }
        }

        return $result;
    }

    public static function carryShipped($current, array $products): array
    {
        $shipped = ['id' => [], 'name' => []];
        foreach (self::decode($current) as $product) {
            if (!is_array($product) || !array_key_exists('shipped', $product)) {
                continue;
            }
            $id = (int) ($product['id'] ?? 0);
            if ($id) {
                $shipped['id'][$id] = (float) $product['shipped'];
            }
            $name = self::nameKey($product['name'] ?? '');
            if ($name !== '') {
                $shipped['name'][$name] = (float) $product['shipped'];
            }
        }
        if (!count($shipped['id']) && !count($shipped['name'])) {
            return $products;
        }
        foreach ($products as $i => $product) {
            if (!is_array($product)) {
                continue;
            }
            $value = self::lookup($shipped, $product);
            if ($value != 0) {
                $products[$i]['shipped'] = $value == (int) $value ? (int) $value : $value;
            }
        }

        return $products;
    }

    public static function decode($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    public static function plainName($name): string
    {
        if (is_array($name)) {
            $name = $name['value'] ?? ($name['text'] ?? (reset($name) ?: ''));
        }

        return trim((string) $name);
    }

    private static function lookup(array $shipped, array $product): float
    {
        $id = (int) ($product['id'] ?? 0);
        if ($id && isset($shipped['id'][$id])) {
            return (float) $shipped['id'][$id];
        }
        $name = self::nameKey($product['name'] ?? '');
        if ($name !== '' && isset($shipped['name'][$name])) {
            return (float) $shipped['name'][$name];
        }

        return 0.0;
    }

    private static function nameKey($name): string
    {
        return mb_strtolower(self::plainName($name));
    }
}
