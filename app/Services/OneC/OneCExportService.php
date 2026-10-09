<?php

namespace App\Services\OneC;

use App\Models\Route;
use App\Services\DocumentNumber;
use App\Services\ShipmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OneCExportService
{
    public const CONFIG_TABLE = 'onec_config';
    public const STATE_TABLE = 'onec_exports';
    public const NUMBER_FIELD = 'number';
    public const DOCUMENTS = [
        'expense_invoices' => 'expense_invoice',
        'receipt_invoices' => 'receipt_invoice',
    ];
    public const STOREHOUSE_FIELDS = [
        'expense_invoices' => 'storehouse_id',
        'receipt_invoices' => 'receipt_storehouse_id',
    ];
    public const STOREHOUSE_1C_FIELD = 'id_1c';
    public const STATUS_FIELD = 'onec_status';
    public const STATUS_TITLE = 'Статус 1С';
    public const STATUS_VALUES = [
        ['value' => 'Не заполнено', 'color' => '#A8A8A8'],
        ['value' => 'Проведено в 1С', 'color' => '#34C759'],
    ];
    public const LIMIT = 100;
    public const SETTLE_SECONDS = 30;

    private static array $numberColumn = [];
    private static array $statusColumn = [];
    private static array $statusValues = [];

    public static function ensureTables($db): void
    {
        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `onec_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(191) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `since` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `onec_exports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(64) NOT NULL,
  `document_id` bigint unsigned NOT NULL,
  `number` varchar(64) DEFAULT NULL,
  `onec_ref` varchar(191) DEFAULT NULL,
  `attempts` int unsigned NOT NULL DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onec_exports_document_unique` (`slug`, `document_id`),
  KEY `onec_exports_number_index` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public static function config(): ?object
    {
        try {
            if (!Schema::hasTable(self::CONFIG_TABLE) || !Schema::hasTable(self::STATE_TABLE)) {
                return null;
            }
            $row = DB::table(self::CONFIG_TABLE)->where('enabled', 1)->orderBy('id')->first();

            return $row && trim((string) $row->token) !== '' ? $row : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function authorized(?object $config, ?string $token): bool
    {
        return $config && is_string($token) && $token !== '' && hash_equals(trim((string) $config->token), $token);
    }

    public static function hasNumberColumn(string $table): bool
    {
        $key = (string) (function_exists('tenant') ? tenant('id') : '') . ':' . $table;
        if (!array_key_exists($key, self::$numberColumn)) {
            try {
                self::$numberColumn[$key] = Schema::hasColumn($table, self::NUMBER_FIELD);
            } catch (\Throwable $e) {
                self::$numberColumn[$key] = false;
            }
        }

        return self::$numberColumn[$key];
    }

    public static function hasStatusColumn(string $table): bool
    {
        $key = (string) (function_exists('tenant') ? tenant('id') : '') . ':' . $table;
        if (!array_key_exists($key, self::$statusColumn)) {
            try {
                self::$statusColumn[$key] = Schema::hasColumn($table, self::STATUS_FIELD);
            } catch (\Throwable $e) {
                self::$statusColumn[$key] = false;
            }
        }

        return self::$statusColumn[$key];
    }

    public static function forget(): void
    {
        self::$numberColumn = [];
        self::$statusColumn = [];
        self::$statusValues = [];
    }

    public static function statusValueId(string $slug, int $index): ?string
    {
        $key = (string) (function_exists('tenant') ? tenant('id') : '') . ':' . $slug . ':' . $index;
        if (array_key_exists($key, self::$statusValues)) {
            return self::$statusValues[$key];
        }
        $id = null;
        try {
            $typeId = DB::table('data_types')->where('slug', $slug)->value('id');
            $fieldId = $typeId ? DB::table('data_rows')->where('data_type_id', $typeId)->where('field', self::STATUS_FIELD)->value('id') : null;
            if ($fieldId) {
                $id = DB::table('field_values')->where('field_id', $fieldId)->where('value', self::STATUS_VALUES[$index]['value'])->value('id');
                if (!$id) {
                    $ordered = DB::table('field_values')->where('field_id', $fieldId)->where('is_hidden', '!=', 1)->orderBy('sort')->orderBy('id')->pluck('id')->all();
                    $id = $ordered[$index] ?? null;
                }
            }
        } catch (\Throwable $e) {
            $id = null;
        }

        return self::$statusValues[$key] = ($id ? (string) $id : null);
    }

    public static function assignStatus($model): void
    {
        $table = $model->getTable();
        if (!isset(self::DOCUMENTS[$table]) || !self::hasStatusColumn($table)) {
            return;
        }
        if (trim((string) $model->getAttribute(self::STATUS_FIELD)) !== '') {
            return;
        }
        $value = self::statusValueId($table, 0);
        if ($value !== null) {
            $model->setAttribute(self::STATUS_FIELD, $value);
        }
    }

    public static function markConfirmed(string $slug, int $id): void
    {
        if (!self::hasStatusColumn($slug)) {
            return;
        }
        $value = self::statusValueId($slug, 1);
        if ($value === null) {
            return;
        }
        DB::table($slug)->where('id', $id)
            ->where(fn ($q) => $q->whereNull(self::STATUS_FIELD)->orWhere(self::STATUS_FIELD, '!=', $value))
            ->update([self::STATUS_FIELD => $value]);
    }

    public static function assignNumber($model): void
    {
        $table = $model->getTable();
        if (!isset(self::DOCUMENTS[$table]) || !self::hasNumberColumn($table)) {
            return;
        }
        if (trim((string) $model->getAttribute(self::NUMBER_FIELD)) !== '') {
            return;
        }
        $number = DocumentNumber::next();
        if ($number) {
            $model->setAttribute(self::NUMBER_FIELD, $number);
        }
    }

    public static function pending(object $config): array
    {
        $documents = [];
        $settled = now()->subSeconds(self::SETTLE_SECONDS);
        foreach (self::DOCUMENTS as $slug => $type) {
            if (!Schema::hasTable($slug) || !self::hasNumberColumn($slug)) {
                continue;
            }
            $query = DB::table($slug . ' as d')
                ->leftJoin(self::STATE_TABLE . ' as s', function ($join) use ($slug) {
                    $join->on('s.document_id', '=', 'd.id')->where('s.slug', '=', $slug);
                })
                ->whereNull('d.deleted_at')
                ->whereNull('s.confirmed_at')
                ->whereNotNull('d.' . self::NUMBER_FIELD)
                ->where('d.' . self::NUMBER_FIELD, '!=', '')
                ->whereNotNull('d.products')
                ->whereNotIn('d.products', ['', '[]'])
                ->where('d.updated_at', '<=', $settled);
            if ($config->since) {
                $query->where('d.created_at', '>=', $config->since);
            }
            foreach ($query->orderBy('d.created_at')->orderBy('d.id')->limit(self::LIMIT)->get(['d.*']) as $row) {
                $document = self::present($slug, $row);
                if (!count($document['items'])) {
                    continue;
                }
                $documents[] = $document;
            }
        }
        usort($documents, fn ($a, $b) => [$a['date'], $a['id']] <=> [$b['date'], $b['id']]);
        $documents = array_slice($documents, 0, self::LIMIT);
        self::markSent($documents);

        return $documents;
    }

    private static function markSent(array $documents): void
    {
        $now = now();
        foreach ($documents as $document) {
            $slug = array_search($document['type'], self::DOCUMENTS, true);
            $exists = DB::table(self::STATE_TABLE)->where('slug', $slug)->where('document_id', $document['id'])->first(['id']);
            if ($exists) {
                DB::table(self::STATE_TABLE)->where('id', $exists->id)->update([
                    'number' => $document['number'],
                    'sent_at' => $now,
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => $now,
                ]);
                continue;
            }
            DB::table(self::STATE_TABLE)->insertOrIgnore([
                'slug' => $slug,
                'document_id' => $document['id'],
                'number' => $document['number'],
                'attempts' => 1,
                'sent_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public static function confirm(array $items): array
    {
        $confirmed = [];
        $unknown = [];
        $now = now();
        foreach ($items as $item) {
            $number = trim((string) ($item['number'] ?? ''));
            if ($number === '') {
                continue;
            }
            $found = null;
            foreach (array_keys(self::DOCUMENTS) as $slug) {
                if (!Schema::hasTable($slug) || !self::hasNumberColumn($slug)) {
                    continue;
                }
                $id = DB::table($slug)->where(self::NUMBER_FIELD, $number)->value('id');
                if ($id) {
                    $found = [$slug, (int) $id];
                    break;
                }
            }
            if (!$found) {
                $unknown[] = $number;
                continue;
            }
            $ref = trim((string) ($item['ref'] ?? '')) ?: null;
            $state = DB::table(self::STATE_TABLE)->where('slug', $found[0])->where('document_id', $found[1])->first(['id', 'confirmed_at']);
            $update = ['number' => $number, 'updated_at' => $now];
            if ($ref !== null) {
                $update['onec_ref'] = mb_substr($ref, 0, 191);
            }
            if ($state) {
                if (!$state->confirmed_at) {
                    $update['confirmed_at'] = $now;
                }
                DB::table(self::STATE_TABLE)->where('id', $state->id)->update($update);
            } else {
                DB::table(self::STATE_TABLE)->insert($update + [
                    'slug' => $found[0],
                    'document_id' => $found[1],
                    'confirmed_at' => $now,
                    'created_at' => $now,
                ]);
            }
            self::markConfirmed($found[0], $found[1]);
            $confirmed[] = $number;
        }

        return ['confirmed' => $confirmed, 'unknown' => $unknown];
    }

    public static function present(string $slug, object $row): array
    {
        $order = self::order($slug, $row);
        $isExpense = $slug === 'expense_invoices';
        $ownId = $isExpense
            ? (self::ids($row->shipment_company_id ?? null)[0] ?? ($order['shipment_company_id'] ?? null))
            : ($order['shipment_company_id'] ?? null);
        $partnerId = self::ids($row->company_id ?? null)[0] ?? null;
        if (!$isExpense && ($order['type'] ?? null) === 'supplier_order' && !empty($order['company_id'])) {
            $partnerId = $order['company_id'];
        }
        $storehouseField = self::STOREHOUSE_FIELDS[$slug];
        $storehouseId = self::ids($row->{$storehouseField} ?? null)[0] ?? null;

        $lines = array_values(array_filter(ShipmentService::decode($row->products ?? null), 'is_array'));
        $productIds = array_values(array_unique(array_filter(array_map(fn ($line) => (int) ($line['id'] ?? 0), $lines))));
        $products = self::products($productIds);
        $orderRows = self::orderRows($order['products'] ?? []);
        $items = [];
        foreach ($lines as $index => $line) {
            $quantity = (float) str_replace(',', '.', (string) ($line['count'] ?? 0));
            if ($quantity <= 0) {
                continue;
            }
            $productId = (int) ($line['id'] ?? 0);
            $name = ShipmentService::plainName($line['name'] ?? '');
            $product = $products[$productId] ?? null;
            $priceKey = isset($line['price']) && is_numeric($line['price']) ? 'price' : 'purchase_price';
            $parts = ShipmentService::lineParts($line, $priceKey);
            $items[] = [
                'row' => $index + 1,
                'order_row' => $orderRows['id'][$productId] ?? ($orderRows['name'][ShipmentService::nameKey($name)] ?? null),
                'quantity' => $quantity == (int) $quantity ? (int) $quantity : $quantity,
                'product_id' => $productId ?: null,
                'product_1c_id' => $product['id_1c'] ?? null,
                'product_b24_id' => $product['id_b24'] ?? null,
                'name' => $name,
                'price' => self::money(is_numeric($line[$priceKey] ?? null) ? (float) $line[$priceKey] : 0),
                'vat_rate' => self::money($parts['rate']),
                'vat_included' => $parts['included'],
                'sum' => self::money($parts['net']),
                'vat_sum' => self::money($parts['vat']),
                'total' => self::money($parts['gross']),
            ];
        }

        return [
            'type' => self::DOCUMENTS[$slug],
            'id' => (int) $row->id,
            'number' => (string) $row->{self::NUMBER_FIELD},
            'date' => self::date($row->created_at ?? null),
            'updated_at' => self::date($row->updated_at ?? null),
            'seller' => self::company($isExpense ? $ownId : $partnerId),
            'buyer' => self::company($isExpense ? $partnerId : $ownId),
            'order_number' => $order ? (string) $order['number'] : null,
            'order' => $order ? ['type' => $order['type'], 'id' => $order['id'], 'b24_id' => $order['b24_id'], 'name' => $order['name']] : null,
            'storehouse' => self::storehouse($storehouseId),
            'items' => $items,
        ];
    }

    private static function order(string $slug, object $row): ?array
    {
        $found = null;
        try {
            $parent = ShipmentService::parentOf($slug, (int) $row->id);
            if ($parent && ShipmentService::isSource($parent[0])) {
                $found = ShipmentService::parentOf($parent[0], (int) $parent[1]);
            } elseif ($parent && $parent[0] === ShipmentService::SUPPLIER) {
                $found = $parent;
            }
        } catch (\Throwable $e) {
            $found = null;
        }
        if (!$found) {
            $candidates = $slug === 'receipt_invoices'
                ? ['related_supplier_orders' => ShipmentService::SUPPLIER, 'related_deals' => 'deals']
                : ['related_deals' => 'deals', 'related_supplier_orders' => ShipmentService::SUPPLIER];
            foreach ($candidates as $column => $orderSlug) {
                $id = self::ids($row->{$column} ?? null)[0] ?? null;
                if ($id) {
                    $found = [$orderSlug, $id];
                    break;
                }
            }
        }
        if (!$found || !Schema::hasTable($found[0])) {
            return null;
        }
        $order = DB::table($found[0])->where('id', $found[1])->first();
        if (!$order) {
            return null;
        }
        $b24Id = trim((string) ($order->b24_id ?? '')) ?: null;

        return [
            'type' => $found[0] === 'deals' ? 'deal' : 'supplier_order',
            'id' => (int) $order->id,
            'b24_id' => $b24Id,
            'number' => $b24Id ?: (string) $order->id,
            'name' => self::text($order->name ?? ''),
            'shipment_company_id' => self::ids($order->shipment_company_id ?? null)[0] ?? null,
            'company_id' => self::ids($order->company_id ?? null)[0] ?? null,
            'products' => array_values(array_filter(ShipmentService::decode($order->products ?? null), 'is_array')),
        ];
    }

    private static function orderRows(array $lines): array
    {
        $rows = ['id' => [], 'name' => []];
        foreach ($lines as $index => $line) {
            $productId = (int) ($line['id'] ?? 0);
            if ($productId && !isset($rows['id'][$productId])) {
                $rows['id'][$productId] = $index + 1;
            }
            $name = ShipmentService::nameKey($line['name'] ?? '');
            if ($name !== '' && !isset($rows['name'][$name])) {
                $rows['name'][$name] = $index + 1;
            }
        }

        return $rows;
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
        if (!$company) {
            return null;
        }

        return [
            'id' => (int) $company->id,
            'name' => self::text($company->name ?? ''),
            'inn' => trim((string) ($company->inn ?? '')) ?: null,
            'kpp' => trim((string) ($company->kpp ?? '')) ?: null,
            'b24_id' => trim((string) ($company->b24_id ?? '')) ?: null,
        ];
    }

    private static function storehouse($id): ?array
    {
        if (!$id || !Schema::hasTable('storehouses')) {
            return null;
        }
        $columns = Schema::hasColumn('storehouses', self::STOREHOUSE_1C_FIELD) ? ['id', 'name', self::STOREHOUSE_1C_FIELD] : ['id', 'name'];
        $storehouse = DB::table('storehouses')->where('id', $id)->first($columns);

        return $storehouse ? [
            'id' => (int) $storehouse->id,
            'name' => self::text($storehouse->name ?? ''),
            'id_1c' => trim((string) ($storehouse->{self::STOREHOUSE_1C_FIELD} ?? '')) ?: null,
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
