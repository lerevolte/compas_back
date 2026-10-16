<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ObjectRelation extends Model
{
    protected $table = 'object_relations';
    protected $guarded = ['id'];

    public const TAB = 'relations';
    public const TAB_TITLE = 'Связанные документы';

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('object_relations');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function link(string $sourceSlug, $sourceId, string $targetSlug, $targetId): void
    {
        if (!self::ready() || !$sourceId || !$targetId) {
            return;
        }

        if ($sourceSlug === $targetSlug && (int) $sourceId === (int) $targetId) {
            return;
        }

        try {
            self::firstOrCreate([
                'source_slug' => $sourceSlug,
                'source_id' => (int) $sourceId,
                'target_slug' => $targetSlug,
                'target_id' => (int) $targetId,
            ], [
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
        }

        self::ensureTab($sourceSlug);
        self::ensureTab($targetSlug);

        \App\Services\RelationFieldsService::refresh($sourceSlug, (int) $sourceId);
        \App\Services\RelationFieldsService::refresh($targetSlug, (int) $targetId);
        if ($targetSlug === \App\Services\ShipmentService::DOCUMENT && \App\Services\ShipmentService::isSource($sourceSlug)) {
            \App\Services\OneC\OneCExportService::refreshNumber($targetSlug, (int) $targetId);
        }
    }

    public static function ordinalOf(string $slug, int $id): ?int
    {
        if (!$id) {
            return null;
        }

        return self::ordinalsFor($slug, [$id])[$id] ?? null;
    }

    private static function aliveIds(string $slug, array $ids): array
    {
        $query = \DB::table($slug)->whereIn('id', $ids);
        if (\Schema::hasColumn($slug, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    private static function parentsFor(string $slug, array $ids): array
    {
        $byTarget = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            $rows = self::where('target_slug', $slug)->whereIn('target_id', $chunk)->orderBy('id')->get(['source_slug', 'source_id', 'target_id']);
            foreach ($rows as $row) {
                $byTarget[(int) $row->target_id][] = [(string) $row->source_slug, (int) $row->source_id];
            }
        }
        $parentOf = [];
        foreach ($byTarget as $targetId => $parents) {
            $parentOf[$targetId] = count($parents) === 1 ? $parents[0] : self::nearestParent($slug, $targetId);
        }

        return $parentOf;
    }

    public static function ordinalsFor(string $slug, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!count($ids) || !self::ready()) {
            return [];
        }
        $parentOf = self::parentsFor($slug, $ids);
        $bySource = [];
        foreach ($parentOf as $parent) {
            if ($parent) {
                $bySource[$parent[0]][$parent[1]] = true;
            }
        }
        if (!count($bySource)) {
            return [];
        }
        $siblings = [];
        foreach ($bySource as $sourceSlug => $sourceIds) {
            $rows = self::where('source_slug', $sourceSlug)->whereIn('source_id', array_keys($sourceIds))
                ->where('target_slug', $slug)->orderBy('id')->get(['source_id', 'target_id']);
            foreach ($rows as $row) {
                $siblings[$sourceSlug . ':' . (int) $row->source_id][] = (int) $row->target_id;
            }
        }
        $allTargets = array_values(array_unique(array_merge(...array_values($siblings))));
        try {
            $existing = array_flip(self::aliveIds($slug, $allTargets));
        } catch (\Throwable $e) {
            return [];
        }
        $siblingParents = $parentOf + self::parentsFor($slug, array_values(array_diff(array_keys($existing), array_keys($parentOf))));
        $result = [];
        foreach ($parentOf as $targetId => $parent) {
            if (!$parent) {
                continue;
            }
            $ordered = array_values(array_unique(array_filter(
                $siblings[$parent[0] . ':' . $parent[1]] ?? [],
                fn ($siblingId) => isset($existing[$siblingId]) && ($siblingParents[$siblingId] ?? null) === $parent
            )));
            if (count($ordered) < 2) {
                continue;
            }
            $position = array_search($targetId, $ordered, true);
            if ($position !== false) {
                $result[$targetId] = $position + 1;
            }
        }

        return $result;
    }

    public static function nearestParent(string $slug, int $id): ?array
    {
        if (!$id || !self::ready()) {
            return null;
        }
        $parents = self::where('target_slug', $slug)->where('target_id', $id)->orderBy('id')->get(['source_slug', 'source_id']);
        if (!count($parents)) {
            return null;
        }
        $first = [(string) $parents[0]->source_slug, (int) $parents[0]->source_id];
        if (count($parents) === 1) {
            return $first;
        }
        $ancestors = [];
        foreach ($parents as $parent) {
            $ancestors += self::ancestorKeys((string) $parent->source_slug, (int) $parent->source_id, [$slug . ':' . $id]);
        }
        foreach ($parents as $parent) {
            if (!isset($ancestors[$parent->source_slug . ':' . $parent->source_id])) {
                return [(string) $parent->source_slug, (int) $parent->source_id];
            }
        }

        return $first;
    }

    private static function ancestorKeys(string $slug, int $id, array $skip = []): array
    {
        $result = [];
        $seen = array_fill_keys($skip, true);
        $seen[$slug . ':' . $id] = true;
        $queue = [[$slug, $id]];
        $guard = 0;
        while (count($queue) && $guard++ < 200) {
            [$nodeSlug, $nodeId] = array_shift($queue);
            $parents = self::where('target_slug', $nodeSlug)->where('target_id', $nodeId)->get(['source_slug', 'source_id']);
            foreach ($parents as $parent) {
                $key = $parent->source_slug . ':' . $parent->source_id;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $result[$key] = true;
                $queue[] = [(string) $parent->source_slug, (int) $parent->source_id];
            }
        }

        return $result;
    }

    public static function exists(string $slugA, int $idA, string $slugB, int $idB): bool
    {
        if (!self::ready()) {
            return false;
        }
        try {
            return self::where(function ($q) use ($slugA, $idA, $slugB, $idB) {
                $q->where(['source_slug' => $slugA, 'source_id' => $idA, 'target_slug' => $slugB, 'target_id' => $idB]);
            })->orWhere(function ($q) use ($slugA, $idA, $slugB, $idB) {
                $q->where(['source_slug' => $slugB, 'source_id' => $idB, 'target_slug' => $slugA, 'target_id' => $idA]);
            })->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function linkWithHistory(string $sourceSlug, $sourceId, string $targetSlug, $targetId): bool
    {
        if (!self::ready() || !$sourceId || !$targetId) {
            return false;
        }
        if (self::exists($sourceSlug, (int) $sourceId, $targetSlug, (int) $targetId)) {
            return false;
        }
        self::link($sourceSlug, $sourceId, $targetSlug, $targetId);
        \App\Services\RelationFieldsService::writeLinkHistory($sourceSlug, (int) $sourceId, $targetSlug, (int) $targetId, true);

        return true;
    }

    public static function afterLink(string $sourceSlug, int $sourceId, string $targetSlug, int $targetId): void
    {
        \App\Services\ProductionService::afterLink($sourceSlug, $sourceId, $targetSlug, $targetId);
        try {
            if ($sourceSlug === 'deals' && \App\Services\ShipmentService::isSource($targetSlug)) {
                \App\Services\ShipmentService::setDealColumn($targetSlug, $targetId, $sourceId);
                \App\Services\ShipmentService::recalcForSource($targetSlug, $targetId);
                \App\Services\ShipmentService::recalcDealShipped($sourceId);
            }
            if ($targetSlug === \App\Services\ShipmentService::DOCUMENT) {
                \App\Services\ShipmentService::recalcForDocument($targetId);
            }
            if ($targetSlug === \App\Services\ShipmentService::RETURN_DOC) {
                ProductReturn::recalcParentShipments($targetId);
            }
            if ($targetSlug === \App\Services\ShipmentService::RECEIPT_DOC) {
                ReceiptInvoice::recalcParentShipments($targetId);
            }
        } catch (\Throwable $e) {
        }
    }

    public static function afterUnlink(string $slugA, int $idA, string $slugB, int $idB): void
    {
        \App\Services\ProductionService::afterUnlink($slugA, $idA, $slugB, $idB);
        foreach ([[$slugA, $idA], [$slugB, $idB]] as [$slug, $id]) {
            if ($slug === \App\Services\ShipmentService::DOCUMENT) {
                \App\Services\OneC\OneCExportService::refreshNumber($slug, $id);
            }
        }
        try {
            foreach ([[$slugA, $idA], [$slugB, $idB]] as [$slug, $id]) {
                if (\App\Services\ShipmentService::isSource($slug)) {
                    \App\Services\ShipmentService::recalcForSource($slug, $id);
                } elseif ($slug === 'deals') {
                    \App\Services\ShipmentService::recalcDealShipped($id);
                } elseif ($slug === \App\Services\ShipmentService::SUPPLIER) {
                    \App\Services\ShipmentService::recalcSupplierReceived($id);
                }
            }
        } catch (\Throwable $e) {
        }
    }

    public static function copyProducts(string $sourceSlug, $sourceId, string $targetSlug, $targetId, bool $dryRun = false): bool
    {
        if (!$sourceId || !$targetId) {
            return false;
        }

        try {
            $target = self::objectWithProducts($targetSlug, (int) $targetId);
            if (!$target || !method_exists($target, 'setProducts')) {
                return false;
            }
            if (self::decodeProducts($target->products)) {
                return false;
            }

            $source = self::objectWithProducts($sourceSlug, (int) $sourceId);
            if (!$source) {
                return false;
            }

            $products = self::decodeProducts($source->products);
            if ($targetSlug === \App\Services\ShipmentService::RETURN_DOC) {
                $products = \App\Services\ShipmentService::withoutServices($products);
            }
            if (!$products) {
                return false;
            }

            $isResidual = false;
            if ($targetSlug === \App\Services\ShipmentService::RETURN_DOC && \App\Services\ShipmentService::isSource($sourceSlug)) {
                $products = \App\Services\ShipmentService::returnDefaults($sourceSlug, (int) $sourceId, (int) $targetId);
                if (!count($products)) {
                    return false;
                }
                if (!$dryRun) {
                    $target->setProducts($products, null);
                }

                return true;
            }
            if (in_array($targetSlug, \App\Services\ShipmentService::childSlugsOf($sourceSlug, (int) $sourceId), true)) {
                $residual = \App\Services\ShipmentService::residualProducts($sourceSlug, (int) $sourceId, $products, [$targetSlug, (int) $targetId]);
                $isResidual = $residual !== $products;
                $products = $residual;
                if (!count($products)) {
                    return false;
                }
            }

            if (!$dryRun) {
                $sourceSum = (float) ($source->sum ?? 0);
                $target->setProducts($products, !$isResidual && $sourceSum > 0 ? $source->sum : null);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function copyB24Id(string $sourceSlug, $sourceId, string $targetSlug, $targetId, bool $dryRun = false): bool
    {
        if (!$sourceId || !$targetId) {
            return false;
        }

        try {
            if (!Schema::hasTable($sourceSlug) || !Schema::hasColumn($sourceSlug, 'b24_id')
                || !Schema::hasTable($targetSlug) || !Schema::hasColumn($targetSlug, 'b24_id')) {
                return false;
            }

            $current = \DB::table($targetSlug)->where('id', (int) $targetId)->value('b24_id');
            if ($current !== null && $current !== '') {
                return false;
            }

            $sourceColumns = ['b24_id'];
            if (Schema::hasColumn($sourceSlug, 'crm_link')) {
                $sourceColumns[] = 'crm_link';
            }
            $source = \DB::table($sourceSlug)->where('id', (int) $sourceId)->first($sourceColumns);
            if (!$source || $source->b24_id === null || $source->b24_id === '') {
                return false;
            }

            if (!$dryRun) {
                $update = ['b24_id' => $source->b24_id];
                if (Schema::hasColumn($targetSlug, 'crm_link') && ($source->crm_link ?? null)) {
                    $currentLink = \DB::table($targetSlug)->where('id', (int) $targetId)->value('crm_link');
                    if ($currentLink === null || $currentLink === '') {
                        $update['crm_link'] = $source->crm_link;
                    }
                }
                \DB::table($targetSlug)->where('id', (int) $targetId)->update($update);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function objectWithProducts(string $slug, int $id)
    {
        if (!Schema::hasTable($slug) || !Schema::hasColumn($slug, 'products')) {
            return null;
        }

        $class = \DB::table('data_types')->where('slug', $slug)->value('model_name');
        if (!$class || !class_exists($class)) {
            return null;
        }

        return $class::find($id);
    }

    private static function decodeProducts($value): array
    {
        if (is_array($value)) {
            $products = $value;
        } else {
            $products = json_decode((string) $value, true);
        }

        if (!is_array($products)) {
            return [];
        }

        return array_values(array_filter($products, 'is_array'));
    }

    public static function ensureTab(string $slug, $db = null, bool $remove = false): bool
    {
        $db = $db ?: \DB::connection();
        $changed = false;

        try {
            if (!$db->table('data_types')->where('slug', $slug)->exists()) {
                return false;
            }

            $menus = $db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->get();
            foreach ($menus as $menu) {
                $tabs = json_decode($menu->value, true);
                if (!is_array($tabs)) {
                    continue;
                }

                $has = collect($tabs)->contains(fn ($tab) => ($tab['tab'] ?? null) === self::TAB);
                if ($has === !$remove) {
                    continue;
                }

                $filtered = array_values(array_filter($tabs, fn ($tab) => ($tab['tab'] ?? null) !== self::TAB));

                if (!$remove) {
                    $maxSort = 0;
                    $maxId = 0;
                    foreach ($filtered as $tab) {
                        $maxSort = max($maxSort, (int) ($tab['sort'] ?? 0));
                        $maxId = max($maxId, (int) ($tab['id'] ?? 0));
                    }
                    $filtered[] = [
                        'title' => self::TAB_TITLE,
                        'tab' => self::TAB,
                        'sort' => $maxSort + 1,
                        'enabled' => 1,
                        'id' => $maxId + 1,
                        'has_roles_read' => false,
                        'roles_read' => null,
                    ];
                }

                $db->table('settings')->where('id', $menu->id)->update([
                    'value' => json_encode(array_values($filtered), JSON_UNESCAPED_SLASHES),
                ]);
                $changed = true;
            }

            if ($changed) {
                Settings::clear_cache();
            }
        } catch (\Throwable $e) {
        }

        return $changed;
    }
}
