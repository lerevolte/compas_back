<?php

namespace App\Services\OneC;

use App\Services\DocumentNumber;
use App\Services\ShipmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OneCProductionService
{
    public const TYPES = [
        'production_orders' => 'production_order',
        'productions' => 'production',
        'specifications' => 'specification',
    ];

    public const PREFIXES = [
        'production_orders' => 'po',
        'productions' => 'pr',
        'specifications' => 'sp',
    ];

    public static function numberFor(string $slug, int $id): string
    {
        return DocumentNumber::PREFIX . self::PREFIXES[$slug] . $id;
    }

    public static function pending(object $config): array
    {
        $documents = [];
        if (!Schema::hasTable(OneCExportService::STATE_TABLE)) {
            return $documents;
        }
        $settled = now()->subSeconds(OneCExportService::SETTLE_SECONDS);
        foreach (self::TYPES as $slug => $type) {
            if (!Schema::hasTable($slug) || !OneCExportService::hasStatusColumn($slug) || !OneCExportService::enabledFor($config, $slug)) {
                continue;
            }
            $query = DB::table($slug . ' as d')
                ->leftJoin(OneCExportService::STATE_TABLE . ' as s', function ($join) use ($slug) {
                    $join->on('s.document_id', '=', 'd.id')->where('s.slug', '=', $slug);
                })
                ->whereNull('s.confirmed_at')
                ->where(fn ($q) => $q->whereNull('d.deleted_at')->orWhereNotNull('s.sent_at'))
                ->where('d.updated_at', '<=', $settled);
            $since = OneCExportService::slugSince($config)[$slug] ?? ($config->since ?? null);
            if ($since) {
                $query->where('d.created_at', '>=', $since);
            }
            $columns = ['d.*', 's.onec_ref as onec_export_ref', 's.sent_at as onec_export_sent_at'];
            if (OneCExportService::hasChangedColumn()) {
                $columns[] = 's.' . OneCExportService::CHANGED_FIELD . ' as onec_export_changed_at';
            }
            foreach ($query->orderBy('d.created_at')->orderBy('d.id')->limit(OneCExportService::LIMIT)->get($columns) as $row) {
                $document = self::present($slug, $row);
                if ($document['deleted'] || count($document['items']) || count($document['materials']) || $document['finished_product']) {
                    $documents[] = $document;
                }
            }
        }
        usort($documents, fn ($a, $b) => [$a['date'], $a['id']] <=> [$b['date'], $b['id']]);
        $documents = array_slice($documents, 0, OneCExportService::LIMIT);
        self::markSent($documents);

        return $documents;
    }

    public static function present(string $slug, object $row): array
    {
        $items = self::lines($row->products ?? null);
        $materials = $slug === 'specifications' ? [] : self::lines($row->materials ?? null);
        $finishedId = $slug === 'specifications' ? (\App\Services\ReverseLinkService::ids($row->finished_product_id ?? null)[0] ?? null) : null;
        $finished = $finishedId ? self::product($finishedId) : null;
        $sourceOrderId = $slug === 'productions' && Schema::hasTable('object_relations')
            ? DB::table('object_relations')->where('source_slug', 'production_orders')->where('target_slug', 'productions')->where('target_id', $row->id)->value('source_id') : null;

        return [
            'type' => self::TYPES[$slug],
            'id' => (int) $row->id,
            'number' => self::numberFor($slug, (int) $row->id),
            'ref' => trim((string) ($row->onec_export_ref ?? '')) ?: null,
            'is_update' => !empty($row->onec_export_sent_at),
            'changed_at' => self::date($row->onec_export_changed_at ?? null),
            'deleted' => !empty($row->deleted_at),
            'date' => self::date($row->created_at ?? null),
            'updated_at' => self::date($row->updated_at ?? null),
            'name' => ShipmentService::plainName($row->name ?? ''),
            'finished_product' => $finished,
            'source_order' => $sourceOrderId ? ['id' => (int) $sourceOrderId, 'number' => self::numberFor('production_orders', (int) $sourceOrderId)] : null,
            'items' => $items,
            'materials' => $materials,
        ];
    }

    private static function lines($json): array
    {
        $lines = array_values(array_filter(ShipmentService::decode($json), 'is_array'));
        $ids = array_values(array_unique(array_filter(array_map(fn ($line) => (int) ($line['id'] ?? 0), $lines))));
        $products = $ids && Schema::hasTable('products')
            ? DB::table('products')->whereIn('id', $ids)->get(['id', 'id_1c', 'id_b24'])->keyBy('id') : collect();
        $items = [];
        foreach ($lines as $index => $line) {
            $quantity = (float) str_replace(',', '.', (string) ($line['count'] ?? 0));
            if ($quantity <= 0) {
                continue;
            }
            $id = (int) ($line['id'] ?? 0);
            $product = $products->get($id);
            $priceKey = is_numeric($line['price'] ?? null) ? 'price' : 'purchase_price';
            $parts = ShipmentService::lineParts($line, $priceKey);
            $items[] = [
                'row' => $index + 1,
                'product_id' => $id ?: null,
                'product_1c_id' => $product->id_1c ?? null,
                'product_b24_id' => $product->id_b24 ?? null,
                'name' => ShipmentService::plainName($line['name'] ?? ''),
                'quantity' => $quantity,
                'output_count' => is_numeric($line['output_count'] ?? null) ? (float) $line['output_count'] : null,
                'produced' => is_numeric($line['produced'] ?? null) ? (float) $line['produced'] : null,
                'specification_id' => $line['specification_id'] ?? null,
                'price' => is_numeric($line[$priceKey] ?? null) ? (float) $line[$priceKey] : null,
                'sum' => round($parts['net'], 2),
                'vat_rate' => $parts['rate'],
                'vat_sum' => round($parts['vat'], 2),
                'total' => round($parts['gross'], 2),
            ];
        }

        return $items;
    }

    private static function product(int $id): ?array
    {
        if (!Schema::hasTable('products')) {
            return null;
        }
        $row = DB::table('products')->where('id', $id)->first(['id', 'id_1c', 'id_b24', 'name']);

        return $row ? ['id' => $id, 'id_1c' => $row->id_1c, 'id_b24' => $row->id_b24, 'name' => ShipmentService::plainName($row->name)] : null;
    }

    private static function markSent(array $documents): void
    {
        $now = now();
        foreach ($documents as $document) {
            $slug = array_search($document['type'], self::TYPES, true);
            $key = ['slug' => $slug, 'document_id' => $document['id']];
            $state = DB::table(OneCExportService::STATE_TABLE)->where($key)->first(['id']);
            if ($state) {
                DB::table(OneCExportService::STATE_TABLE)->where('id', $state->id)->update([
                    'number' => $document['number'], 'sent_at' => $now,
                    'attempts' => DB::raw('attempts + 1'), 'updated_at' => $now,
                ]);
            } else {
                DB::table(OneCExportService::STATE_TABLE)->insertOrIgnore($key + [
                    'number' => $document['number'], 'attempts' => 1,
                    'sent_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public static function confirm(array $items): array
    {
        $confirmed = [];
        $unknown = [];
        $config = OneCExportService::config();
        foreach ($items as $item) {
            $number = trim((string) ($item['number'] ?? ''));
            $match = self::fromNumber($number);
            if (!$match || !$config || !OneCExportService::enabledFor($config, $match[0]) || !Schema::hasTable($match[0]) || !OneCExportService::hasStatusColumn($match[0]) || !DB::table($match[0])->where('id', $match[1])->exists()) {
                $unknown[] = $number;
                continue;
            }
            [$slug, $id] = $match;
            $type = trim((string) ($item['type'] ?? ''));
            if ($type !== '' && !in_array($type, [$slug, self::TYPES[$slug]], true)) {
                $unknown[] = $number;
                continue;
            }
            $now = now();
            $update = ['number' => $number, 'confirmed_at' => $now, 'updated_at' => $now];
            $ref = trim((string) ($item['ref'] ?? ''));
            if ($ref !== '') {
                $update['onec_ref'] = mb_substr($ref, 0, 191);
            }
            if (OneCExportService::hasChangedColumn()) {
                $update[OneCExportService::CHANGED_FIELD] = null;
            }
            $key = ['slug' => $slug, 'document_id' => $id];
            $state = DB::table(OneCExportService::STATE_TABLE)->where($key)->first(['id']);
            if ($state) {
                DB::table(OneCExportService::STATE_TABLE)->where('id', $state->id)->update($update);
            } else {
                DB::table(OneCExportService::STATE_TABLE)->insert($key + $update + ['created_at' => $now]);
            }
            $status = $item['status'] ?? null;
            OneCExportService::markStatus($slug, $id, $status ?? OneCExportService::STATUS_CREATED, $status === null);
            $confirmed[] = $number;
        }

        return ['confirmed' => $confirmed, 'unknown' => $unknown];
    }

    private static function fromNumber(string $number): ?array
    {
        foreach (self::PREFIXES as $slug => $prefix) {
            if (preg_match('/^' . preg_quote(DocumentNumber::PREFIX . $prefix, '/') . '(\d+)$/i', $number, $m)) {
                return [$slug, (int) $m[1]];
            }
        }

        return null;
    }

    public static function changed($model): void
    {
        if (!$model->wasRecentlyCreated && count(array_intersect(array_keys($model->getChanges()), ['name', 'products', 'materials', 'finished_product_id', 'created_at']))) {
            OneCExportService::markChangedById($model->getTable(), (int) $model->id);
        }
    }

    private static function date($value): ?string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i:s') : null;
    }
}
