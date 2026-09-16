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
    public const RETURN_DOC = 'product_returns';
    public const SUPPLIER = 'supplier_orders';

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

    public const ACTION_FIELD = 'action_type';
    public const ACTION_LOADING = 'Загрузка';
    public const ACTION_UNLOADING = 'Выгрузка';

    private static array $actionCache = [];

    public static function actionLabel(string $slug, int $id): ?string
    {
        if ($slug !== 'logistic_tasks' || !$id) {
            return null;
        }
        $key = $slug . ':' . $id;
        if (array_key_exists($key, self::$actionCache)) {
            return self::$actionCache[$key];
        }
        $result = null;
        try {
            if (Schema::hasColumn($slug, self::ACTION_FIELD)) {
                $raw = DB::table($slug)->where('id', $id)->value(self::ACTION_FIELD);
                $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                if (is_array($decoded)) {
                    $raw = $decoded[0] ?? null;
                }
                if ($raw !== null && $raw !== '' && is_numeric($raw)) {
                    $label = DB::table('field_values')->where('id', (int) $raw)->value('value');
                    $label = mb_strtolower(trim((string) $label));
                    $result = $label !== '' ? $label : null;
                }
            }
        } catch (\Throwable $e) {
            $result = null;
        }
        self::$actionCache[$key] = $result;

        return $result;
    }

    public static function isLoading(string $slug, int $id): bool
    {
        return self::actionLabel($slug, $id) === mb_strtolower(self::ACTION_LOADING);
    }

    public static function isNeutralAction(string $slug, int $id): bool
    {
        $label = self::actionLabel($slug, $id);

        return $label !== null
            && $label !== mb_strtolower(self::ACTION_LOADING)
            && $label !== mb_strtolower(self::ACTION_UNLOADING);
    }

    public static function forgetLoading(string $slug, int $id): void
    {
        unset(self::$actionCache[$slug . ':' . $id]);
    }

    public static function normalizeDealId($value): ?int
    {
        if (is_string($value) && is_array($decoded = json_decode($value, true))) {
            $value = $decoded;
        }
        if (is_array($value)) {
            $value = array_values(array_filter($value, 'is_numeric'))[0] ?? null;
        }
        return $value !== null && $value !== '' && is_numeric($value) ? (int) $value : null;
    }

    public static function syncDealLink(string $slug, int $id, $newDealId, $oldDealId = null): void
    {
        if (!self::isSource($slug) || !ObjectRelation::ready() || !Schema::hasTable('deals')) {
            return;
        }
        $new = self::normalizeDealId($newDealId);
        $old = self::normalizeDealId($oldDealId);
        if ($old && $old !== $new) {
            ObjectRelation::where('source_slug', 'deals')->where('source_id', $old)
                ->where('target_slug', $slug)->where('target_id', $id)->delete();
            try {
                self::recalcDealShipped($old);
            } catch (\Throwable $e) {
            }
        }
        if ($new && $new !== $old) {
            $exists = ObjectRelation::where('source_slug', 'deals')->where('source_id', $new)
                ->where('target_slug', $slug)->where('target_id', $id)->exists();
            if (!$exists) {
                ObjectRelation::link('deals', $new, $slug, $id);
            }
            try {
                self::recalcForSource($slug, $id);
                self::recalcDealShipped($new);
            } catch (\Throwable $e) {
            }
        }
    }

    public static function setDealColumn(string $slug, int $id, int $dealId): void
    {
        if (!self::isSource($slug) || !Schema::hasColumn($slug, 'deal_id')) {
            return;
        }
        DB::table($slug)->where('id', $id)->update(['deal_id' => $dealId]);
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
                \Log::warning('shipments: статус отгрузки не обновлён', ['source' => $slug . '#' . $id, 'error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
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
            \Log::warning('shipments: пересчёт не выполнен', ['source' => $slug . '#' . $id, 'error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
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

        $returned = ['id' => [], 'name' => [], 'price_id' => [], 'price_name' => []];
        foreach ($children as $child) {
            $result = self::mergeUsage($result, self::invoicesUsage((string) $child->target_slug, (int) $child->target_id));
            $returned = self::mergeUsage($returned, self::returnsUsage((string) $child->target_slug, (int) $child->target_id));
        }

        return self::subtractUsage($result, $returned);
    }

    private static function mergeUsage(array $base, array $add): array
    {
        foreach (['id', 'name', 'price_id', 'price_name'] as $bucket) {
            foreach ($add[$bucket] ?? [] as $key => $value) {
                $base[$bucket][$key] = ($base[$bucket][$key] ?? 0) + $value;
            }
        }

        return $base;
    }

    public static function shippedBySource(string $slug, int $id): array
    {
        if (self::isNeutralAction($slug, $id)) {
            return ['id' => [], 'name' => [], 'price_id' => [], 'price_name' => []];
        }
        if (self::isLoading($slug, $id)) {
            return self::returnsUsage($slug, $id);
        }
        $shipped = self::invoicesUsage($slug, $id);
        $returned = self::returnsUsage($slug, $id);

        return self::subtractUsage($shipped, $returned);
    }

    public static function invoicesUsage(string $slug, int $id, ?int $exceptId = null): array
    {
        $result = ['id' => [], 'name' => [], 'price_id' => [], 'price_name' => []];

        $documentIds = ObjectRelation::where('source_slug', $slug)
            ->where('source_id', $id)
            ->where('target_slug', self::DOCUMENT)
            ->pluck('target_id')
            ->map(fn ($v) => (int) $v)
            ->all();
        if ($exceptId) {
            $documentIds = array_values(array_diff($documentIds, [$exceptId]));
        }
        if (!count($documentIds)) {
            return $result;
        }

        foreach (ExpenseInvoice::whereIn('id', $documentIds)->get(['id', 'products']) as $document) {
            self::accumulate($result, $document->products);
        }

        return $result;
    }

    public static function returnsUsage(string $slug, int $id, ?int $exceptId = null): array
    {
        $result = ['id' => [], 'name' => [], 'price_id' => [], 'price_name' => []];
        if (!Schema::hasTable(self::RETURN_DOC)) {
            return $result;
        }

        $documentIds = ObjectRelation::where('source_slug', $slug)
            ->where('source_id', $id)
            ->where('target_slug', self::RETURN_DOC)
            ->pluck('target_id')
            ->map(fn ($v) => (int) $v)
            ->all();
        if ($exceptId) {
            $documentIds = array_values(array_diff($documentIds, [$exceptId]));
        }
        if (!count($documentIds)) {
            return $result;
        }

        $query = DB::table(self::RETURN_DOC)->whereIn('id', $documentIds);
        if (Schema::hasColumn(self::RETURN_DOC, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        foreach ($query->pluck('products') as $products) {
            self::accumulate($result, $products);
        }

        return $result;
    }

    public static function subtractUsage(array $base, array $minus): array
    {
        foreach (['id', 'name', 'price_id', 'price_name'] as $bucket) {
            foreach ($minus[$bucket] ?? [] as $key => $value) {
                $base[$bucket][$key] = max(0, ($base[$bucket][$key] ?? 0) - $value);
            }
        }

        return $base;
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
        $parentSlugs = self::isSource($slug)
            ? ['deals']
            : ($slug === self::DOCUMENT ? self::SOURCES : ($slug === self::RETURN_DOC ? array_merge(self::SOURCES, [self::SUPPLIER]) : []));
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

    public static function childSlugsOf(string $slug, ?int $id = null): array
    {
        if ($slug === 'deals') {
            return self::SOURCES;
        }
        if ($slug === self::SUPPLIER) {
            return [self::RETURN_DOC];
        }
        if (self::isSource($slug)) {
            if ($id && self::isNeutralAction($slug, $id)) {
                return [];
            }
            return $id && self::isLoading($slug, $id) ? [self::RETURN_DOC] : [self::DOCUMENT];
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
        $childSlugs = self::childSlugsOf($sourceSlug, $sourceId);
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
            if (!count($products)) {
                return [];
            }
            if ($childSlug === self::RETURN_DOC && self::isSource($parentSlug)
                && !self::isLoading($parentSlug, $parentId) && !self::isNeutralAction($parentSlug, $parentId)) {
                return self::validateReturn($parentSlug, $parentId, $exceptChildId, $products);
            }
            if (!in_array($childSlug, self::childSlugsOf($parentSlug, $parentId), true)) {
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
                self::childSlugsOf($parentSlug, $parentId),
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

    public static function validateAgainstChildren(string $slug, int $id, array $products): array
    {
        try {
            if (!ObjectRelation::ready()) {
                return [];
            }
            $childSlugs = $slug === 'deals'
                ? self::SOURCES
                : ($slug === self::SUPPLIER ? [self::RETURN_DOC] : (self::isSource($slug) ? self::childSlugsOf($slug, $id) : []));
            if (!count($childSlugs)) {
                return [];
            }
            $lines = self::childLines($slug, $id, $childSlugs);
            if (self::isSource($slug) && in_array(self::DOCUMENT, $childSlugs, true)) {
                $returns = self::childLines($slug, $id, [self::RETURN_DOC]);
                foreach ($returns as $key => $return) {
                    if (isset($lines[$key])) {
                        $lines[$key]['count'] = max(0, $lines[$key]['count'] - $return['count']);
                        $lines[$key]['price_total'] = max(0, $lines[$key]['price_total'] - $return['price_total']);
                    }
                }
            }
            if (!count($lines)) {
                return [];
            }

            $services = self::serviceIds(array_map(fn ($line) => $line['id'], $lines));
            $format = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
            $label = $slug === 'deals' ? 'в связанных задачах/самовывозах' : 'в связанных документах';

            $findProduct = function (array $line) use ($products) {
                if ($line['id']) {
                    foreach ($products as $product) {
                        if (is_array($product) && (int) ($product['id'] ?? 0) === $line['id']) {
                            return $product;
                        }
                    }
                }
                if ($line['name_key'] !== '') {
                    foreach ($products as $product) {
                        if (is_array($product) && self::nameKey($product['name'] ?? '') === $line['name_key']) {
                            return $product;
                        }
                    }
                }
                return null;
            };

            $errors = [];
            foreach ($lines as $line) {
                $isService = $line['id'] && in_array($line['id'], $services, true);
                if (!$isService && $line['count'] <= 0.0001) {
                    continue;
                }
                if ($isService && $line['price_total'] <= 0.0001) {
                    continue;
                }
                $name = $line['name'] !== '' ? $line['name'] : 'Товар';
                $product = $findProduct($line);
                if (!$product) {
                    $errors[] = $isService
                        ? "«{$name}»: услуга удалена из состава, но уже есть {$label} на {$format($line['price_total'])}"
                        : "«{$name}»: удалён из состава, но уже есть {$label} {$format($line['count'])} шт";
                    continue;
                }
                $count = (float) ($product['count'] ?? 0);
                $price = (float) ($product['price'] ?? 0);
                if ($isService) {
                    $total = $price * ($count > 0 ? $count : 1);
                    if ($line['price_total'] > $total + 0.0001) {
                        $errors[] = "«{$name}»: стоимость услуги {$format($total)} меньше, чем уже распределено {$label} ({$format($line['price_total'])})";
                    }
                } elseif ($line['count'] > $count + 0.0001) {
                    $errors[] = "«{$name}»: {$label} уже {$format($line['count'])} шт — нельзя указать меньше ({$format($count)} шт)";
                }
            }

            return $errors;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function childLines(string $slug, int $id, array $childSlugs): array
    {
        $lines = [];
        $relations = ObjectRelation::where('source_slug', $slug)
            ->where('source_id', $id)
            ->whereIn('target_slug', $childSlugs)
            ->get(['target_slug', 'target_id']);

        foreach ($relations->groupBy('target_slug') as $childSlug => $items) {
            if (!Schema::hasTable($childSlug) || !Schema::hasColumn($childSlug, 'products')) {
                continue;
            }
            $ids = $items->pluck('target_id')->map(fn ($v) => (int) $v)->all();
            $query = DB::table($childSlug)->whereIn('id', $ids);
            if (Schema::hasColumn($childSlug, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            foreach ($query->pluck('products') as $raw) {
                foreach (self::decode($raw) as $product) {
                    if (!is_array($product)) {
                        continue;
                    }
                    $count = (float) ($product['count'] ?? 0);
                    if ($count <= 0) {
                        continue;
                    }
                    $productId = (int) ($product['id'] ?? 0);
                    $nameKey = self::nameKey($product['name'] ?? '');
                    $key = $productId ? 'id:' . $productId : 'name:' . $nameKey;
                    if (!isset($lines[$key])) {
                        $lines[$key] = [
                            'id' => $productId,
                            'name' => self::plainName($product['name'] ?? ''),
                            'name_key' => $nameKey,
                            'count' => 0.0,
                            'price_total' => 0.0,
                        ];
                    }
                    $lines[$key]['count'] += $count;
                    $lines[$key]['price_total'] += (float) ($product['price'] ?? 0) * $count;
                }
            }
        }

        return $lines;
    }

    public static function validateReturn(string $parentSlug, int $parentId, ?int $exceptChildId, array $products): array
    {
        try {
            $available = self::subtractUsage(
                self::invoicesUsage($parentSlug, $parentId),
                self::returnsUsage($parentSlug, $parentId, $exceptChildId)
            );
            $format = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

            $errors = [];
            foreach ($products as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $count = (float) ($product['count'] ?? 0);
                if ($count <= 0) {
                    continue;
                }
                $name = self::plainName($product['name'] ?? '') ?: 'Товар';
                $limit = self::lookup($available, $product);
                if ($count > $limit + 0.0001) {
                    $errors[] = "«{$name}»: возврат {$format($count)} шт превышает отгруженное — доступно к возврату {$format($limit)} шт";
                }
            }

            return $errors;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function returnDefaults(string $parentSlug, int $parentId, ?int $exceptChildId = null): array
    {
        if (!self::isSource($parentSlug) || !Schema::hasTable($parentSlug) || !Schema::hasColumn($parentSlug, 'products')) {
            return [];
        }
        $row = DB::table($parentSlug)->where('id', $parentId)->first();
        $products = array_values(array_filter(self::decode($row->products ?? null), 'is_array'));
        if (!count($products)) {
            return [];
        }
        if (self::isNeutralAction($parentSlug, $parentId)) {
            foreach ($products as $i => $product) {
                unset($products[$i]['shipped']);
            }
            return array_values($products);
        }
        if (self::isLoading($parentSlug, $parentId)) {
            return self::residualProducts($parentSlug, $parentId, $products, $exceptChildId ? [self::RETURN_DOC, $exceptChildId] : null);
        }
        $available = self::subtractUsage(
            self::invoicesUsage($parentSlug, $parentId),
            self::returnsUsage($parentSlug, $parentId, $exceptChildId)
        );

        $result = [];
        foreach ($products as $product) {
            $limit = self::lookup($available, $product);
            if ($limit <= 0) {
                continue;
            }
            unset($product['shipped']);
            $product['count'] = $limit == (int) $limit ? (int) $limit : $limit;
            $price = (float) ($product['price'] ?? 0);
            $product['sum'] = round($price * $limit, 2);
            $result[] = $product;
        }

        return $result;
    }

    public static function serviceIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!count($ids) || !Schema::hasTable('products') || !Schema::hasColumn('products', 'product_type')) {
            return [];
        }
        $result = [];
        $deliveryB24 = class_exists(\Modules\Bitrix24\Http\Controllers\Bitrix24Controller::class)
            ? array_map('strval', \Modules\Bitrix24\Http\Controllers\Bitrix24Controller::SKIP_PRODUCT_IDS)
            : [];
        $columns = Schema::hasColumn('products', 'id_b24') ? ['id', 'product_type', 'id_b24'] : ['id', 'product_type'];
        foreach (DB::table('products')->whereIn('id', $ids)->get($columns) as $row) {
            $raw = $row->product_type;
            if (is_string($raw) && is_array($decoded = json_decode($raw, true))) {
                $raw = $decoded[0] ?? null;
            }
            if (count($deliveryB24) && isset($row->id_b24) && in_array((string) $row->id_b24, $deliveryB24, true)) {
                $raw = '1';
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
