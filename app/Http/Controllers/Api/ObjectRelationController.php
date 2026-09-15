<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ObjectRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ObjectRelationController extends Controller
{
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

        ObjectRelation::link($data['source_slug'], $data['source_id'], $data['target_slug'], $data['target_id']);
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

        if ($data['target_slug'] === \App\Services\ShipmentService::DOCUMENT) {
            \App\Services\ShipmentService::recalcForDocument((int) $data['target_id']);
        }
        if ($data['target_slug'] === \App\Services\ShipmentService::RETURN_DOC) {
            \App\Models\ProductReturn::recalcParentShipments((int) $data['target_id']);
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
            $used = \App\Services\ShipmentService::usageByChildren($slug, $id, $childSlugs);
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

        $parent = \App\Services\ShipmentService::parentOf($slug, $id);
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
            if ($slug === \App\Services\ShipmentService::RETURN_DOC && \App\Services\ShipmentService::isSource($parentSlug) && !\App\Services\ShipmentService::isLoading($parentSlug, (int) $parentId)) {
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
                $siblingSlugs = \App\Services\ShipmentService::childSlugsOf($parentSlug, (int) $parentId);
                $usedOthers = \App\Services\ShipmentService::usageByChildren($parentSlug, $parentId, $siblingSlugs, [$slug, $id]);
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

        return response()->json(['parent' => $parentInfo, 'limits' => $limits, 'usage' => $usage, 'loading' => $loading]);
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
        $tree = $this->buildNode($root['slug'], $root['id'], (int) $id, $slug, []);

        return response()->json(['data' => $tree]);
    }

    private function ancestorDocuments(string $slug, int $id): array
    {
        $targets = array_keys(\App\Services\SaleDocumentService::TARGETS);
        $result = [];
        $guard = 0;
        $visited = [$slug . ':' . $id];
        while ($guard++ < 20) {
            $parent = ObjectRelation::where('target_slug', $slug)
                ->where('target_id', $id)
                ->orderBy('id')
                ->first(['source_slug', 'source_id']);
            if (!$parent) {
                break;
            }
            $slug = (string) $parent->source_slug;
            $id = (int) $parent->source_id;
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
            $parent = ObjectRelation::where('target_slug', $slug)
                ->where('target_id', $id)
                ->orderBy('id')
                ->first(['source_slug', 'source_id']);

            if (!$parent) {
                break;
            }

            $slug = $parent->source_slug;
            $id = (int) $parent->source_id;
        }

        return ['slug' => $slug, 'id' => $id];
    }

    private function buildNode(string $slug, int $id, int $currentId, string $currentSlug, array $visited): ?array
    {
        $key = $slug . ':' . $id;
        if (in_array($key, $visited, true) || count($visited) > 50) {
            return null;
        }
        $visited[] = $key;

        $object = $this->objectInfo($slug, $id);
        if (!$object) {
            return null;
        }

        $children = [];
        foreach (ObjectRelation::where('source_slug', $slug)->where('source_id', $id)->orderBy('id')->get() as $relation) {
            $child = $this->buildNode($relation->target_slug, (int) $relation->target_id, $currentId, $currentSlug, $visited);
            if ($child) {
                $children[] = $child;
            }
        }

        return $object + [
            'is_current' => $slug === $currentSlug && $id === $currentId,
            'children' => $children,
        ];
    }

    private function objectInfo(string $slug, int $id): ?array
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
            'products' => in_array($slug, array_merge([\App\Services\ShipmentService::DOCUMENT, \App\Services\ShipmentService::RETURN_DOC, \App\Services\ShipmentService::SUPPLIER], \App\Services\ShipmentService::SOURCES), true) ? $this->productsOf($row) : [],
        ];
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
