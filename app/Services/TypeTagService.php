<?php

namespace App\Services;

use App\Models\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TypeTagService
{
    public static function ids($value): array
    {
        if (is_string($value) && is_array($decoded = json_decode($value, true))) {
            $value = $decoded;
        }
        $ids = is_array($value) ? $value : [$value];
        return array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
    }

    public static function field(string $table, string $title): ?object
    {
        $typeId = DB::table('data_types')->where('slug', $table)->value('id');
        $row = $typeId ? DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('type', 'select_dropdown')
            ->where('title', $title)
            ->where('is_remove', 0)
            ->first() : null;
        if (!$row || !Schema::hasColumn($table, $row->field)) {
            return null;
        }
        return $row;
    }

    public static function optionValue(object $row, string $label)
    {
        $details = json_decode((string) $row->details, true);
        foreach ((is_array($details) ? ($details['options'] ?? []) : []) as $option) {
            $text = is_array($option) ? ($option['label'] ?? '') : '';
            $text = is_array($text) ? ($text['text'] ?? '') : $text;
            if (mb_strtolower(trim((string) $text)) === mb_strtolower(trim($label))) {
                return $option['value'];
            }
        }
        return null;
    }

    public static function current($raw): array
    {
        $current = is_string($raw) && is_array($decoded = json_decode($raw, true)) ? $decoded : ($raw === null || $raw === '' ? [] : [$raw]);
        return array_values(array_filter($current, fn ($v) => $v !== null && $v !== ''));
    }

    public static function add(string $table, string $title, $ids, string $label, ?callable $afterUpdate = null): int
    {
        return self::apply($table, $title, $ids, $label, true, $afterUpdate);
    }

    public static function remove(string $table, string $title, $ids, string $label, ?callable $afterUpdate = null): int
    {
        return self::apply($table, $title, $ids, $label, false, $afterUpdate);
    }

    private static function apply(string $table, string $title, $ids, string $label, bool $add, ?callable $afterUpdate): int
    {
        $ids = self::ids($ids);
        if (!count($ids)) {
            return 0;
        }
        try {
            $row = self::field($table, $title);
            if (!$row) {
                return 0;
            }
            $optionValue = self::optionValue($row, $label);
            if ($optionValue === null) {
                return 0;
            }
            $updated = 0;
            foreach (DB::table($table)->whereIn('id', $ids)->get(['id', $row->field]) as $item) {
                $current = self::current($item->{$row->field});
                $has = in_array((string) $optionValue, array_map('strval', $current), true);
                if ($add === $has) {
                    continue;
                }
                $next = $add
                    ? array_merge($current, [$optionValue])
                    : array_values(array_filter($current, fn ($v) => (string) $v !== (string) $optionValue));
                if ($row->is_plural) {
                    $historyValue = $next;
                    $stored = json_encode($next);
                } else {
                    $historyValue = $add ? (string) $optionValue : null;
                    $stored = $historyValue;
                }
                try {
                    History::saveForObject($table, [['id' => $item->id, $row->field => $historyValue]], true, [], [], true);
                } catch (\Throwable $e) {
                }
                DB::table($table)->where('id', $item->id)->update([$row->field => $stored, 'updated_at' => now()]);
                if ($afterUpdate) {
                    $afterUpdate((int) $item->id, $row->field);
                }
                $updated++;
            }
            return $updated;
        } catch (\Throwable $e) {
            Log::warning('type auto-tag failed', ['table' => $table, 'label' => $label, 'add' => $add, 'error' => $e->getMessage()]);
            return 0;
        }
    }
}
