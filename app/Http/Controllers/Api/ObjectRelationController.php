<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ObjectRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ObjectRelationController extends Controller
{
    private array $infoCache = [];

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_slug' => 'required|string|max:64',
            'source_id' => 'required|integer',
            'target_slug' => 'required|string|max:64',
            'target_id' => 'required|integer',
            'copy_products' => 'nullable|boolean',
        ]);

        if (!ObjectRelation::ready()) {
            return response()->json(['ok' => false], 200);
        }

        return \App\Services\ShipmentService::withFamilyLock($data['source_slug'], (int) $data['source_id'], fn () => $this->storeLinked($data));
    }

    private function storeLinked(array $data)
    {
        $targetProducts = \App\Services\ShipmentService::productsOf($data['target_slug'], (int) $data['target_id']);
        if (count($targetProducts)) {
            $linkErrors = \App\Services\ShipmentService::validateAgainstPair($data['source_slug'], (int) $data['source_id'], $data['target_slug'], (int) $data['target_id'], $targetProducts);
            if (count($linkErrors)) {
                return response()->json([
                    'title' => 'Расхождение по составу со связанным документом — связь не создана',
                    'errors' => $linkErrors,
                ], 422);
            }
        }
        $storehouseErrors = \App\Services\StorehouseService::linkErrors($data['source_slug'], (int) $data['source_id'], $data['target_slug'], (int) $data['target_id']);
        if (count($storehouseErrors)) {
            return response()->json([
                'title' => 'Склад не совпадает с документом-основанием — связь не создана',
                'errors' => $storehouseErrors,
            ], 422);
        }
        ObjectRelation::linkWithHistory($data['source_slug'], $data['source_id'], $data['target_slug'], $data['target_id']);
        \App\Services\StorehouseService::inheritOnLink($data['source_slug'], (int) $data['source_id'], $data['target_slug'], (int) $data['target_id']);
        if ($data['source_slug'] === 'deals' && \App\Services\ShipmentService::isSource($data['target_slug'])) {
            \App\Services\ShipmentService::setDealColumn($data['target_slug'], (int) $data['target_id'], (int) $data['source_id']);
            try {
                \App\Services\ShipmentService::recalcDealShipped((int) $data['source_id']);
            } catch (\Throwable $e) {
            }
        }

        $b24Copied = ObjectRelation::copyB24Id(
            $data['source_slug'],
            $data['source_id'],
            $data['target_slug'],
            $data['target_id']
        );

        $productsCopied = false;
        if (($data['copy_products'] ?? true) !== false) {
            $productsCopied = ObjectRelation::copyProducts(
                $data['source_slug'],
                $data['source_id'],
                $data['target_slug'],
                $data['target_id']
            );
        }

        \App\Services\ProductionService::afterLink((string) $data['source_slug'], (int) $data['source_id'], (string) $data['target_slug'], (int) $data['target_id']);

        if ($data['target_slug'] === \App\Services\ShipmentService::DOCUMENT) {
            \App\Services\ShipmentService::recalcForDocument((int) $data['target_id']);
        }
        if ($data['target_slug'] === \App\Services\ShipmentService::RETURN_DOC) {
            \App\Models\ProductReturn::recalcParentShipments((int) $data['target_id']);
        }
        if ($data['target_slug'] === \App\Services\ShipmentService::RECEIPT_DOC) {
            \App\Models\ReceiptInvoice::recalcParentShipments((int) $data['target_id']);
        }

        $printGenerated = \App\Services\SaleDocumentService::generateFor(
            $data['target_slug'],
            $data['target_id'],
            $data['source_slug'],
            $data['source_id']
        );

        if ($data['target_slug'] === \App\Services\ShipmentService::DOCUMENT) {
            \App\Services\ShipmentService::recalcForDocument((int) $data['target_id']);
        }

        return response()->json(['ok' => true, 'products_copied' => $productsCopied, 'b24_copied' => $b24Copied, 'print_generated' => $printGenerated]);
    }

    public function validateProducts(Request $request)
    {
        $data = $request->validate([
            'source_slug' => 'required|string|max:64',
            'source_id' => 'required|integer',
            'target_slug' => 'required|string|max:64',
            'target_id' => 'nullable|integer',
            'products' => 'nullable|array',
            'fields' => 'nullable|array',
        ]);

        $products = [];
        foreach ((array) ($data['products'] ?? []) as $product) {
            if (!is_array($product)) {
                continue;
            }
            $products[] = [
                'id' => $product['id'] ?? null,
                'name' => $product['product_name'] ?? ($product['name'] ?? ''),
                'price' => $product['product_price'] ?? ($product['price'] ?? null),
                'count' => $product['product_count'] ?? ($product['count'] ?? null),
            ];
        }

        $errors = \App\Services\ShipmentService::validateAgainstPair(
            $data['source_slug'],
            (int) $data['source_id'],
            $data['target_slug'],
            isset($data['target_id']) ? (int) $data['target_id'] : null,
            $products
        );
        $errors = array_merge($errors, \App\Services\StorehouseService::draftErrors(
            $data['source_slug'],
            (int) $data['source_id'],
            $data['target_slug'],
            (array) ($data['fields'] ?? [])
        ));

        return response()->json(['errors' => $errors]);
    }

    public function productsCheck($slug, $id)
    {
        $id = (int) $id;
        $empty = ['parent' => null, 'limits' => [], 'usage' => []];
        if (!ObjectRelation::ready()) {
            return response()->json($empty);
        }

        $usage = [];
        $loading = \App\Services\ShipmentService::isLoading($slug, $id);
        $childSlugs = \App\Services\ShipmentService::childSlugsOf($slug, $id);
        if (count($childSlugs)) {
            $target = request()->query('target');
            $used = \App\Services\ShipmentService::usageForTarget($slug, $id, is_string($target) && $target !== '' ? $target : null);
            $returned = \App\Services\ShipmentService::isSource($slug) && !$loading
                ? \App\Services\ShipmentService::returnsUsage($slug, (int) $id)
                : null;
            $row = Schema::hasTable($slug) ? DB::table($slug)->where('id', $id)->first() : null;
            $products = \App\Services\ShipmentService::decode($row->products ?? null);
            $services = \App\Services\ShipmentService::serviceIds(array_map(fn ($p) => $p['id'] ?? 0, array_filter($products, 'is_array')));
            foreach ($products as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $usage[] = [
                    'id' => (int) ($product['id'] ?? 0) ?: null,
                    'name' => \App\Services\ShipmentService::plainName($product['name'] ?? ''),
                    'is_service' => in_array((int) ($product['id'] ?? 0), $services, true),
                    'count' => (float) ($product['count'] ?? 0),
                    'used' => \App\Services\ShipmentService::lookup($used, $product),
                    'used_price' => \App\Services\ShipmentService::lookupPrice($used, $product),
                    'returned' => $returned !== null ? \App\Services\ShipmentService::lookup($returned, $product) : 0,
                ];
            }
        }

        $parent = $id ? \App\Services\ShipmentService::parentOf($slug, $id) : null;
        if (!$id) {
            $draftSlug = (string) request()->query('parent_slug', '');
            $draftId = (int) request()->query('parent_id', 0);
            if ($draftSlug !== '' && $draftId > 0 && preg_match('/^[a-z0-9_]+$/', $draftSlug) && Schema::hasTable($draftSlug)) {
                $parent = [$draftSlug, $draftId];
            }
        }
        $limits = [];
        $parentInfo = null;
        if ($parent) {
            [$parentSlug, $parentId] = $parent;
            $parentInfo = $this->objectInfo($parentSlug, $parentId);
            if ($parentInfo) {
                unset($parentInfo['products']);
            }
            $row = DB::table($parentSlug)->where('id', $parentId)->first();
            $products = \App\Services\ShipmentService::decode($row->products ?? null);
            if ($slug === \App\Services\ShipmentService::RETURN_DOC && \App\Services\ShipmentService::isSource($parentSlug)
                && !\App\Services\ShipmentService::isLoading($parentSlug, (int) $parentId)
                && !\App\Services\ShipmentService::isNeutralAction($parentSlug, (int) $parentId)) {
                $shipped = \App\Services\ShipmentService::invoicesUsage($parentSlug, (int) $parentId);
                $otherReturns = \App\Services\ShipmentService::returnsUsage($parentSlug, (int) $parentId, (int) $id);
                foreach ($products as $product) {
                    if (!is_array($product)) {
                        continue;
                    }
                    $limits[] = [
                        'id' => (int) ($product['id'] ?? 0) ?: null,
                        'name' => \App\Services\ShipmentService::plainName($product['name'] ?? ''),
                        'is_service' => false,
                        'count' => \App\Services\ShipmentService::lookup($shipped, $product),
                        'price' => 0,
                        'used_others' => \App\Services\ShipmentService::lookup($otherReturns, $product),
                        'used_others_price' => 0,
                    ];
                }
            } else {
                $usedOthers = \App\Services\ShipmentService::usageBySiblings($parentSlug, (int) $parentId, $slug, [$slug, $id]);
                $services = \App\Services\ShipmentService::serviceIds(array_map(fn ($p) => $p['id'] ?? 0, array_filter($products, 'is_array')));
                foreach ($products as $product) {
                    if (!is_array($product)) {
                        continue;
                    }
                    $limits[] = [
                        'id' => (int) ($product['id'] ?? 0) ?: null,
                        'name' => \App\Services\ShipmentService::plainName($product['name'] ?? ''),
                        'is_service' => in_array((int) ($product['id'] ?? 0), $services, true),
                        'count' => (float) ($product['count'] ?? 0),
                        'price' => (float) ($product['price'] ?? 0),
                        'used_others' => \App\Services\ShipmentService::lookup($usedOthers, $product),
                        'used_others_price' => \App\Services\ShipmentService::lookupPrice($usedOthers, $product),
                    ];
                }
            }
        }

        $ownRow = $id && Schema::hasTable($slug) ? DB::table($slug)->where('id', $id)->first(['products']) : null;
        $ownProducts = array_filter(\App\Services\ShipmentService::decode($ownRow->products ?? null), 'is_array');

        return response()->json([
            'parent' => $parentInfo,
            'limits' => $limits,
            'usage' => $usage,
            'services' => \App\Services\ShipmentService::serviceIds(array_map(fn ($p) => (int) ($p['id'] ?? 0), $ownProducts)),
            'loading' => $loading,
            'neutral' => \App\Services\ShipmentService::isNeutralAction($slug, $id),
        ]);
    }

    public function printDocuments($slug, $id)
    {
        if (!ObjectRelation::ready()) {
            return response()->json(['data' => []]);
        }

        $tree = $this->buildNode($slug, (int) $id, (int) $id, $slug, []);

        $flat = [];
        $walk = function ($node) use (&$walk, &$flat) {
            if (!$node) {
                return;
            }
            $flat[] = $node;
            foreach ($node['children'] ?? [] as $child) {
                $walk($child);
            }
        };
        $walk($tree);

        foreach ($this->ancestorDocuments($slug, (int) $id) as $node) {
            $flat[] = $node;
        }

        $docs = [];
        $seen = [];
        foreach ($flat as $node) {
            if (isset($seen[$node['slug'] . '#' . $node['id']])) {
                continue;
            }
            $seen[$node['slug'] . '#' . $node['id']] = true;
            if (!isset(\App\Services\SaleDocumentService::TARGETS[$node['slug']])) {
                continue;
            }
            $row = DB::table($node['slug'])->where('id', $node['id'])->first();
            if (!$row || (property_exists($row, 'deleted_at') && $row->deleted_at)) {
                continue;
            }
            $docs[] = [
                'slug' => $node['slug'],
                'id' => $node['id'],
                'entity_title' => $node['entity_title'],
                'name' => $node['name'],
                'created_at' => $node['created_at'],
                'sum' => $row->sum ?? null,
                'files' => \App\Services\SaleDocumentService::documentFiles($row->photo ?? null),
            ];
        }

        return response()->json(['data' => $docs]);
    }

    public function tree($slug, $id)
    {
        if (!ObjectRelation::ready()) {
            return response()->json(['data' => null]);
        }

        $root = $this->rootOf($slug, (int) $id);
        $depths = $this->depthsFrom($root['slug'], $root['id']);
        $placed = [];
        $tree = $this->buildNode($root['slug'], $root['id'], (int) $id, $slug, [], $depths, $placed);
        if ($tree && !($root['slug'] === $slug && $root['id'] === (int) $id)) {
            $tree = $this->pruneToCurrent($tree);
        }

        return response()->json(['data' => $tree]);
    }

    private function pruneToCurrent(array $node): ?array
    {
        if (!empty($node['is_current'])) {
            return $node;
        }
        $children = [];
        foreach ($node['children'] ?? [] as $child) {
            $kept = $this->pruneToCurrent($child);
            if ($kept) {
                $children[] = $kept;
            }
        }
        if (!count($children)) {
            return null;
        }
        $node['children'] = $children;

        return $node;
    }

    private function depthsFrom(string $slug, int $id): array
    {
        $rootKey = $slug . ':' . $id;
        $depths = [$rootKey => 0];
        $queue = [[$slug, $id, 0]];
        $guard = 0;
        while (count($queue) && $guard++ < 5000) {
            [$nodeSlug, $nodeId, $depth] = array_shift($queue);
            if ($depth >= 50) {
                continue;
            }
            $relations = ObjectRelation::where('source_slug', $nodeSlug)
                ->where('source_id', $nodeId)
                ->orderBy('id')
                ->get(['target_slug', 'target_id']);
            foreach ($relations as $relation) {
                $childSlug = (string) $relation->target_slug;
                $childId = (int) $relation->target_id;
                $key = $childSlug . ':' . $childId;
                if ($key === $rootKey || ($depths[$key] ?? -1) >= $depth + 1) {
                    continue;
                }
                if (!$this->objectInfo($childSlug, $childId)) {
                    continue;
                }
                $depths[$key] = $depth + 1;
                $queue[] = [$childSlug, $childId, $depth + 1];
            }
        }

        return $depths;
    }

    private function ancestorDocuments(string $slug, int $id): array
    {
        $targets = array_keys(\App\Services\SaleDocumentService::TARGETS);
        $result = [];
        $guard = 0;
        $visited = [$slug . ':' . $id];
        while ($guard++ < 20) {
            $parent = ObjectRelation::nearestParent($slug, $id);
            if (!$parent) {
                break;
            }
            [$slug, $id] = $parent;
            if (in_array($slug . ':' . $id, $visited, true)) {
                break;
            }
            $visited[] = $slug . ':' . $id;
            $children = ObjectRelation::where('source_slug', $slug)
                ->where('source_id', $id)
                ->whereIn('target_slug', $targets)
                ->orderBy('id')
                ->get(['target_slug', 'target_id']);
            foreach ($children as $relation) {
                $info = $this->objectInfo((string) $relation->target_slug, (int) $relation->target_id);
                if ($info) {
                    $result[] = $info;
                }
            }
        }

        return $result;
    }

    private function rootOf(string $slug, int $id): array
    {
        $guard = 0;
        while ($guard++ < 20) {
            $parent = ObjectRelation::nearestParent($slug, $id);
            if (!$parent) {
                break;
            }
            [$slug, $id] = $parent;
        }

        return ['slug' => $slug, 'id' => $id];
    }

    private function buildNode(string $slug, int $id, int $currentId, string $currentSlug, array $visited, array $depths, array &$placed): ?array
    {
        $key = $slug . ':' . $id;
        if (in_array($key, $visited, true) || count($visited) > 50 || isset($placed[$key])) {
            return null;
        }
        $visited[] = $key;

        $object = $this->objectInfo($slug, $id);
        if (!$object) {
            return null;
        }
        $placed[$key] = true;
        $depth = $depths[$key] ?? count($visited) - 1;

        $children = [];
        $ordinals = [];
        foreach (ObjectRelation::where('source_slug', $slug)->where('source_id', $id)->orderBy('id')->get() as $relation) {
            $childKey = $relation->target_slug . ':' . $relation->target_id;
            if (isset($depths[$childKey]) && $depths[$childKey] !== $depth + 1) {
                continue;
            }
            $child = $this->buildNode($relation->target_slug, (int) $relation->target_id, $currentId, $currentSlug, $visited, $depths, $placed);
            if ($child) {
                $ordinals[$child['slug']] = ($ordinals[$child['slug']] ?? 0) + 1;
                $child['ordinal'] = $ordinals[$child['slug']];
                $children[] = $child;
            }
        }

        return $object + [
            'is_current' => $slug === $currentSlug && $id === $currentId,
            'ordinal' => null,
            'children' => $children,
        ];
    }

    private function objectInfo(string $slug, int $id): ?array
    {
        $cacheKey = $slug . ':' . $id;
        if (array_key_exists($cacheKey, $this->infoCache)) {
            return $this->infoCache[$cacheKey];
        }

        return $this->infoCache[$cacheKey] = $this->loadObjectInfo($slug, $id);
    }

    private function loadObjectInfo(string $slug, int $id): ?array
    {
        if (!Schema::hasTable($slug)) {
            return null;
        }

        $row = DB::table($slug)->where('id', $id)->first();
        if (!$row) {
            return null;
        }

        if (property_exists($row, 'deleted_at') && $row->deleted_at) {
            return null;
        }

        $type = DB::table('data_types')->where('slug', $slug)->first(['title_singular', 'title_plural']);

        return [
            'slug' => $slug,
            'id' => $id,
            'entity_title' => ($type->title_plural ?? '') ?: (($type->title_singular ?? '') ?: $slug),
            'name' => $this->nameOf($row),
            'created_at' => isset($row->created_at) && $row->created_at
                ? (date('H:i:s', strtotime($row->created_at)) === '00:00:00'
                    ? date('d.m.Y', strtotime($row->created_at))
                    : date('d.m.Y H:i:s', strtotime($row->created_at)))
                : null,
            'products' => in_array($slug, array_merge(['deals', \App\Services\ShipmentService::DOCUMENT, \App\Services\ShipmentService::RETURN_DOC, \App\Services\ShipmentService::RECEIPT_DOC, \App\Services\ShipmentService::SUPPLIER], \App\Services\ShipmentService::SOURCES), true) ? $this->productsOf($row) : [],
            'rows' => $this->rowsOf($slug, $row),
        ];
    }

    private function rowsOf(string $slug, $row): array
    {
        $typeId = DB::table('data_types')->where('slug', $slug)->value('id');
        if (!$typeId) {
            return [];
        }
        $fields = DB::table('data_rows')->where('data_type_id', $typeId)
            ->where(fn ($q) => $q->whereNull('is_remove')->orWhere('is_remove', 0))
            ->whereNull('group_id')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
        $flagged = $fields->filter(fn ($field) => \App\Models\Field::showsInRelations($field->details));
        if (!$flagged->count()) {
            $flagged = $fields->filter(fn ($field) => in_array($field->field, ['name', 'id', 'delivery_date', 'products'], true) || in_array($field->type, ['status', 'deal_stages'], true));
        }
        $result = [];
        foreach ($flagged as $field) {
            if (!property_exists($row, $field->field)) {
                continue;
            }
            $item = $this->rowOf($field, $row);
            if ($item) {
                $result[] = $item;
            }
        }

        return $result;
    }

    private function statusFile($file): ?string
    {
        if (is_array($file)) {
            $file = $file[0] ?? null;
        }
        if (is_string($file) && in_array(substr(ltrim($file), 0, 1), ['[', '{'], true)) {
            $decoded = json_decode($file, true);
            $file = is_array($decoded) ? ($decoded[0] ?? $decoded) : $file;
        }
        if (is_array($file)) {
            $file = $file['url'] ?? ($file['download_link'] ?? ($file['src'] ?? null));
        }
        $file = is_string($file) ? trim($file) : '';
        if ($file === '' || in_array($file, ['[]', '{}', 'null'], true)) {
            return null;
        }
        if (!preg_match('~^(https?:)?//|^/|^data:~', $file)) {
            $file = '/' . ltrim($file, '/');
        }

        return $file;
    }

    private function rowOf($field, $row): ?array
    {
        $raw = $row->{$field->field};
        $base = ['key' => $field->field, 'label' => trim((string) $field->title) ?: $field->field, 'type' => $field->type];
        if (in_array($field->type, ['file', 'text_group', 'redactor', 'waybills', 'route_map', 'geoposition'], true)) {
            return null;
        }
        if ($field->type === 'deal_stages') {
            $details = json_decode((string) $field->details, true);
            $options = is_array($details) && is_array($details['options'] ?? null) ? $details['options'] : [];
            $current = is_scalar($raw) ? trim((string) $raw) : '';
            $option = null;
            foreach ($options as $item) {
                if (is_array($item) && isset($item['value']) && (string) $item['value'] === $current) {
                    $option = $item;
                    break;
                }
            }

            return array_merge($base, [
                'type' => 'status',
                'value' => $option ? (string) ($option['label'] ?? $current) : ($current !== '' ? $current : '—'),
                'color' => $option ? (($option['color'] ?? null) ?: ($option['b24_color'] ?? null)) : null,
                'file' => $option ? $this->statusFile($option['file'] ?? null) : null,
            ]);
        }
        if ($field->type === 'status') {
            $value = DB::table('field_values')->where('field_id', $field->id)->where('id', (int) $raw)->first(['value', 'color', 'file']);

            return $base + [
                'value' => $value->value ?? '—',
                'color' => $value->color ?? null,
                'file' => $value ? $this->statusFile($value->file) : null,
            ];
        }
        if ($field->field === 'products' || $field->type === 'json') {
            $products = $this->productsOf($row);

            return count($products) ? array_merge($base, ['type' => 'products', 'value' => $products]) : null;
        }
        if ($field->type === 'date') {
            $time = $raw ? strtotime((string) $raw) : false;
            if (!$time) {
                return $base + ['value' => '—'];
            }

            return $base + ['value' => date('H:i:s', $time) === '00:00:00' ? date('d.m.Y', $time) : date('d.m.Y H:i', $time)];
        }
        if ($field->type === 'checkbox') {
            $checked = \App\Models\Field::checkboxValue($raw);

            return $base + ['value' => $checked === null ? '—' : ($checked === '1' ? 'Да' : 'Нет')];
        }
        if ($field->field === 'name') {
            return $base + ['value' => $this->nameOf($row) ?: '—'];
        }
        $text = '';
        try {
            $human = \App\Models\Field::getHumanValue($field, $raw);
            $text = is_array($human['res'] ?? null) ? implode(', ', $human['res']) : (string) ($human['res'] ?? '');
        } catch (\Throwable $e) {
            $text = '';
        }
        if ($text === '') {
            $text = is_scalar($raw) ? trim((string) $raw) : '';
            if ($text !== '' && ($text[0] === '{' || $text[0] === '[')) {
                $decoded = json_decode($text, true);
                $text = is_array($decoded) ? (string) ($decoded['value'] ?? ($decoded['text'] ?? implode(', ', array_filter($decoded, 'is_scalar')))) : $text;
            }
        }
        $text = trim(html_entity_decode(strip_tags($text)));
        if ($field->type === 'number' && $text !== '' && trim((string) $field->unit) !== '') {
            $text .= ' ' . trim((string) $field->unit);
        }

        return $base + ['value' => $text !== '' ? $text : '—'];
    }

    private function productsOf($row): array
    {
        $result = [];
        foreach (\App\Services\ShipmentService::decode($row->products ?? null) as $product) {
            if (!is_array($product)) {
                continue;
            }
            $name = \App\Services\ShipmentService::plainName($product['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $result[] = ['name' => $name, 'count' => $product['count'] ?? 0];
        }

        return $result;
    }

    private function nameOf($row): string
    {
        $name = $row->name ?? '';
        if (is_string($name) && $name !== '' && ($name[0] === '{' || $name[0] === '[')) {
            $decoded = json_decode($name, true);
            if (is_array($decoded)) {
                $name = (string) ($decoded['value'] ?? reset($decoded));
            }
        }

        return trim((string) $name);
    }
}
