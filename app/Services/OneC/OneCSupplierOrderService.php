<?php

namespace App\Services\OneC;

use App\Models\Route;
use App\Services\DocumentNumber;
use App\Services\ShipmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OneCSupplierOrderService
{
    public const SLUG = 'supplier_orders';
    public const TYPE = 'supplier_order';
    public const NUMBER_PREFIX = DocumentNumber::PREFIX . 'z';
    public const STATE_FIELD = 'onec_state';
    public const STATE_TITLE = 'Статус';
    public const CONTRACT_FIELD = 'contract';
    public const CONTRACT_TITLE = 'Договор';
    public const STOREHOUSE_FIELD = 'storehouse_id';
    public const STATES = [
        1 => ['value' => 'В работе', 'color' => '#2F8CFF'],
        2 => ['value' => 'Планируемая закупка', 'color' => '#8E7CC3'],
        3 => ['value' => 'Запросил счет', 'color' => '#F5A623'],
        4 => ['value' => 'Счет получен', 'color' => '#F8C51C'],
        5 => ['value' => 'Счет оплачиваем', 'color' => '#E67E22'],
        6 => ['value' => 'Оплатили', 'color' => '#34C759'],
        7 => ['value' => 'Запланировали забор', 'color' => '#1ABC9C'],
        8 => ['value' => 'Требуется бухгалтерия', 'color' => '#C74822'],
        9 => ['value' => 'Завершен', 'color' => '#4A4A4A'],
    ];
    public const RESYNC_FIELDS = ['products', 'sum', 'company_id', 'shipment_company_id', 'date', 'name'];

    private static array $fields = [];

    public static function forget(): void
    {
        self::$fields = [];
    }

    public static function ready(): bool
    {
        try {
            return Schema::hasTable(self::SLUG) && Schema::hasTable(OneCExportService::STATE_TABLE);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function numberFor(int $id): string
    {
        return self::NUMBER_PREFIX . $id;
    }

    public static function idFromNumber($number, $id = null): int
    {
        $number = mb_strtolower(trim((string) $number));
        if ($number !== '' && str_starts_with($number, self::NUMBER_PREFIX)) {
            return (int) mb_substr($number, mb_strlen(self::NUMBER_PREFIX));
        }

        return is_numeric($id) ? (int) $id : 0;
    }

    public static function fieldColumns(): array
    {
        $key = (string) (function_exists('tenant') ? tenant('id') : '');
        if (array_key_exists($key, self::$fields)) {
            return self::$fields[$key];
        }
        $result = ['contract' => null, 'storehouse' => null, 'state' => null];
        try {
            $typeId = DB::table('data_types')->where('slug', self::SLUG)->value('id');
            if ($typeId) {
                $rows = DB::table('data_rows')->where('data_type_id', $typeId)->where('is_remove', 0)->orderBy('id')->get(['field', 'type', 'title', 'relation_table', 'details']);
                foreach ($rows as $row) {
                    if (!Schema::hasColumn(self::SLUG, $row->field)) {
                        continue;
                    }
                    $table = $row->relation_table ?: (json_decode((string) $row->details, true)['table'] ?? null);
                    if (!$result['storehouse'] && $row->type === 'relation' && $table === 'storehouses') {
                        $result['storehouse'] = $row->field;
                    }
                    if (!$result['contract'] && ($row->field === self::CONTRACT_FIELD || mb_strtolower(trim((string) $row->title)) === mb_strtolower(self::CONTRACT_TITLE))) {
                        $result['contract'] = $row->field;
                    }
                    if ($row->field === self::STATE_FIELD) {
                        $result['state'] = $row->field;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return self::$fields[$key] = $result;
    }

    public static function resyncFields(): array
    {
        $columns = self::fieldColumns();

        return array_values(array_filter(array_merge(self::RESYNC_FIELDS, [$columns['contract'], $columns['storehouse']])));
    }

    public static function touched($model): bool
    {
        if ($model->wasRecentlyCreated) {
            return false;
        }
        $fields = self::resyncFields();
        foreach (array_keys($model->getChanges()) as $key) {
            if (in_array($key, $fields, true)) {
                return true;
            }
        }

        return false;
    }

    public static function markChanged(int $id): void
    {
        if (!$id || !self::ready()) {
            return;
        }
        try {
            $state = DB::table(OneCExportService::STATE_TABLE)->where('slug', self::SLUG)->where('document_id', $id)->first(['id', 'confirmed_at']);
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

    public static function pending(object $config): array
    {
        if (!self::ready() || !OneCExportService::enabledFor($config, self::SLUG)) {
            return [];
        }
        $since = OneCExportService::sinceFor($config, self::SLUG);
        $settled = now()->subSeconds(OneCExportService::SETTLE_SECONDS);
        $query = DB::table(self::SLUG . ' as d')
            ->leftJoin(OneCExportService::STATE_TABLE . ' as s', function ($join) {
                $join->on('s.document_id', '=', 'd.id')->where('s.slug', '=', self::SLUG);
            })
            ->whereNull('s.confirmed_at')
            ->where(fn ($q) => $q->whereNull('d.deleted_at')->orWhereNotNull('s.sent_at'))
            ->whereNotNull('d.products')
            ->whereNotIn('d.products', ['', '[]'])
            ->where('d.updated_at', '<=', $settled);
        if ($since) {
            $query->where('d.created_at', '>=', $since);
        }
        $columns = ['d.*', 's.onec_ref as onec_export_ref', 's.sent_at as onec_export_sent_at'];
        if (OneCExportService::hasChangedColumn()) {
            $columns[] = 's.' . OneCExportService::CHANGED_FIELD . ' as onec_export_changed_at';
        }
        $documents = [];
        foreach ($query->orderBy('d.created_at')->orderBy('d.id')->limit(OneCExportService::LIMIT)->get($columns) as $row) {
            $document = self::present($row);
            if (!count($document['items']) && !$document['deleted']) {
                continue;
            }
            $documents[] = $document;
        }
        self::markSent($documents);

        return $documents;
    }

    public static function present(object $row): array
    {
        $columns = self::fieldColumns();
        $lines = array_values(array_filter(ShipmentService::decode($row->products ?? null), 'is_array'));
        $productIds = array_values(array_unique(array_filter(array_map(fn ($line) => (int) ($line['id'] ?? 0), $lines))));
        $products = self::products($productIds);
        $items = [];
        foreach ($lines as $index => $line) {
            $quantity = (float) str_replace(',', '.', (string) ($line['count'] ?? 0));
            if ($quantity <= 0) {
                continue;
            }
            $productId = (int) ($line['id'] ?? 0);
            $product = $products[$productId] ?? null;
            $priceKey = isset($line['purchase_price']) && is_numeric($line['purchase_price']) ? 'purchase_price' : 'price';
            $parts = ShipmentService::lineParts($line, $priceKey);
            $items[] = [
                'row' => $index + 1,
                'product_id' => $productId ?: null,
                'product_1c_id' => $product['id_1c'] ?? null,
                'product_b24_id' => $product['id_b24'] ?? null,
                'name' => ShipmentService::plainName($line['name'] ?? ''),
                'quantity' => $quantity == (int) $quantity ? (int) $quantity : $quantity,
                'price' => self::money(is_numeric($line[$priceKey] ?? null) ? (float) $line[$priceKey] : 0),
                'sum' => self::money($parts['gross']),
                'vat_rate' => self::money($parts['rate']),
                'vat_included' => $parts['included'],
                'vat_sum' => self::money($parts['vat']),
                'sum_without_vat' => self::money($parts['net']),
            ];
        }
        $state = self::currentState($row);

        return [
            'type' => self::TYPE,
            'id' => (int) $row->id,
            'number' => self::numberFor((int) $row->id),
            'ref' => trim((string) ($row->onec_export_ref ?? '')) ?: null,
            'is_update' => !empty($row->onec_export_sent_at),
            'changed_at' => self::date($row->onec_export_changed_at ?? null),
            'deleted' => !empty($row->deleted_at),
            'date' => self::date($row->created_at ?? null),
            'updated_at' => self::date($row->updated_at ?? null),
            'name' => self::text($row->name ?? ''),
            'organization' => self::company(self::ids($row->shipment_company_id ?? null)[0] ?? null),
            'supplier' => self::company(self::ids($row->company_id ?? null)[0] ?? null),
            'contract' => $columns['contract'] ? (self::text($row->{$columns['contract']} ?? '') ?: null) : null,
            'receipt_date' => self::date($row->date ?? null),
            'storehouse' => self::storehouse($columns['storehouse'] ? (self::ids($row->{$columns['storehouse']} ?? null)[0] ?? null) : null),
            'state' => $state,
            'sum' => self::money(array_sum(array_map(fn ($item) => (float) $item['sum'], $items))),
            'items' => $items,
        ];
    }

    public static function confirm(array $items): array
    {
        $confirmed = [];
        $unknown = [];
        $states = [];
        if (!self::ready()) {
            return ['confirmed' => $confirmed, 'unknown' => $unknown, 'states' => $states];
        }
        $now = now();
        foreach ($items as $item) {
            $id = self::idFromNumber($item['number'] ?? null, $item['id'] ?? null);
            if (!$id || !DB::table(self::SLUG)->where('id', $id)->exists()) {
                $unknown[] = $item['number'] ?? $item['id'] ?? null;
                continue;
            }
            $stateIndex = self::normalizeState($item['state'] ?? null);
            if ($stateIndex !== null) {
                self::setState($id, $stateIndex);
                $states[] = ['number' => self::numberFor($id), 'state' => self::STATES[$stateIndex]['value']];
            }
            if (!empty($item['state_only'])) {
                continue;
            }
            $update = ['number' => self::numberFor($id), 'confirmed_at' => $now];
            $ref = trim((string) ($item['ref'] ?? ''));
            if ($ref !== '') {
                $update['onec_ref'] = mb_substr($ref, 0, 191);
            }
            if (OneCExportService::hasChangedColumn()) {
                $update[OneCExportService::CHANGED_FIELD] = null;
            }
            $existing = DB::table(OneCExportService::STATE_TABLE)->where('slug', self::SLUG)->where('document_id', $id)->first(['id']);
            if ($existing) {
                DB::table(OneCExportService::STATE_TABLE)->where('id', $existing->id)->update($update + ['updated_at' => $now]);
            } else {
                DB::table(OneCExportService::STATE_TABLE)->insert($update + ['slug' => self::SLUG, 'document_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
            $confirmed[] = self::numberFor($id);
        }

        return ['confirmed' => $confirmed, 'unknown' => $unknown, 'states' => $states];
    }

    public static function normalizeState($raw): ?int
    {
        if ($raw === null || is_bool($raw) || !is_scalar($raw)) {
            return null;
        }
        $text = mb_strtolower(trim((string) $raw));
        if ($text === '') {
            return null;
        }
        if (is_numeric($text) && isset(self::STATES[(int) $text])) {
            return (int) $text;
        }
        $text = trim(preg_replace('/^\d+\s*[.)]\s*/u', '', $text));
        $text = str_replace('ё', 'е', $text);
        foreach (self::STATES as $index => $state) {
            if (mb_strtolower($state['value']) === $text) {
                return $index;
            }
        }
        foreach (self::STATES as $index => $state) {
            if (mb_strpos($text, mb_strtolower($state['value'])) !== false) {
                return $index;
            }
        }

        return null;
    }

    public static function stateValueId(int $index): ?string
    {
        try {
            $typeId = DB::table('data_types')->where('slug', self::SLUG)->value('id');
            $fieldId = $typeId ? DB::table('data_rows')->where('data_type_id', $typeId)->where('field', self::STATE_FIELD)->value('id') : null;
            if (!$fieldId) {
                return null;
            }
            $id = DB::table('field_values')->where('field_id', $fieldId)->where('sort', $index)->where('is_hidden', '!=', 1)->orderBy('id')->value('id');
            if (!$id) {
                $id = DB::table('field_values')->where('field_id', $fieldId)->where('value', self::STATES[$index]['value'])->orderBy('id')->value('id');
            }

            return $id ? (string) $id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function setState(int $id, int $index): void
    {
        if (!isset(self::STATES[$index]) || !self::fieldColumns()['state']) {
            return;
        }
        $value = self::stateValueId($index);
        if ($value === null) {
            return;
        }
        $row = DB::table(self::SLUG)->where('id', $id)->first(['id', self::STATE_FIELD]);
        if (!$row) {
            return;
        }
        $current = trim((string) $row->{self::STATE_FIELD});
        if ($current === $value) {
            return;
        }
        DB::table(self::SLUG)->where('id', $id)->update([self::STATE_FIELD => $value, 'updated_at' => now()]);
        self::writeHistory($id, $current, $value);
        try {
            if (Schema::hasTable('local_cache')) {
                DB::table('local_cache')->where('url', 'like', 'objects/' . self::SLUG . '%')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
    }

    public static function install($db, $sb, callable $log): void
    {
        $typeId = (int) $db->table('data_types')->where('slug', self::SLUG)->value('id');
        if (!$typeId || !$sb->hasTable(self::SLUG)) {
            $log('сущности «Заказы поставщикам» нет, пропуск');
            return;
        }
        $sectionId = (int) $db->table('field_sections')
            ->where('page', self::SLUG)
            ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
            ->orderBy('sort')
            ->value('id');
        $base = fn () => \App\Console\Commands\InstallSaleDocsEntities::baseRow($typeId, $sectionId);
        $nextSort = fn () => ((int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort')) + 1;

        if (!$sb->hasColumn(self::SLUG, self::STATE_FIELD)) {
            $db->statement('ALTER TABLE `' . self::SLUG . '` ADD COLUMN `' . self::STATE_FIELD . '` TEXT NULL');
        }
        $stateAttrs = ['type' => 'status', 'title' => self::STATE_TITLE, 'required' => 0, 'is_remove' => 0, 'hide' => 0, 'is_default' => 1];
        $stateRow = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::STATE_FIELD)->first();
        if ($stateRow) {
            $db->table('data_rows')->where('id', $stateRow->id)->update(['type' => 'status', 'is_remove' => 0]);
            $fieldId = (int) $stateRow->id;
        } else {
            $fieldId = (int) $db->table('data_rows')->insertGetId(array_merge($base(), $stateAttrs, ['field' => self::STATE_FIELD, 'sort' => $nextSort()]));
            $log('создано поле «' . self::STATE_TITLE . '» (id ' . $fieldId . ')');
        }
        foreach (self::STATES as $sort => $def) {
            $value = $db->table('field_values')->where('field_id', $fieldId)->where('sort', $sort)->orderBy('id')->first();
            if (!$value) {
                $value = $db->table('field_values')->where('field_id', $fieldId)->where('value', $def['value'])->orderBy('id')->first();
            }
            if ($value) {
                $db->table('field_values')->where('id', $value->id)->update(['sort' => $sort, 'is_hidden' => 0]);
            } else {
                $db->table('field_values')->insert(['field_id' => $fieldId, 'value' => $def['value'], 'color' => $def['color'], 'sort' => $sort, 'is_hidden' => 0]);
            }
        }

        $extra = $db->table('data_rows')->where('data_type_id', $typeId)
            ->where(fn ($q) => $q->where(fn ($q) => $q->where('field', self::CONTRACT_FIELD)->where('type', 'text'))
                ->orWhere(fn ($q) => $q->where('field', self::STOREHOUSE_FIELD)->where('relation_table', 'storehouses')))
            ->get(['id', 'field', 'title']);
        foreach ($extra as $row) {
            if ($sb->hasTable('section_fields_sort')) {
                $db->table('section_fields_sort')->where('field_id', $row->id)->delete();
            }
            $db->table('data_rows')->where('id', $row->id)->delete();
            if ($sb->hasColumn(self::SLUG, $row->field)) {
                $sb->table(self::SLUG, fn ($table) => $table->dropColumn($row->field));
            }
            $log('удалено поле «' . $row->title . '»');
        }
        self::forget();
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . self::SLUG)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
    }

    private static function currentState(object $row): ?array
    {
        $value = trim((string) ($row->{self::STATE_FIELD} ?? ''));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }
        $option = DB::table('field_values')->where('id', (int) $value)->first(['id', 'value', 'sort']);
        if (!$option) {
            return null;
        }

        return ['code' => isset(self::STATES[(int) $option->sort]) ? (int) $option->sort : null, 'name' => (string) $option->value];
    }

    private static function writeHistory(int $id, string $old, string $new): void
    {
        try {
            $names = DB::table('field_values')->whereIn('id', array_values(array_filter([$old, $new], 'is_numeric')))->pluck('value', 'id');
            $label = fn (string $v) => $v === '' ? '' : (string) ($names[(int) $v] ?? $v);
            $history = new \App\Models\History([
                'entity' => self::SLUG,
                'entity_id' => $id,
                'user_id' => \App\Models\History::currentUserId() ?: 1,
                'event' => 'FIELD_UPDATED',
                'field' => self::STATE_FIELD,
                'text' => self::STATE_TITLE . ': ' . $label($old) . ' -> ' . $label($new),
                'old_value' => $label($old),
                'new_value' => $label($new),
            ]);
            $history->saveQuietly();
            \App\Events\ObjectUpdated::dispatch('HistoryUpdated', \App\Models\History::getDataList([$history]));
        } catch (\Throwable $e) {
        }
    }

    private static function markSent(array $documents): void
    {
        $now = now();
        foreach ($documents as $document) {
            $existing = DB::table(OneCExportService::STATE_TABLE)->where('slug', self::SLUG)->where('document_id', $document['id'])->first(['id']);
            if ($existing) {
                DB::table(OneCExportService::STATE_TABLE)->where('id', $existing->id)->update([
                    'number' => $document['number'],
                    'sent_at' => $now,
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => $now,
                ]);
                continue;
            }
            DB::table(OneCExportService::STATE_TABLE)->insertOrIgnore([
                'slug' => self::SLUG,
                'document_id' => $document['id'],
                'number' => $document['number'],
                'attempts' => 1,
                'sent_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private static function products(array $ids): array
    {
        if (!count($ids) || !Schema::hasTable('products')) {
            return [];
        }
        $columns = array_values(array_filter(['id', 'id_1c', 'id_b24'], fn ($column) => Schema::hasColumn('products', $column)));
        $result = [];
        foreach (DB::table('products')->whereIn('id', $ids)->get($columns) as $product) {
            $result[(int) $product->id] = [
                'id_1c' => trim((string) ($product->id_1c ?? '')) ?: null,
                'id_b24' => trim((string) ($product->id_b24 ?? '')) ?: null,
            ];
        }

        return $result;
    }

    private static function company($id): ?array
    {
        if (!$id || !Schema::hasTable('companies')) {
            return null;
        }
        $company = DB::table('companies')->where('id', $id)->first();

        return $company ? [
            'id' => (int) $company->id,
            'name' => self::text($company->name ?? ''),
            'inn' => trim((string) ($company->inn ?? '')) ?: null,
            'kpp' => trim((string) ($company->kpp ?? '')) ?: null,
            'b24_id' => trim((string) ($company->b24_id ?? '')) ?: null,
        ] : null;
    }

    private static function storehouse($id): ?array
    {
        if (!$id || !Schema::hasTable('storehouses')) {
            return null;
        }
        $columns = Schema::hasColumn('storehouses', OneCExportService::STOREHOUSE_1C_FIELD) ? ['id', 'name', OneCExportService::STOREHOUSE_1C_FIELD] : ['id', 'name'];
        $storehouse = DB::table('storehouses')->where('id', $id)->first($columns);

        return $storehouse ? [
            'id' => (int) $storehouse->id,
            'name' => self::text($storehouse->name ?? ''),
            'id_1c' => trim((string) ($storehouse->{OneCExportService::STOREHOUSE_1C_FIELD} ?? '')) ?: null,
        ] : null;
    }

    private static function money(float $value)
    {
        $value = round($value, 2);

        return $value == (int) $value ? (int) $value : $value;
    }

    private static function ids($raw): array
    {
        return array_values(array_filter(array_map('intval', Route::parseIdList($raw))));
    }

    private static function text($value): string
    {
        $decoded = is_string($value) ? json_decode($value, true) : null;
        if (is_array($decoded) && array_key_exists('value', $decoded)) {
            return trim((string) $decoded['value']);
        }

        return trim((string) $value);
    }

    private static function date($value): ?string
    {
        if (!$value) {
            return null;
        }
        $time = strtotime((string) $value);

        return $time ? date('Y-m-d\TH:i:s', $time) : null;
    }
}
