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

        $printGenerated = \App\Services\SaleDocumentService::generateFor(
            $data['target_slug'],
            $data['target_id'],
            $data['source_slug'],
            $data['source_id']
        );

        return response()->json(['ok' => true, 'products_copied' => $productsCopied, 'b24_copied' => $b24Copied, 'print_generated' => $printGenerated]);
    }

    public function productsCheck($slug, $id)
    {
        $id = (int) $id;
        $empty = ['parent' => null, 'limits' => [], 'usage' => []];
        if (!ObjectRelation::ready()) {
            return response()->json($empty);
        }

        $usage = [];
        $childSlugs = \App\Services\ShipmentService::childSlugsOf($slug);
        if (count($childSlugs)) {
            $used = \App\Services\ShipmentService::usageByChildren($slug, $id, $childSlugs);
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
            $siblingSlugs = \App\Services\ShipmentService::childSlugsOf($parentSlug);
            $usedOthers = \App\Services\ShipmentService::usageByChildren($parentSlug, $parentId, $siblingSlugs, [$slug, $id]);
            $row = DB::table($parentSlug)->where('id', $parentId)->first();
            $products = \App\Services\ShipmentService::decode($row->products ?? null);
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

        return response()->json(['parent' => $parentInfo, 'limits' => $limits, 'usage' => $usage]);
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

        $docs = [];
        foreach ($flat as $node) {
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
            'products' => in_array($slug, array_merge([\App\Services\ShipmentService::DOCUMENT], \App\Services\ShipmentService::SOURCES), true) ? $this->productsOf($row) : [],
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
