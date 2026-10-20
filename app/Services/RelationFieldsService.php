<?php

namespace App\Services;

use App\Models\ObjectRelation;
use Illuminate\Support\Facades\DB;

class RelationFieldsService
{
    public const MODULE = 'relations';
    public const MODULE_TITLE = 'Связанные документы';
    public const SECTION_NAME = 'Связанные документы';

    public const ENTITIES = [
        'deals', 'supplier_orders', 'logistic_tasks', 'pickups',
        'payment_invoices', 'expense_invoices', 'product_returns', 'receipt_invoices', 'addresses', 'warehouses',
    ];

    public const RANK = [
        'deals' => 0, 'supplier_orders' => 0, 'addresses' => 0, 'warehouses' => 0,
        'logistic_tasks' => 1, 'pickups' => 1, 'payment_invoices' => 1,
        'expense_invoices' => 2, 'product_returns' => 2, 'receipt_invoices' => 2,
    ];

    public const PLURAL = [
        'deals' => ['supplier_orders', 'logistic_tasks', 'pickups', 'payment_invoices', 'expense_invoices', 'product_returns', 'receipt_invoices'],
        'supplier_orders' => ['logistic_tasks', 'receipt_invoices'],
        'logistic_tasks' => ['expense_invoices', 'product_returns', 'receipt_invoices'],
        'pickups' => ['expense_invoices', 'product_returns', 'receipt_invoices'],
        'payment_invoices' => ['expense_invoices', 'product_returns', 'receipt_invoices'],
        'addresses' => ['logistic_tasks', 'pickups'],
        'warehouses' => ['logistic_tasks', 'pickups'],
        'expense_invoices' => [],
        'product_returns' => [],
        'receipt_invoices' => [],
    ];

    public const EXCLUDE = [
        'deals' => ['supplier_orders', 'receipt_invoices', 'product_returns', 'addresses', 'warehouses'],
        'supplier_orders' => ['deals', 'pickups', 'expense_invoices', 'product_returns'],
        'logistic_tasks' => ['pickups', 'product_returns'],
        'pickups' => ['logistic_tasks', 'supplier_orders', 'receipt_invoices', 'product_returns', 'addresses', 'warehouses'],
        'payment_invoices' => ['supplier_orders', 'receipt_invoices', 'product_returns', 'addresses', 'warehouses'],
        'expense_invoices' => ['receipt_invoices', 'product_returns', 'addresses'],
        'receipt_invoices' => ['deals', 'pickups', 'payment_invoices', 'expense_invoices', 'product_returns', 'addresses', 'warehouses'],
        'addresses' => ['product_returns'],
        'warehouses' => ['product_returns'],
    ];

    public const LEGACY = [
        'logistic_tasks' => ['deals' => 'deal_id'],
        'pickups' => ['deals' => 'deal_id'],
    ];

    private static array $fieldsCache = [];

    public static function isEntity(string $slug): bool
    {
        return in_array($slug, self::ENTITIES, true);
    }

    public static function isExcluded(string $slug, string $target): bool
    {
        return in_array($target, self::EXCLUDE[$slug] ?? [], true);
    }

    public static function field(string $slug, string $target): string
    {
        return self::LEGACY[$slug][$target] ?? 'related_' . $target;
    }

    public static function isLegacy(string $slug, string $target): bool
    {
        return isset(self::LEGACY[$slug][$target]);
    }

    public static function isSingle(string $slug, string $target): bool
    {
        if (self::isLegacy($slug, $target)) {
            return true;
        }

        return !in_array($target, self::PLURAL[$slug] ?? [], true);
    }

    public static function singleValue($value): ?int
    {
        return self::ids($value)[0] ?? null;
    }

    public static function fieldsFor(string $slug, $db = null): array
    {
        $db = $db ?: DB::connection();
        $key = $db->getName() . ':' . $slug;
        if (isset(self::$fieldsCache[$key])) {
            return self::$fieldsCache[$key];
        }
        $result = [];
        try {
            if (self::isEntity($slug) && $db->getSchemaBuilder()->hasTable($slug)) {
                $columns = array_map('strtolower', $db->getSchemaBuilder()->getColumnListing($slug));
                foreach (self::ENTITIES as $target) {
                    if ($target === $slug || self::isExcluded($slug, $target)) {
                        continue;
                    }
                    $field = self::field($slug, $target);
                    if (in_array(strtolower($field), $columns, true)) {
                        $result[$target] = $field;
                    }
                }
            }
        } catch (\Throwable $e) {
            $result = [];
        }
        self::$fieldsCache[$key] = $result;

        return $result;
    }

