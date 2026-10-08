<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashDocumentService
{
    public const SLUG = 'cash_documents';
    public const OPERATIONS = [
        'cash_incomes' => ['kind' => 'income', 'model' => \App\Models\CashIncome::class],
        'cash_expenses' => ['kind' => 'expense', 'model' => \App\Models\CashExpense::class],
    ];
    public const KIND_FIELD = 'operation_kind';
    public const SUM_FIELD = 'sum';

    private static array $ready = [];
    private static bool $cascading = false;

    public static function ready(): bool
    {
        $key = (string) (function_exists('tenant') ? tenant('id') : '');
        if (!array_key_exists($key, self::$ready)) {
            try {
                self::$ready[$key] = Schema::hasTable(self::SLUG)
                    && Schema::hasColumn(self::SLUG, 'operation_id')
                    && Schema::hasColumn(self::SLUG, 'operation_slug');
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

    public static function operationFor(int $documentId): ?array
    {
        if (!self::ready() || !$documentId) {
            return null;
        }
        $document = DB::table(self::SLUG)->where('id', $documentId)->first(['operation_slug', 'operation_id']);
        if (!$document || !isset(self::OPERATIONS[$document->operation_slug]) || !$document->operation_id) {
            return null;
        }

        return ['slug' => $document->operation_slug, 'id' => (int) $document->operation_id];
    }

    public static function fillDate($model): void
    {
        if ($model->date !== null && trim((string) $model->date) !== '') {
            return;
        }
        $created = $model->created_at ?: now();
        $model->date = \Carbon\Carbon::parse($created)->format('Y-m-d');
    }

    public static function syncOperation(string $slug, int $id): void
    {
        if (!$id || !isset(self::OPERATIONS[$slug]) || !self::ready()) {
            return;
        }
        try {
            $operation = DB::table($slug)->where('id', $id)->first();
            $document = DB::table(self::SLUG)->where('operation_slug', $slug)->where('operation_id', $id)->orderBy('id')->first();
            if (!$operation || $operation->deleted_at) {
                if ($document && !$document->deleted_at) {
                    DB::table(self::SLUG)->where('id', $document->id)->update(['deleted_at' => now()]);
                    self::dispatch('ObjectDeleted', (int) $document->id);
                }
                return;
            }
            $attrs = self::attributes($slug, $operation);
            if ($document) {
                $changed = (bool) $document->deleted_at;
                foreach ($attrs as $column => $value) {
                    if ((string) ($document->{$column} ?? '') !== (string) ($value ?? '')) {
                        $changed = true;
                    }
                }
                if (!$changed) {
                    return;
                }
                DB::table(self::SLUG)->where('id', $document->id)->update($attrs + ['deleted_at' => null, 'updated_at' => now()]);
                self::dispatch($document->deleted_at ? 'ObjectCreated' : 'ObjectUpdated', (int) $document->id);
                return;
            }
            $documentId = (int) DB::table(self::SLUG)->insertGetId($attrs + [
                'operation_slug' => $slug,
                'operation_id' => $id,
                'created_at' => $operation->created_at ?? now(),
                'updated_at' => now(),
            ]);
            self::dispatch('ObjectCreated', $documentId);
        } catch (\Throwable $e) {
            \Log::warning('cash documents: синхронизация операции не удалась', ['slug' => $slug, 'id' => $id, 'error' => $e->getMessage()]);
        }
    }

    public static function onDocumentDeleted($document): void
    {
        self::cascade($document, false);
    }

    public static function onDocumentRestored($document): void
    {
        self::cascade($document, true);
    }

    public static function sumColors(): array
    {
        $colors = [];
        try {
            $rows = DB::table('data_rows as r')
                ->join('data_types as t', 't.id', '=', 'r.data_type_id')
                ->whereIn('t.slug', array_keys(self::OPERATIONS))
                ->where('r.field', self::SUM_FIELD)
                ->where('r.set_color', 1)
                ->get(['t.slug', 'r.label_color']);
            foreach ($rows as $row) {
                $color = trim((string) $row->label_color);
                if ($color !== '') {
                    $colors[self::OPERATIONS[$row->slug]['kind']] = $color;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $colors;
    }

    public static function rebuild(): array
    {
        $result = ['synced' => 0, 'legacy_removed' => 0];
        if (!self::ready()) {
            return $result;
        }
        $result['legacy_removed'] = DB::table(self::SLUG)
            ->where(fn ($q) => $q->whereNull('operation_id')->orWhereNull('operation_slug'))
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
        foreach (array_keys(self::OPERATIONS) as $slug) {
            if (!Schema::hasTable($slug)) {
                continue;
            }
            if (Schema::hasColumn($slug, 'date')) {
                DB::table($slug)->whereNull('date')->whereNotNull('created_at')->update(['date' => DB::raw('DATE(created_at)')]);
            }
            foreach (DB::table($slug)->pluck('id') as $id) {
                self::syncOperation($slug, (int) $id);
                $result['synced']++;
            }
        }
        $orphans = DB::table(self::SLUG . ' as d')
            ->whereNull('d.deleted_at')
            ->whereNotNull('d.operation_id')
            ->get(['d.id', 'd.operation_slug', 'd.operation_id']);
        foreach ($orphans as $orphan) {
            if (!isset(self::OPERATIONS[$orphan->operation_slug]) || !DB::table($orphan->operation_slug)->where('id', $orphan->operation_id)->exists()) {
                DB::table(self::SLUG)->where('id', $orphan->id)->update(['deleted_at' => now()]);
            }
        }

        return $result;
    }

    private static function cascade($document, bool $restore): void
    {
        if (self::$cascading || !self::ready()) {
            return;
        }
        $slug = (string) ($document->operation_slug ?? '');
        $id = (int) ($document->operation_id ?? 0);
        if (!$id || !isset(self::OPERATIONS[$slug])) {
            return;
        }
        $class = self::OPERATIONS[$slug]['model'];
        self::$cascading = true;
        try {
            $operation = $class::withTrashed()->find($id);
            if ($operation && $restore && $operation->trashed()) {
                $operation->restore();
            } elseif ($operation && !$restore && !$operation->trashed()) {
                $operation->delete();
            }
        } catch (\Throwable $e) {
            \Log::warning('cash documents: каскад на операцию не удался', ['document' => $document->id ?? null, 'error' => $e->getMessage()]);
        } finally {
            self::$cascading = false;
        }
    }

    private static function attributes(string $slug, $operation): array
    {
        $config = self::OPERATIONS[$slug];
        $amount = self::number($operation->sum ?? null);
        $attrs = [
            'name' => self::text($operation->name ?? null),
            'date' => $operation->date ? substr((string) $operation->date, 0, 10) : ($operation->created_at ? substr((string) $operation->created_at, 0, 10) : null),
            'sum' => self::format(abs($amount)),
            'operation_kind' => $config['kind'],
            'comment' => $operation->comment ?? null,
            'user_id' => $operation->user_id ?? null,
        ];
        if (Schema::hasColumn(self::SLUG, 'expense_article_id')) {
            $attrs['expense_article_id'] = property_exists($operation, 'expense_article_id') ? $operation->expense_article_id : null;
        }

        return $attrs;
    }

    private static function dispatch(string $event, int $documentId): void
    {
        try {
            $model = \App\Models\CashDocument::withTrashed()->find($documentId);
            if ($model) {
                \App\Events\ObjectUpdated::dispatch($event, $model->getData());
            }
        } catch (\Throwable $e) {
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
            $value = $decoded['value'] ?? implode(' ', array_filter(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $decoded), fn ($v) => $v !== ''));
        }

        return trim((string) $value);
    }

    private static function format(float $value): string
    {
        $text = number_format(abs($value) < 0.00001 ? 0 : $value, 2, '.', '');
        $text = rtrim(rtrim($text, '0'), '.');

        return $text === '-0' ? '0' : $text;
    }
}
