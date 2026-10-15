<?php

namespace App\Services\OneC;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OneCCashExportService
{
    public const ARTICLES = 'expense_articles';
    public const CATEGORIES = 'expense_article_categories';
    public const DOCUMENTS = 'cash_documents';
    public const CODE_FIELD = 'id_1c';
    public const CODE_TITLE = 'Код 1С';
    public const NUMBER_PREFIX = 'cmps-k';
    public const TYPES = ['expense' => 'cash_expense', 'income' => 'cash_income'];
    public const LIMIT = 100;

    private static array $codeColumn = [];

    public static function hasCodeColumn(): bool
    {
        $key = (string) (function_exists('tenant') ? tenant('id') : '');
        if (!array_key_exists($key, self::$codeColumn)) {
            try {
                self::$codeColumn[$key] = Schema::hasTable(self::ARTICLES) && Schema::hasColumn(self::ARTICLES, self::CODE_FIELD);
            } catch (\Throwable $e) {
                self::$codeColumn[$key] = false;
            }
        }

        return self::$codeColumn[$key];
    }

    public static function forget(): void
    {
        self::$codeColumn = [];
    }

    public static function numberFor(int $documentId): string
    {
        return self::NUMBER_PREFIX . $documentId;
    }

    public static function pendingArticles(): array
    {
        if (!self::hasCodeColumn()) {
            return [];
        }
        $states = DB::table(OneCExportService::STATE_TABLE)->where('slug', self::ARTICLES)->get(['document_id', 'number', 'confirmed_at'])->keyBy('document_id');
        $categories = self::categoryNames();
        $articles = [];
        $query = DB::table(self::ARTICLES)->whereNull('deleted_at')->orderBy('id');
        foreach ($query->get() as $row) {
            $state = $states[$row->id] ?? null;
            $code = self::code($row->{self::CODE_FIELD} ?? null);
            if ($code !== null && $state && $state->confirmed_at && $state->number === self::articleHash($row)) {
                continue;
            }
            $categoryId = self::firstId($row->category_id ?? null);
            $articles[] = [
                'id' => (int) $row->id,
                'code' => $code,
                'name' => self::plain($row->name),
                'types' => self::articleTypes($row->article_type ?? null),
                'category' => $categoryId ? ['id' => $categoryId, 'name' => $categories[$categoryId] ?? null] : null,
                'comment' => self::plain($row->comment ?? null) ?: null,
            ];
            if (count($articles) >= self::LIMIT) {
                break;
            }
        }
        self::markSent(self::ARTICLES, array_map(fn ($article) => [$article['id'], null], $articles));

        return $articles;
    }

    public static function confirmArticles(array $items): array
    {
        $confirmed = [];
        $unknown = [];
        if (!self::hasCodeColumn()) {
            return ['confirmed' => $confirmed, 'unknown' => $unknown];
        }
        $now = now();
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $code = self::code($item['code'] ?? null);
            $row = $id ? DB::table(self::ARTICLES)->where('id', $id)->first() : null;
            if (!$row && $code !== null) {
                $row = DB::table(self::ARTICLES)->where(self::CODE_FIELD, $code)->first();
            }
            if (!$row) {
                $unknown[] = $item['id'] ?? $code;
                continue;
            }
            if ($code !== null && self::code($row->{self::CODE_FIELD} ?? null) !== $code) {
                DB::table(self::ARTICLES)->where('id', $row->id)->update([self::CODE_FIELD => $code, 'updated_at' => $now]);
                $row->{self::CODE_FIELD} = $code;
                self::bumpCache(self::ARTICLES);
            }
            self::saveState(self::ARTICLES, (int) $row->id, [
                'number' => self::articleHash($row),
                'onec_ref' => self::code($row->{self::CODE_FIELD} ?? null),
                'confirmed_at' => $now,
            ]);
            $confirmed[] = ['id' => (int) $row->id, 'code' => self::code($row->{self::CODE_FIELD} ?? null)];
        }

