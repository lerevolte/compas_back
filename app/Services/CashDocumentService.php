<?php

namespace App\Services;

use App\Models\ObjectRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashDocumentService
{
    public const SLUG = 'cash_documents';
    public const OPERATIONS = ['cash_incomes' => 1, 'cash_expenses' => -1];
    public const OPERATIONS_FIELD = 'operations';
    public const SUM_FIELD = 'sum';

    private static array $ready = [];

    public static function ready(): bool
    {
        $key = (string) (function_exists('tenant') ? tenant('id') : '');
        if (!array_key_exists($key, self::$ready)) {
            try {
                self::$ready[$key] = ObjectRelation::ready()
                    && Schema::hasTable(self::SLUG)
                    && Schema::hasColumn(self::SLUG, self::OPERATIONS_FIELD);
            } catch (\Throwable $e) {
                self::$ready[$key] = false;
            }
        }

        return self::$ready[$key];
    }

    public static function forget(): void
    {
        self::$ready = [];
    }

    public static function onRelation(string $sourceSlug, int $sourceId, string $targetSlug, int $targetId): void
    {
        if (!self::ready()) {
            return;
        }
        if ($sourceSlug === self::SLUG && isset(self::OPERATIONS[$targetSlug])) {
            self::recalc($sourceId);
        } elseif ($targetSlug === self::SLUG && isset(self::OPERATIONS[$sourceSlug])) {
            self::recalc($targetId);
        }
    }

    public static function recalcForOperation(string $slug, int $id): void
    {
        if (!self::ready() || !isset(self::OPERATIONS[$slug]) || !$id) {
            return;
        }
        $documentIds = ObjectRelation::where('source_slug', self::SLUG)->where('target_slug', $slug)->where('target_id', $id)->pluck('source_id')
            ->merge(ObjectRelation::where('target_slug', self::SLUG)->where('source_slug', $slug)->where('source_id', $id)->pluck('target_id'))
            ->map(fn ($v) => (int) $v)->unique();
        foreach ($documentIds as $documentId) {
            self::recalc($documentId);
        }
    }

    public static function recalc(int $documentId): void
    {
        if (!self::ready() || !$documentId) {
            return;
        }
        try {
            $document = DB::table(self::SLUG)->where('id', $documentId)->first(['id', self::OPERATIONS_FIELD, self::SUM_FIELD]);
            if (!$document) {
                return;
            }
            $rows = [];
            foreach (self::OPERATIONS as $slug => $sign) {
                if (!Schema::hasTable($slug)) {
                    continue;
                }
                $ids = ObjectRelation::where('source_slug', self::SLUG)->where('source_id', $documentId)->where('target_slug', $slug)->pluck('target_id')
                    ->merge(ObjectRelation::where('target_slug', self::SLUG)->where('target_id', $documentId)->where('source_slug', $slug)->pluck('source_id'))
                    ->map(fn ($v) => (int) $v)->unique()->values()->all();
                if (!count($ids)) {
                    continue;
                }
                $query = DB::table($slug)->whereIn('id', $ids);
                if (Schema::hasColumn($slug, 'deleted_at')) {
                    $query->whereNull('deleted_at');
                }
                foreach ($query->get() as $row) {
                    $rows[] = ['sign' => $sign, 'row' => $row, 'order' => (string) ($row->date ?? '') . '|' . (string) ($row->created_at ?? '') . '|' . $row->id];
                }
            }
            usort($rows, fn ($a, $b) => strcmp($a['order'], $b['order']));
            $lines = [];
            $total = 0.0;
            foreach ($rows as $item) {
                $amount = self::number($item['row']->sum ?? null);
                $total += $item['sign'] * $amount;
                $comment = self::text($item['row']->comment ?? null);
                $lines[] = ($item['sign'] > 0 ? '+' : '−') . self::format($amount) . ($comment !== '' ? ' — ' . $comment : '');
            }
            $update = [
                self::OPERATIONS_FIELD => count($lines) ? json_encode($lines, JSON_UNESCAPED_UNICODE) : null,
                self::SUM_FIELD => count($rows) ? self::format($total, false) : null,
            ];
            $changed = false;
            foreach ($update as $column => $value) {
                if ((string) ($document->{$column} ?? '') !== (string) ($value ?? '')) {
                    $changed = true;
                }
            }
            if (!$changed) {
                return;
            }
            DB::table(self::SLUG)->where('id', $documentId)->update($update);
            try {
                $model = \App\Models\CashDocument::find($documentId);
                if ($model) {
                    \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $model->getData([self::OPERATIONS_FIELD, self::SUM_FIELD]));
                }
            } catch (\Throwable $e) {
            }
        } catch (\Throwable $e) {
            \Log::warning('cash documents: пересчёт не удался', ['document' => $documentId, 'error' => $e->getMessage()]);
        }
    }

    private static function number($value): float
    {
        if (is_string($value)) {
            $value = str_replace([' ', ','], ['', '.'], trim($value));
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private static function text($value): string
    {
        $decoded = is_string($value) ? json_decode($value, true) : null;
        if (is_array($decoded)) {
            $value = implode(' ', array_filter(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $decoded), fn ($v) => $v !== ''));
        }

        return trim((string) $value);
    }

    private static function format(float $value, bool $spaces = true): string
    {
        $text = number_format(abs($value) < 0.00001 ? 0 : $value, 2, '.', $spaces ? ' ' : '');
        $text = rtrim(rtrim($text, '0'), '.');

        return $text === '-0' ? '0' : $text;
    }
}
