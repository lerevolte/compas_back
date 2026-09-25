<?php

namespace App\Services;

use App\Models\History;
use App\Models\ObjectRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StorehouseService
{
    public const TABLE = 'storehouses';
    public const WRITE_OFF = 'storehouse_id';
    public const RECEIPT = 'receipt_storehouse_id';

    public const TITLES = [
        self::WRITE_OFF => 'Склад списания',
        self::RECEIPT => 'Склад прихода',
    ];

    public const FIELDS = [
        'logistic_tasks' => [self::WRITE_OFF, self::RECEIPT],
        'pickups' => [self::WRITE_OFF],
        'expense_invoices' => [self::WRITE_OFF],
        'product_returns' => [self::RECEIPT],
        'receipt_invoices' => [self::RECEIPT],
    ];

    public const CHILDREN = [
        'expense_invoices' => ['field' => self::WRITE_OFF, 'parents' => ['logistic_tasks', 'pickups']],
        'product_returns' => ['field' => self::RECEIPT, 'parents' => ['logistic_tasks', 'pickups']],
        'receipt_invoices' => ['field' => self::RECEIPT, 'parents' => ['logistic_tasks', 'pickups', 'supplier_orders']],
    ];

    private static array $readyCache = [];
    private static array $warnings = [];

    public static function has(string $slug, string $field): bool
    {
        $key = $slug . ':' . $field;
        if (array_key_exists($key, self::$readyCache)) {
            return self::$readyCache[$key];
        }
        $result = false;
        try {
            if (Schema::hasTable(self::TABLE) && Schema::hasTable($slug) && Schema::hasColumn($slug, $field)) {
                $typeId = DB::table('data_types')->where('slug', $slug)->value('id');
                $result = $typeId && DB::table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->where('is_remove', 0)->exists();
            }
        } catch (\Throwable $e) {
            $result = false;
        }
        return self::$readyCache[$key] = $result;
    }

    public static function normalize($value): ?int
    {
        if (is_string($value) && is_array($decoded = json_decode($value, true))) {
            $value = $decoded;
        }
        if (is_array($value)) {
            $value = array_key_exists('value', $value) ? $value['value'] : (array_values(array_filter($value, 'is_numeric'))[0] ?? null);
            if (is_array($value)) {
                $value = array_values(array_filter($value, 'is_numeric'))[0] ?? null;
            }
        }
        return $value !== null && $value !== '' && is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public static function valueOf(string $slug, int $id, string $field): ?int
    {
        if (!$id || !self::has($slug, $field)) {
            return null;
        }
        return self::normalize(DB::table($slug)->where('id', $id)->value($field));
    }

    public static function name(?int $id): string
    {
        if (!$id) {
            return '—';
        }
        $name = DB::table(self::TABLE)->where('id', $id)->value('name');
        return $name !== null && $name !== '' ? (string) $name : ('Склад #' . $id);
    }

    public static function documentLabel(string $slug, int $id): string
    {
        $title = (string) (DB::table('data_types')->where('slug', $slug)->value('title_singular') ?: $slug);
        $name = Schema::hasColumn($slug, 'name') ? DB::table($slug)->where('id', $id)->value('name') : null;
        if (is_string($name) && is_array($decoded = json_decode($name, true))) {
            $name = $decoded['value'] ?? null;
        }
        return $name ? $title . ' «' . $name . '»' : $title . ' #' . $id;
    }

    public static function pairErrors(string $parentSlug, int $parentId, string $childSlug, ?int $childValue): array
    {
        $rule = self::CHILDREN[$childSlug] ?? null;
        if (!$rule || !in_array($parentSlug, $rule['parents'], true) || !$childValue) {
            return [];
        }
        $field = $rule['field'];
        if (!self::has($childSlug, $field)) {
            return [];
        }
        $parentValue = self::valueOf($parentSlug, $parentId, $field);
        if (!$parentValue || $parentValue === $childValue) {
            return [];
        }
        return [self::TITLES[$field] . ' «' . self::name($childValue) . '» не совпадает со складом в документе-основании '
            . self::documentLabel($parentSlug, $parentId) . ' — «' . self::name($parentValue) . '»'];
    }

    public static function parentsOf(string $childSlug, int $childId): array
    {
        $rule = self::CHILDREN[$childSlug] ?? null;
        if (!$rule || !$childId || !ObjectRelation::ready()) {
            return [];
        }
        return ObjectRelation::whereIn('source_slug', $rule['parents'])
            ->where('target_slug', $childSlug)
            ->where('target_id', $childId)
            ->get(['source_slug', 'source_id'])
            ->map(fn ($r) => [(string) $r->source_slug, (int) $r->source_id])
            ->all();
    }

    public static function rowErrors(string $slug, int $id, array $row): array
    {
        $rule = self::CHILDREN[$slug] ?? null;
        if (!$rule || !$id) {
            return [];
        }
        $field = $rule['field'];
        if (!self::has($slug, $field)) {
            return [];
        }
        try {
            $childValue = array_key_exists($field, $row) ? self::normalize($row[$field]) : self::valueOf($slug, $id, $field);
            $errors = [];
            $parents = array_key_exists($field, $row) ? self::parentsOf($slug, $id) : [];
            foreach (RelationFieldsService::managedFieldsFor($slug) as $target => $relField) {
                if (!in_array($target, $rule['parents'], true) || !array_key_exists($relField, $row)) {
                    continue;
                }
                $current = RelationFieldsService::ids(DB::table($slug)->where('id', $id)->value($relField));
                foreach (array_diff(RelationFieldsService::ids($row[$relField]), $current) as $parentId) {
                    $parents[] = [$target, (int) $parentId];
                }
            }
            foreach ($parents as [$parentSlug, $parentId]) {
                $errors = array_merge($errors, self::pairErrors($parentSlug, $parentId, $slug, $childValue));
            }
            return array_values(array_unique($errors));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function draftErrors(string $parentSlug, int $parentId, string $childSlug, array $fields): array
    {
        $rule = self::CHILDREN[$childSlug] ?? null;
        if (!$rule || !array_key_exists($rule['field'], $fields)) {
            return [];
        }
        return self::pairErrors($parentSlug, $parentId, $childSlug, self::normalize($fields[$rule['field']]));
    }

    public static function linkErrors(string $parentSlug, int $parentId, string $childSlug, int $childId): array
    {
        $rule = self::CHILDREN[$childSlug] ?? null;
        if (!$rule) {
            return [];
        }
        return self::pairErrors($parentSlug, $parentId, $childSlug, self::valueOf($childSlug, $childId, $rule['field']));
    }

    public static function inheritOnLink(string $parentSlug, int $parentId, string $childSlug, int $childId): void
    {
        $rule = self::CHILDREN[$childSlug] ?? null;
        if (!$rule || !in_array($parentSlug, $rule['parents'], true)) {
            return;
        }
        $field = $rule['field'];
        if (!self::has($childSlug, $field) || self::valueOf($childSlug, $childId, $field)) {
            return;
        }
        $parentValue = self::valueOf($parentSlug, $parentId, $field);
        if ($parentValue) {
            self::write($childSlug, $childId, $field, $parentValue);
        }
    }

    public static function cascadeFromParent(string $parentSlug, int $parentId, string $field): void
    {
        if (!ObjectRelation::ready() || !self::has($parentSlug, $field)) {
            return;
        }
        $value = self::valueOf($parentSlug, $parentId, $field);
        if (!$value) {
            return;
        }
        $childSlugs = [];
        foreach (self::CHILDREN as $childSlug => $rule) {
            if ($rule['field'] === $field && in_array($parentSlug, $rule['parents'], true) && self::has($childSlug, $field)) {
                $childSlugs[] = $childSlug;
            }
        }
        if (!count($childSlugs)) {
            return;
        }
        $changed = [];
        $children = ObjectRelation::where('source_slug', $parentSlug)
            ->where('source_id', $parentId)
            ->whereIn('target_slug', $childSlugs)
            ->get(['target_slug', 'target_id']);
        foreach ($children as $child) {
            $childSlug = (string) $child->target_slug;
            $childId = (int) $child->target_id;
            $query = DB::table($childSlug)->where('id', $childId);
            if (Schema::hasColumn($childSlug, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            if (!$query->exists()) {
                continue;
            }
            $current = self::valueOf($childSlug, $childId, $field);
            if ($current === $value) {
                continue;
            }
            self::write($childSlug, $childId, $field, $value);
            $changed[] = self::documentLabel($childSlug, $childId);
        }
        if (count($changed)) {
            self::$warnings[] = self::TITLES[$field] . ' изменён на «' . self::name($value) . '» также в связанных документах: ' . implode(', ', $changed);
        }
    }

    public static function pullWarnings(): array
    {
        $warnings = array_values(array_unique(self::$warnings));
        self::$warnings = [];
        return $warnings;
    }

    private static function write(string $slug, int $id, string $field, int $value): void
    {
        try {
            History::saveForObject($slug, [['id' => $id, $field => $value]], true, [], [], true);
        } catch (\Throwable $e) {
        }
        DB::table($slug)->where('id', $id)->update([$field => $value]);
        try {
            $class = DB::table('data_types')->where('slug', $slug)->value('model_name');
            $object = $class && class_exists($class) ? $class::find($id) : null;
            if ($object && method_exists($object, 'getData')) {
                \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $object->getData([$field]));
            }
        } catch (\Throwable $e) {
        }
    }
}
