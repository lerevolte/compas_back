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

    public static function hasShippedColumn(string $slug): bool
    {
        return self::isSource($slug) || $slug === 'deals';
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
            if ($changed) {
                $object->products = json_encode($products, JSON_UNESCAPED_UNICODE);
                $object->timestamps = false;
                $object->saveQuietly();
                $object->timestamps = true;

                try {
                    \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $object->getData(['products']));
                } catch (\Throwable $e) {
                }
            }

            try {
                self::updateShipmentStatus($slug, $object, $products, $shipped);
            } catch (\Throwable $e) {
            }
            try {
                $parent = self::parentOf($slug, $id);
                if ($parent && $parent[0] === 'deals') {
                    self::recalcDealShipped((int) $parent[1]);
                }
            } catch (\Throwable $e) {
            }

            return $changed;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function updateShipmentStatus(string $slug, $object, array $products, array $shipped): void
    {
        if (!Schema::hasColumn($slug, 'shipment_status')) {
            return;
        }
        $typeId = DB::table('data_types')->where('slug', $slug)->value('id');
        $fieldId = $typeId
            ? DB::table('data_rows')->where('data_type_id', $typeId)->where('field', 'shipment_status')->value('id')
            : null;
        if (!$fieldId) {
            return;
        }

        $hasAny = false;
        $full = false;
        $lines = 0;
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }
            $count = (float) ($product['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $lines++;
            $value = self::lookup($shipped, $product);
            if ($value > 0.0001) {
                $hasAny = true;
            }
            if ($lines === 1) {
                $full = true;
            }
            if ($value + 0.0001 < $count) {
                $full = false;
            }
        }
        $label = !$lines || !$hasAny ? 'Не отгружено' : ($full ? 'Отгружено полностью' : 'Отгружено частично');

        $valueId = DB::table('field_values')->where('field_id', $fieldId)->where('value', $label)->value('id');
        if (!$valueId || (string) $object->shipment_status === (string) $valueId) {
            return;
        }

        $object->shipment_status = (string) $valueId;
        $object->timestamps = false;
        $object->saveQuietly();
        $object->timestamps = true;

        try {
            \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $object->getData(['shipment_status']));
        } catch (\Throwable $e) {
        }
    }

    public static function recalcDealShipped(int $dealId): bool
    {
        if (!ObjectRelation::ready() || !Schema::hasTable('deals') || !Schema::hasColumn('deals', 'products')) {
            return false;
        }
        try {
            $class = self::modelClass('deals');
            $deal = $class ? $class::withTrashed()->find($dealId) : null;
            if (!$deal) {
                return false;
            }
            $products = self::decode($deal->products);
            if (!count($products)) {
                return false;
            }

            $shipped = self::shippedForDeal($dealId);
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

    public static function shippedForDeal(int $dealId): array
    {
        $result = ['id' => [], 'name' => [], 'price_id' => [], 'price_name' => []];

        $children = ObjectRelation::where('source_slug', 'deals')
            ->where('source_id', $dealId)
            ->whereIn('target_slug', self::SOURCES)
            ->get(['target_slug', 'target_id']);
        if (!count($children)) {
            return $result;
        }

        $documentIds = [];
        foreach ($children as $child) {
            $ids = ObjectRelation::where('source_slug', $child->target_slug)
                ->where('source_id', (int) $child->target_id)
                ->where('target_slug', self::DOCUMENT)
                ->pluck('target_id')
                ->map(fn ($v) => (int) $v)
                ->all();
            $documentIds = array_merge($documentIds, $ids);
        }
        $documentIds = array_values(array_unique($documentIds));
        if (!count($documentIds)) {
            return $result;
        }

        foreach (ExpenseInvoice::whereIn('id', $documentIds)->get(['id', 'products']) as $document) {
            self::accumulate($result, $document->products);
        }

        return $result;
    }

    public static function shippedBySource(string $slug, int $id): array
    {
        $result = ['id' => [], 'name' => [], 'price_id' => [], 'price_name' => []];

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
        $result = ['id' => [], 'name' => [], 'price_id' => [], 'price_name' => []];
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

    public static function lookupPrice(array $used, array $product): float
    {
        $id = (int) ($product['id'] ?? 0);
        if ($id && isset($used['price_id'][$id])) {
            return (float) $used['price_id'][$id];
        }
        $name = self::nameKey($product['name'] ?? '');
        if ($name !== '' && isset($used['price_name'][$name])) {
            return (float) $used['price_name'][$name];
        }

        return 0.0;
    }

    public static function residualProducts(string $sourceSlug, int $sourceId, array $products, ?array $exceptTarget = null): array
    {
        $childSlugs = self::childSlugsOf($sourceSlug);
        if (!count($childSlugs) || !count($products)) {
            return $products;
        }
        $used = self::usageByChildren($sourceSlug, $sourceId, $childSlugs, $exceptTarget);
        $services = self::serviceIds(array_map(fn ($p) => is_array($p) ? ($p['id'] ?? 0) : 0, $products));

        $result = [];
        foreach ($products as $product) {
            if (!is_array($product)) {
                $result[] = $product;
                continue;
            }
            $count = (float) ($product['count'] ?? 0);
            $price = (float) ($product['price'] ?? 0);
            if (in_array((int) ($product['id'] ?? 0), $services, true)) {
                $rest = max(0, $price - self::lookupPrice($used, $product));
                $product['price'] = $rest == (int) $rest ? (int) $rest : round($rest, 2);
                $product['sum'] = round($product['price'] * $count, 2);
                $result[] = $product;
                continue;
            }
            $rest = max(0, $count - self::lookup($used, $product));
            if ($rest <= 0) {
                continue;
            }
            $product['count'] = $rest == (int) $rest ? (int) $rest : $rest;
            $product['sum'] = round($price * $rest, 2);
            $result[] = $product;
        }

        return array_values($result);
    }

    public static function validateAgainstParent(string $slug, int $id, array $products): array
    {
        try {
            $parent = self::parentOf($slug, $id);
            if (!$parent) {
                return [];
            }

            return self::validateAgainstPair($parent[0], $parent[1], $slug, $id, $products);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function validateAgainstPair(string $parentSlug, int $parentId, string $childSlug, ?int $exceptChildId, array $products): array
    {
        try {
            if (!count($products) || !in_array($childSlug, self::childSlugsOf($parentSlug), true)) {
                return [];
            }
            if (!Schema::hasTable($parentSlug) || !Schema::hasColumn($parentSlug, 'products')) {
                return [];
            }
            $row = DB::table($parentSlug)->where('id', $parentId)->first();
            $parentProducts = array_values(array_filter(self::decode($row->products ?? null), 'is_array'));
            if (!count($parentProducts)) {
                return [];
            }
            $services = self::serviceIds(array_map(fn ($p) => $p['id'] ?? 0, $parentProducts));
            $usedOthers = self::usageByChildren(
                $parentSlug,
                $parentId,
                self::childSlugsOf($parentSlug),
                $exceptChildId ? [$childSlug, $exceptChildId] : null
            );

            $findLimit = function (array $product) use ($parentProducts) {
                $id = (int) ($product['id'] ?? 0);
                $name = self::nameKey($product['name'] ?? '');
                foreach ($parentProducts as $limit) {
                    $limitId = (int) ($limit['id'] ?? 0);
                    if ($id && $limitId === $id) {
                        return $limit;
                    }
                    if (!$id && $name !== '' && self::nameKey($limit['name'] ?? '') === $name) {
                        return $limit;
                    }
                }
                return null;
            };

            $format = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
            $errors = [];
            foreach ($products as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $name = self::plainName($product['name'] ?? '') ?: 'Товар';
                $limit = $findLimit($product);
                if (!$limit) {
                    $errors[] = "«{$name}»: нет в составе документа-основания";
                    continue;
                }
                $count = (float) ($product['count'] ?? 0);
                $price = (float) ($product['price'] ?? 0);
                $limitCount = (float) ($limit['count'] ?? 0);
                $limitPrice = (float) ($limit['price'] ?? 0);
                if (in_array((int) ($limit['id'] ?? 0), $services, true)) {
                    if ($limitPrice > 0) {
                        $lineTotal = $price * ($count > 0 ? $count : 1);
                        $limitTotal = $limitPrice * ($limitCount > 0 ? $limitCount : 1);
                        $othersPrice = self::lookupPrice($usedOthers, $limit);
                        if ($lineTotal + $othersPrice > $limitTotal + 0.0001) {
                            $errors[] = "«{$name}»: стоимость услуги {$format($lineTotal)}"
                                . ($othersPrice > 0 ? " + в других документах {$format($othersPrice)}" : '')
                                . " — превышает {$format($limitTotal)} в основании";
                        }
                    }
                } else {
                    $others = self::lookup($usedOthers, $limit);
                    if ($count + $others > $limitCount + 0.0001) {
                        $errors[] = "«{$name}»: в основании {$format($limitCount)} шт, здесь {$format($count)} шт"
                            . ($others > 0 ? " + в других документах {$format($others)} шт" : '')
                            . " — превышение на {$format($count + $others - $limitCount)} шт";
                    }
                    if ($limitPrice > 0 && $price > $limitPrice + 0.0001) {
                        $errors[] = "«{$name}»: цена {$format($price)} выше цены в основании {$format($limitPrice)}";
                    }
                }
            }

            return $errors;
        } catch (\Throwable $e) {
            return [];
        }
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
            $price = (float) ($product['price'] ?? 0) * $count;
            $id = (int) ($product['id'] ?? 0);
            if ($id) {
                $result['id'][$id] = ($result['id'][$id] ?? 0) + $count;
                $result['price_id'][$id] = ($result['price_id'][$id] ?? 0) + $price;
            }
            $name = self::nameKey($product['name'] ?? '');
            if ($name !== '') {
                $result['name'][$name] = ($result['name'][$name] ?? 0) + $count;
                $result['price_name'][$name] = ($result['price_name'][$name] ?? 0) + $price;
            }
        }
    }

    private static function modelClass(string $slug): ?string
    {
        $class = DB::table('data_types')->where('slug', $slug)->value('model_name');

        return $class && class_exists($class) ? $class : null;
    }
}
