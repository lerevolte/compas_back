<?php

namespace App\Services;

use App\Models\ExpenseInvoice;
use App\Models\ObjectRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShipmentService
{
    public const SOURCES = ['logistic_tasks', 'pickups'];
    public const DOCUMENT = 'expense_invoices';

    public static function ready(): bool
    {
        try {
            return ObjectRelation::ready()
                && Schema::hasTable(self::DOCUMENT) && Schema::hasColumn(self::DOCUMENT, 'products');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function isSource(string $slug): bool
    {
        return in_array($slug, self::SOURCES, true);
    }

    public static function sourceFor(int $documentId): ?array
    {
        if (!self::ready()) {
            return null;
        }
        $relation = ObjectRelation::whereIn('source_slug', self::SOURCES)
            ->where('target_slug', self::DOCUMENT)
            ->where('target_id', $documentId)
            ->orderBy('id')
            ->first(['source_slug', 'source_id']);

        return $relation ? [(string) $relation->source_slug, (int) $relation->source_id] : null;
    }

    public static function recalcForDocument(int $documentId): bool
    {
        $source = self::sourceFor($documentId);

        return $source ? self::recalcForSource($source[0], $source[1]) : false;
    }

    public static function recalcForSource(string $slug, int $id): bool
    {
        if (!self::ready() || !self::isSource($slug) || !Schema::hasTable($slug) || !Schema::hasColumn($slug, 'products')) {
            return false;
        }

        try {
            $class = self::modelClass($slug);
            $object = $class ? $class::withTrashed()->find($id) : null;
            if (!$object) {
                return false;
            }
            $products = self::decode($object->products);
            if (!count($products)) {
                return false;
            }

            $shipped = self::shippedBySource($slug, $id);
            $changed = false;
            foreach ($products as $i => $product) {
                if (!is_array($product)) {
                    continue;
                }
                $value = self::lookup($shipped, $product);
                if (!array_key_exists('shipped', $product) && $value == 0) {
                    continue;
                }
                if (!array_key_exists('shipped', $product) || abs((float) ($product['shipped'] ?? 0) - $value) > 0.0001) {
                    $products[$i]['shipped'] = $value == (int) $value ? (int) $value : $value;
                    $changed = true;
                }
            }
            if (!$changed) {
                return false;
            }

            $object->products = json_encode($products, JSON_UNESCAPED_UNICODE);
            $object->timestamps = false;
            $object->saveQuietly();
            $object->timestamps = true;

            try {
                \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $object->getData(['products']));
            } catch (\Throwable $e) {
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function shippedBySource(string $slug, int $id): array
    {
        $result = ['id' => [], 'name' => []];

        $documentIds = ObjectRelation::where('source_slug', $slug)
            ->where('source_id', $id)
            ->where('target_slug', self::DOCUMENT)
            ->pluck('target_id')
            ->map(fn ($v) => (int) $v)
            ->all();
        if (!count($documentIds)) {
            return $result;
        }

        foreach (ExpenseInvoice::whereIn('id', $documentIds)->get(['id', 'products']) as $document) {
            self::accumulate($result, $document->products);
        }

        return $result;
    }

    public static function usageByChildren(string $slug, int $id, array $childSlugs, ?array $except = null): array
    {
        $result = ['id' => [], 'name' => []];
        if (!ObjectRelation::ready() || !count($childSlugs)) {
            return $result;
        }
        $relations = ObjectRelation::where('source_slug', $slug)
            ->where('source_id', $id)
            ->whereIn('target_slug', $childSlugs)
            ->get(['target_slug', 'target_id']);

        foreach ($relations->groupBy('target_slug') as $childSlug => $items) {
            if (!Schema::hasTable($childSlug) || !Schema::hasColumn($childSlug, 'products')) {
                continue;
            }
            $ids = $items->pluck('target_id')->map(fn ($v) => (int) $v)->all();
            if ($except && $except[0] === $childSlug) {
                $ids = array_values(array_diff($ids, [(int) $except[1]]));
            }
            if (!count($ids)) {
                continue;
            }
            $query = DB::table($childSlug)->whereIn('id', $ids);
            if (Schema::hasColumn($childSlug, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            foreach ($query->pluck('products') as $products) {
                self::accumulate($result, $products);
            }
        }

        return $result;
    }

    public static function parentOf(string $slug, int $id): ?array
    {
        if (!ObjectRelation::ready()) {
            return null;
        }
        $parentSlugs = self::isSource($slug) ? ['deals'] : ($slug === self::DOCUMENT ? self::SOURCES : []);
        if (!count($parentSlugs)) {
            return null;
        }
        $relation = ObjectRelation::whereIn('source_slug', $parentSlugs)
            ->where('target_slug', $slug)
            ->where('target_id', $id)
            ->orderBy('id')
            ->first(['source_slug', 'source_id']);

        return $relation ? [(string) $relation->source_slug, (int) $relation->source_id] : null;
    }

    public static function childSlugsOf(string $slug): array
    {
        if ($slug === 'deals') {
            return self::SOURCES;
        }
        if (self::isSource($slug)) {
            return [self::DOCUMENT];
        }

        return [];
    }

    public static function lookup(array $shipped, array $product): float
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

    public static function serviceIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!count($ids) || !Schema::hasTable('products') || !Schema::hasColumn('products', 'product_type')) {
            return [];
        }
        $result = [];
        foreach (DB::table('products')->whereIn('id', $ids)->get(['id', 'product_type']) as $row) {
            $raw = $row->product_type;
            if (is_string($raw) && is_array($decoded = json_decode($raw, true))) {
                $raw = $decoded[0] ?? null;
            }
            if (trim((string) $raw) === '1') {
                $result[] = (int) $row->id;
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

    public static function nameKey($name): string
    {
        return mb_strtolower(self::plainName($name));
    }

    private static function accumulate(array &$result, $products): void
    {
        foreach (self::decode($products) as $product) {
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

    private static function modelClass(string $slug): ?string
    {
        $class = DB::table('data_types')->where('slug', $slug)->value('model_name');

        return $class && class_exists($class) ? $class : null;
    }
}