    public static function forgetCache(): void
    {
        self::$fieldsCache = [];
    }

    public static function managedFieldsFor(string $slug): array
    {
        $result = [];
        foreach (self::fieldsFor($slug) as $target => $field) {
            if (!self::isLegacy($slug, $target)) {
                $result[$target] = $field;
            }
        }

        return $result;
    }

    public static function ids($value): array
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return [];
            }
            $decoded = json_decode($trimmed, true);
            $value = is_array($decoded) ? $decoded : (is_numeric($trimmed) ? [$trimmed] : []);
        }
        if (!is_array($value)) {
            $value = $value === null || $value === '' ? [] : [$value];
        }
        $ids = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $item = $item['id'] ?? ($item['value'] ?? null);
            }
            if ($item !== null && $item !== '' && is_numeric($item) && (int) $item > 0) {
                $ids[] = (int) $item;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function relatedIds(string $slug, int $id, string $target, $db = null): array
    {
        $db = $db ?: DB::connection();
        $ids = [];
        foreach ($db->table('object_relations')->where('source_slug', $slug)->where('source_id', $id)->where('target_slug', $target)->orderBy('id')->pluck('target_id') as $v) {
            $ids[] = (int) $v;
        }
        foreach ($db->table('object_relations')->where('target_slug', $slug)->where('target_id', $id)->where('source_slug', $target)->orderBy('id')->pluck('source_id') as $v) {
            $ids[] = (int) $v;
        }
        $ids = array_values(array_unique($ids));
        if (count($ids) && $db->getSchemaBuilder()->hasTable($target)) {
            $query = $db->table($target)->whereIn('id', $ids);
            if ($db->getSchemaBuilder()->hasColumn($target, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            $alive = array_map('intval', $query->pluck('id')->all());
            $ids = array_values(array_filter($ids, fn ($v) => in_array($v, $alive, true)));
        }

        return $ids;
    }

    public static function refresh(string $slug, int $id, $db = null): void
    {
        $db = $db ?: DB::connection();
        if (!$id || !self::isEntity($slug)) {
            return;
        }
        try {
            if (!$db->getSchemaBuilder()->hasTable('object_relations')) {
                return;
            }
            $fields = self::fieldsFor($slug, $db);
            if (!count($fields)) {
                return;
            }
            $row = $db->table($slug)->where('id', $id)->first(array_merge(['id'], array_values($fields)));
            if (!$row) {
                return;
            }
            $update = [];
            foreach ($fields as $target => $field) {
                $ids = self::relatedIds($slug, $id, $target, $db);
                if (self::isSingle($slug, $target)) {
                    $value = $ids[0] ?? null;
                    $current = self::ids($row->{$field})[0] ?? null;
                    if ($current !== $value) {
                        $update[$field] = $value;
                    }
                } else {
                    $current = self::ids($row->{$field});
                    sort($current);
                    $sorted = $ids;
                    sort($sorted);
                    if ($current !== $sorted) {
                        $update[$field] = json_encode($ids);
                    }
                }
            }
            if (count($update)) {
                $db->table($slug)->where('id', $id)->update($update);
            }
        } catch (\Throwable $e) {
            \Log::warning('relations: не удалось обновить поля связей', ['object' => $slug . '#' . $id, 'error' => $e->getMessage()]);
        }
    }

    public static function refreshNeighbours(string $slug, int $id): void
    {
        if (!$id || !self::isEntity($slug) || !ObjectRelation::ready()) {
            return;
        }
        $pairs = [];
        foreach (DB::table('object_relations')->where('source_slug', $slug)->where('source_id', $id)->get(['target_slug', 'target_id']) as $row) {
            $pairs[$row->target_slug . ':' . $row->target_id] = [(string) $row->target_slug, (int) $row->target_id];
        }
        foreach (DB::table('object_relations')->where('target_slug', $slug)->where('target_id', $id)->get(['source_slug', 'source_id']) as $row) {
            $pairs[$row->source_slug . ':' . $row->source_id] = [(string) $row->source_slug, (int) $row->source_id];
        }
        foreach ($pairs as [$otherSlug, $otherId]) {
            self::refresh($otherSlug, $otherId);
        }
    }

    public static function apply(string $slug, int $id, string $target, $oldValue, $newValue): void
    {
        if (!$id || !ObjectRelation::ready() || !self::isEntity($slug) || !self::isEntity($target)) {
            return;
        }
        $old = self::ids($oldValue);
        $new = self::ids($newValue);
        if (self::isSingle($slug, $target)) {
            $new = array_slice($new, 0, 1);
            $old = array_values(array_unique(array_merge($old, self::relatedIds($slug, $id, $target))));
        }
        $removed = array_values(array_diff($old, $new));
        $added = array_values(array_diff($new, $old));
        if (!count($removed) && !count($added)) {
            return;
        }

        foreach ($removed as $targetId) {
            self::unlink($slug, $id, $target, $targetId);
        }
        foreach ($added as $targetId) {
            self::linkPair($slug, $id, $target, $targetId);
        }

        self::refresh($slug, $id);
        foreach (array_merge($removed, $added) as $targetId) {
            self::refresh($target, $targetId);
        }
    }

    public static function linkPair(string $slug, int $id, string $target, int $targetId): void
    {
        if ($slug === $target && $id === $targetId) {
            return;
        }
        $exists = ObjectRelation::where(function ($q) use ($slug, $id, $target, $targetId) {
            $q->where(['source_slug' => $slug, 'source_id' => $id, 'target_slug' => $target, 'target_id' => $targetId]);
        })->orWhere(function ($q) use ($slug, $id, $target, $targetId) {
            $q->where(['source_slug' => $target, 'source_id' => $targetId, 'target_slug' => $slug, 'target_id' => $id]);
        })->exists();
        if ($exists) {
            return;
        }
        $rankA = self::RANK[$slug] ?? 1;
        $rankB = self::RANK[$target] ?? 1;
        if ($rankB < $rankA) {
            ObjectRelation::link($target, $targetId, $slug, $id);
            ObjectRelation::afterLink($target, $targetId, $slug, $id);
        } else {
            ObjectRelation::link($slug, $id, $target, $targetId);
            ObjectRelation::afterLink($slug, $id, $target, $targetId);
        }
    }

    public static function unlink(string $slug, int $id, string $target, int $targetId): void
    {
        $deleted = ObjectRelation::where(function ($q) use ($slug, $id, $target, $targetId) {
            $q->where(['source_slug' => $slug, 'source_id' => $id, 'target_slug' => $target, 'target_id' => $targetId]);
        })->orWhere(function ($q) use ($slug, $id, $target, $targetId) {
            $q->where(['source_slug' => $target, 'source_id' => $targetId, 'target_slug' => $slug, 'target_id' => $id]);
        })->delete();
        if ($deleted) {
            ObjectRelation::afterUnlink($slug, $id, $target, $targetId);
        }
    }

    public static function objectsWithRelations($db = null): array
    {
        $db = $db ?: DB::connection();
        if (!$db->getSchemaBuilder()->hasTable('object_relations')) {
            return [];
        }
        $pairs = [];
        foreach ($db->table('object_relations')->get(['source_slug', 'source_id', 'target_slug', 'target_id']) as $relation) {
            $pairs[$relation->source_slug . ':' . $relation->source_id] = [(string) $relation->source_slug, (int) $relation->source_id];
            $pairs[$relation->target_slug . ':' . $relation->target_id] = [(string) $relation->target_slug, (int) $relation->target_id];
        }

        return array_values(array_filter($pairs, fn ($pair) => self::isEntity($pair[0])));
    }

    public static function writeLinkHistory(string $slugA, int $idA, string $slugB, int $idB, bool $added = true): void
    {
        if (!$idA || !$idB) {
            return;
        }
        $items = [];
        foreach ([[$slugA, $idA, $slugB, $idB], [$slugB, $idB, $slugA, $idA]] as [$slug, $id, $target, $targetId]) {
            try {
                $item = self::linkHistoryFor($slug, $id, $target, $targetId, $added);
                if ($item) {
                    $items[] = $item;
                }
            } catch (\Throwable $e) {
                \Log::warning('relations: не удалось записать историю связи', ['object' => $slug . '#' . $id, 'target' => $target . '#' . $targetId, 'error' => $e->getMessage()]);
            }
        }
        if (count($items)) {
            try {
                \App\Events\ObjectUpdated::dispatch('HistoryUpdated', \App\Models\History::getDataList($items));
            } catch (\Throwable $e) {
            }
        }
    }

    private static function linkHistoryFor(string $slug, int $id, string $target, int $targetId, bool $added): ?\App\Models\History
    {
        if (!DB::getSchemaBuilder()->hasTable($slug) || !DB::getSchemaBuilder()->hasTable($target)) {
            return null;
        }
        $typeId = DB::table('data_types')->where('slug', $slug)->value('id');
        if (!$typeId) {
            return null;
        }
        $fieldName = self::field($slug, $target);
        $field = DB::table('data_rows')->where('data_type_id', $typeId)->where('field', $fieldName)->first(['title']);
        $targetType = DB::table('data_types')->where('slug', $target)->first(['title_singular', 'title_plural']);
        $title = trim((string) ($field->title ?? '')) ?: (trim((string) ($targetType->title_plural ?? '')) ?: $target);
        $singular = trim((string) ($targetType->title_singular ?? '')) ?: $target;

        $ids = self::relatedIds($slug, $id, $target);
        $others = array_values(array_filter($ids, fn ($v) => $v !== $targetId));
        $before = $added ? $others : array_values(array_unique(array_merge($others, [$targetId])));
        $after = $added ? array_values(array_unique(array_merge($others, [$targetId]))) : $others;
        $names = self::linkNames($target, array_values(array_unique(array_merge($before, $after))), $singular);
        $link = fn (int $v) => "<span data-slug='{$target}' data-id='{$v}'>" . ($names[$v] ?? ($singular . ' #' . $v)) . '</span>';
        $plain = fn (array $list) => implode(', ', array_map(fn ($v) => $names[$v] ?? ($singular . ' #' . $v), $list));
        $links = fn (array $list) => implode(', ', array_map($link, $list));

        $base = [
            'entity' => $slug,
            'entity_id' => $id,
            'user_id' => \App\Models\History::currentUserId() ?: 1,
            'field' => $fieldName,
            'old_value' => $plain($before),
            'new_value' => $plain($after),
            'is_relation' => 1,
        ];
        $history = new \App\Models\History($base + [
            'text' => $title . ': ' . $links($before) . ' -> ' . $links($after),
            'event' => 'FIELD_UPDATED',
        ]);
        $history->saveQuietly();

        $history = new \App\Models\History($base + [
            'text' => $title . ', ' . ($added ? 'добавлена связь: ' : 'удалена связь: ') . $link($targetId),
            'event' => $added ? 'RELATION_ADDED' : 'RELATION_DELETED',
            'color' => $added ? '#23704B' : '#C74822',
        ]);
        $history->saveQuietly();

        return $history;
    }

    private static function linkNames(string $slug, array $ids, string $singular): array
    {
        $result = [];
        if (!count($ids)) {
            return $result;
        }
        $columns = ['id'];
        foreach (['name', 'last_name', 'middle_name'] as $column) {
            if (DB::getSchemaBuilder()->hasColumn($slug, $column)) {
                $columns[] = $column;
            }
        }
        foreach (DB::table($slug)->whereIn('id', $ids)->get($columns) as $row) {
            $name = $row->name ?? '';
            if (is_string($name) && $name !== '' && ($name[0] === '{' || $name[0] === '[')) {
                $decoded = json_decode($name, true);
                if (is_array($decoded)) {
                    $name = (string) ($decoded['value'] ?? reset($decoded));
                }
            }
            $name = trim(implode(' ', array_filter([trim((string) $name), trim((string) ($row->last_name ?? '')), trim((string) ($row->middle_name ?? ''))])));
            $result[(int) $row->id] = $name !== '' ? $name : $singular . ' #' . $row->id;
        }

        return $result;
    }
}