        return ['confirmed' => $confirmed, 'unknown' => $unknown];
    }

    public static function pendingDocuments(object $config): array
    {
        if (!Schema::hasTable(self::DOCUMENTS) || !Schema::hasTable(self::ARTICLES)) {
            return [];
        }
        $settled = now()->subSeconds(OneCExportService::SETTLE_SECONDS);
        $query = DB::table(self::DOCUMENTS . ' as d')
            ->leftJoin(OneCExportService::STATE_TABLE . ' as s', function ($join) {
                $join->on('s.document_id', '=', 'd.id')->where('s.slug', '=', self::DOCUMENTS);
            })
            ->whereNull('s.confirmed_at')
            ->where(fn ($q) => $q->whereNull('d.deleted_at')->orWhereNotNull('s.sent_at'))
            ->where('d.updated_at', '<=', $settled);
        if ($config->since) {
            $query->where('d.created_at', '>=', $config->since);
        }
        $columns = ['d.*', 's.onec_ref as onec_export_ref', 's.sent_at as onec_export_sent_at'];
        if (OneCExportService::hasChangedColumn()) {
            $columns[] = 's.' . OneCExportService::CHANGED_FIELD . ' as onec_export_changed_at';
        }
        $rows = $query->orderBy('d.date')->orderBy('d.id')->limit(self::LIMIT * 3)->get($columns);
        $articleIds = $rows->map(fn ($row) => self::firstId($row->expense_article_id ?? null))->filter()->unique()->values()->all();
        $articles = count($articleIds) ? DB::table(self::ARTICLES)->whereIn('id', $articleIds)->get()->keyBy('id') : collect();
        $userIds = $rows->map(fn ($row) => self::firstId($row->user_id ?? null))->filter()->unique()->values()->all();
        $users = count($userIds) && Schema::hasTable('users') ? DB::table('users')->whereIn('id', $userIds)->pluck('name', 'id') : collect();

        $documents = [];
        foreach ($rows as $row) {
            $articleId = self::firstId($row->expense_article_id ?? null);
            $article = $articleId ? ($articles[$articleId] ?? null) : null;
            $articleCode = $article ? self::code($article->{self::CODE_FIELD} ?? null) : null;
            if ($article && $articleCode === null && !$row->deleted_at) {
                continue;
            }
            $kind = self::kindOf($row);
            $userId = self::firstId($row->user_id ?? null);
            $documents[] = [
                'type' => self::TYPES[$kind] ?? 'cash_' . $kind,
                'id' => (int) $row->id,
                'number' => self::numberFor((int) $row->id),
                'date' => $row->date ? \Carbon\Carbon::parse($row->date)->format('Y-m-d') : null,
                'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d\TH:i:s') : null,
                'updated_at' => $row->updated_at ? \Carbon\Carbon::parse($row->updated_at)->format('Y-m-d\TH:i:s') : null,
                'sum' => round((float) str_replace([' ', ','], ['', '.'], (string) ($row->sum ?? 0)), 2),
                'article' => $article ? ['id' => (int) $article->id, 'code' => $articleCode, 'name' => self::plain($article->name)] : null,
                'comment' => self::plain($row->comment ?? null) ?: null,
                'responsible' => $userId ? ['id' => $userId, 'name' => $users[$userId] ?? null] : null,
                'operation' => ['slug' => $row->operation_slug ?? null, 'id' => isset($row->operation_id) ? (int) $row->operation_id : null],
                'deleted' => (bool) $row->deleted_at,
                'ref' => $row->onec_export_ref ?? null,
                'is_update' => !empty($row->onec_export_sent_at),
                'changed_at' => !empty($row->onec_export_changed_at) ? \Carbon\Carbon::parse($row->onec_export_changed_at)->format('Y-m-d\TH:i:s') : null,
            ];
            if (count($documents) >= self::LIMIT) {
                break;
            }
        }
        self::markSent(self::DOCUMENTS, array_map(fn ($document) => [$document['id'], $document['number']], $documents));

        return $documents;
    }

    public static function confirmDocuments(array $items): array
    {
        $confirmed = [];
        $unknown = [];
        if (!Schema::hasTable(self::DOCUMENTS)) {
            return ['confirmed' => $confirmed, 'unknown' => $unknown];
        }
        $now = now();
        foreach ($items as $item) {
            $id = self::documentId($item);
            if (!$id || !DB::table(self::DOCUMENTS)->where('id', $id)->exists()) {
                $unknown[] = $item['number'] ?? $item['id'] ?? null;
                continue;
            }
            $update = ['number' => self::numberFor($id), 'confirmed_at' => $now];
            $ref = self::code($item['ref'] ?? null);
            if ($ref !== null) {
                $update['onec_ref'] = mb_substr($ref, 0, 191);
            }
            if (OneCExportService::hasChangedColumn()) {
                $update[OneCExportService::CHANGED_FIELD] = null;
            }
            self::saveState(self::DOCUMENTS, $id, $update);
            $confirmed[] = self::numberFor($id);
        }

        return ['confirmed' => $confirmed, 'unknown' => $unknown];
    }

    public static function markChanged(int $documentId): void
    {
        if (!$documentId) {
            return;
        }
        try {
            if (!Schema::hasTable(OneCExportService::STATE_TABLE)) {
                return;
            }
            $state = DB::table(OneCExportService::STATE_TABLE)->where('slug', self::DOCUMENTS)->where('document_id', $documentId)->first(['id', 'confirmed_at']);
            if (!$state || !$state->confirmed_at) {
                return;
            }
            $update = ['confirmed_at' => null, 'updated_at' => now()];
            if (OneCExportService::hasChangedColumn()) {
                $update[OneCExportService::CHANGED_FIELD] = now();
            }
            DB::table(OneCExportService::STATE_TABLE)->where('id', $state->id)->update($update);
        } catch (\Throwable $e) {
        }
    }

    public static function install($db, $sb): array
    {
        if (!$sb->hasTable(self::ARTICLES)) {
            return ['ok' => false, 'message' => 'сущности «Статьи» нет'];
        }
        if (!$sb->hasColumn(self::ARTICLES, self::CODE_FIELD)) {
            $db->statement('ALTER TABLE `' . self::ARTICLES . '` ADD COLUMN `' . self::CODE_FIELD . '` VARCHAR(64) NULL, ADD INDEX `' . self::ARTICLES . '_' . self::CODE_FIELD . '_index` (`' . self::CODE_FIELD . '`)');
        }
        self::forget();
        $typeId = (int) $db->table('data_types')->where('slug', self::ARTICLES)->value('id');
        if (!$typeId) {
            return ['ok' => false, 'message' => 'нет data_types для «Статьи»'];
        }
        $created = false;
        if (!$db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::CODE_FIELD)->exists()) {
            $sectionId = (int) $db->table('field_sections')
                ->where('page', self::ARTICLES)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $db->table('data_rows')->insert(array_merge(\App\Console\Commands\InstallSaleDocsEntities::baseRow($typeId, $sectionId), [
                'field' => self::CODE_FIELD,
                'type' => 'text',
                'title' => self::CODE_TITLE,
                'sort' => $maxSort + 1,
            ]));
            $created = true;
        }
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . self::ARTICLES)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }

        return ['ok' => true, 'created' => $created];
    }

    private static function markSent(string $slug, array $entries): void
    {
        $now = now();
        foreach ($entries as [$id, $number]) {
            $exists = DB::table(OneCExportService::STATE_TABLE)->where('slug', $slug)->where('document_id', $id)->first(['id']);
            if ($exists) {
                $update = ['sent_at' => $now, 'attempts' => DB::raw('attempts + 1'), 'updated_at' => $now];
                if ($number !== null) {
                    $update['number'] = $number;
                }
                DB::table(OneCExportService::STATE_TABLE)->where('id', $exists->id)->update($update);
                continue;
            }
            DB::table(OneCExportService::STATE_TABLE)->insertOrIgnore([
                'slug' => $slug,
                'document_id' => $id,
                'number' => $number,
                'attempts' => 1,
                'sent_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private static function saveState(string $slug, int $id, array $update): void
    {
        $now = now();
        $state = DB::table(OneCExportService::STATE_TABLE)->where('slug', $slug)->where('document_id', $id)->first(['id']);
        if ($state) {
            DB::table(OneCExportService::STATE_TABLE)->where('id', $state->id)->update($update + ['updated_at' => $now]);
            return;
        }
        DB::table(OneCExportService::STATE_TABLE)->insert($update + [
            'slug' => $slug,
            'document_id' => $id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private static function documentId(array $item): int
    {
        $number = trim((string) ($item['number'] ?? ''));
        if ($number !== '' && str_starts_with(mb_strtolower($number), self::NUMBER_PREFIX)) {
            return (int) mb_substr($number, mb_strlen(self::NUMBER_PREFIX));
        }

        return (int) ($item['id'] ?? 0);
    }

    private static function kindOf(object $row): string
    {
        $kind = self::articleTypes($row->operation_kind ?? null)[0] ?? null;
        if ($kind) {
            return $kind;
        }
        foreach (\App\Services\CashDocumentService::OPERATIONS as $slug => $operation) {
            if (($row->operation_slug ?? null) === $slug) {
                return $operation['kind'];
            }
        }

        return 'expense';
    }

    private static function articleHash(object $row): string
    {
        return md5(self::plain($row->name) . '|' . (self::code($row->{self::CODE_FIELD} ?? null) ?? ''));
    }

    private static function articleTypes($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        $values = is_array($decoded) ? $decoded : [$raw];
        $types = [];
        foreach ($values as $value) {
            $value = is_array($value) ? ($value['value'] ?? null) : $value;
            if (in_array($value, ['income', 'expense'], true)) {
                $types[] = $value;
            }
        }

        return array_values(array_unique($types));
    }

    private static function categoryNames(): array
    {
        try {
            if (!Schema::hasTable(self::CATEGORIES)) {
                return [];
            }

            return DB::table(self::CATEGORIES)->pluck('name', 'id')->map(fn ($name) => self::plain($name))->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function bumpCache(string $slug): void
    {
        try {
            if (Schema::hasTable('local_cache')) {
                DB::table('local_cache')->where('url', 'like', 'objects/' . $slug . '%')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
    }

    private static function firstId($raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (is_array($decoded)) {
            $first = reset($decoded);
            $first = is_array($first) ? ($first['value'] ?? $first['id'] ?? null) : $first;

            return $first ? (int) $first : null;
        }

        return (int) $raw ?: null;
    }

    private static function code($raw): ?string
    {
        if (!is_scalar($raw)) {
            return null;
        }
        $code = trim((string) $raw);

        return $code === '' ? null : $code;
    }

    private static function plain($value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded['value'] ?? '';
            }
        } elseif (is_array($value)) {
            $value = $value['value'] ?? '';
        }

        return trim(strip_tags((string) $value));
    }
}
