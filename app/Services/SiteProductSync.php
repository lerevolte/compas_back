<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SiteProductSync
{
    public const TABLE = 'site_sync_config';
    public const FIELDS = ['length', 'width', 'height', 'volume', 'weight', 'storage_unit', 'supplier_barcode', 'pallet_quantity', 'replenishment_period', 'replenishment_method', 'storage_conditions'];
    public const TRIGGER_FIELDS = ['article', 'length', 'width', 'height', 'volume', 'weight', 'storage_unit', 'supplier_barcode', 'pallet_quantity', 'replenishment_period', 'replenishment_method', 'storage_conditions'];
    public const BATCH = 100;

    public static bool $muted = false;

    private static ?array $configCache = null;
    private static array $optionsCache = [];

    public static function ensureTable($db): void
    {
        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `site_sync_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `url` text,
  `token` varchar(191) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public static function config(): ?array
    {
        if (self::$configCache !== null) {
            return self::$configCache ?: null;
        }
        self::$configCache = [];
        try {
            if (Schema::hasTable(self::TABLE) && Schema::hasColumn('products', 'article')) {
                $row = DB::table(self::TABLE)->where('enabled', 1)->orderBy('id')->first();
                if ($row && trim((string) $row->url) !== '' && trim((string) $row->token) !== '') {
                    self::$configCache = ['url' => trim((string) $row->url), 'token' => trim((string) $row->token)];
                }
            }
        } catch (\Throwable $e) {
            self::$configCache = [];
        }

        return self::$configCache ?: null;
    }

    public static function resetCache(): void
    {
        self::$configCache = null;
        self::$optionsCache = [];
    }

    public static function ready(): bool
    {
        return self::config() !== null;
    }

    public static function payload(Product $product): ?array
    {
        $article = trim((string) $product->article);
        if ($article === '' || !ctype_digit($article)) {
            return null;
        }
        $data = ['id' => (int) $article, 'compas_id' => (int) $product->id];
        foreach (self::FIELDS as $field) {
            if (!Schema::hasColumn('products', $field)) {
                continue;
            }
            $raw = $product->getAttribute($field);
            $decoded = is_string($raw) && is_array($tmp = json_decode($raw, true)) ? $tmp : $raw;
            if (in_array($field, ['storage_unit', 'replenishment_method'], true)) {
                $value = is_array($decoded) ? ($decoded[0] ?? null) : $decoded;
                $data[$field] = self::optionLabel($field, $value);
            } elseif ($field === 'storage_conditions') {
                $values = is_array($decoded) ? $decoded : ($decoded === null || $decoded === '' ? [] : [$decoded]);
                $data[$field] = array_values(array_filter(array_map(fn ($v) => self::optionLabel($field, $v), $values), fn ($v) => $v !== null && $v !== ''));
            } else {
                $value = is_array($decoded) ? ($decoded[0] ?? null) : $decoded;
                $value = is_string($value) ? str_replace(',', '.', trim($value)) : $value;
                $data[$field] = $value === null || $value === '' ? null : (is_numeric($value) ? (float) $value : (string) $value);
            }
        }

        return array_filter($data, fn ($v) => !($v === null || $v === '' || $v === []));
    }

    private static function optionLabel(string $field, $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!isset(self::$optionsCache[$field])) {
            $map = [];
            try {
                $typeId = DB::table('data_types')->where('slug', 'products')->value('id');
                $details = $typeId ? DB::table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->value('details') : null;
                $decoded = is_string($details) ? json_decode($details, true) : null;
                foreach ((is_array($decoded) ? ($decoded['options'] ?? []) : []) as $option) {
                    if (!is_array($option) || !array_key_exists('value', $option)) {
                        continue;
                    }
                    $label = $option['label'] ?? '';
                    $label = is_array($label) ? ($label['text'] ?? '') : $label;
                    $map[(string) $option['value']] = trim((string) $label);
                }
            } catch (\Throwable $e) {
            }
            self::$optionsCache[$field] = $map;
        }

        return self::$optionsCache[$field][(string) $value] ?? (string) $value;
    }

    public static function push(array $products): array
    {
        $config = self::config();
        $stat = ['sent' => 0, 'updated' => 0, 'missing' => [], 'errors' => []];
        if (!$config || !count($products)) {
            return $stat;
        }
        foreach (array_chunk(array_values($products), self::BATCH) as $chunk) {
            $stat['sent'] += count($chunk);
            try {
                $response = Http::timeout(60)
                    ->withHeaders(['X-Compas-Token' => $config['token']])
                    ->post($config['url'], ['token' => $config['token'], 'products' => $chunk]);
                $body = $response->json();
                if (!$response->ok() || !is_array($body)) {
                    $stat['errors'][] = 'HTTP ' . $response->status() . ': ' . mb_substr((string) $response->body(), 0, 300);
                    Log::channel('site_sync')->warning('site-sync: сайт ответил ошибкой', ['status' => $response->status(), 'body' => mb_substr((string) $response->body(), 0, 500)]);
                    continue;
                }
                $stat['updated'] += count($body['updated'] ?? []);
                $stat['missing'] = array_merge($stat['missing'], $body['missing'] ?? []);
                foreach ($body['errors'] ?? [] as $error) {
                    $stat['errors'][] = is_array($error) ? (($error['id'] ?? '?') . ': ' . ($error['error'] ?? '')) : (string) $error;
                }
                if (!empty($body['unknown_properties'])) {
                    Log::channel('site_sync')->warning('site-sync: на сайте нет свойств', ['codes' => $body['unknown_properties']]);
                }
            } catch (\Throwable $e) {
                $stat['errors'][] = $e->getMessage();
                Log::channel('site_sync')->error('site-sync: запрос не удался', ['error' => $e->getMessage()]);
            }
        }
        Log::channel('site_sync')->info('site-sync: отправлено', ['sent' => $stat['sent'], 'updated' => $stat['updated'], 'missing' => count($stat['missing']), 'errors' => count($stat['errors'])]);

        return $stat;
    }

    public static function read(array $ids, string $action = 'read'): array
    {
        $config = self::config();
        if (!$config || !count($ids)) {
            return ['items' => [], 'missing' => [], 'error' => $config ? null : 'not configured'];
        }
        try {
            $response = Http::timeout(120)
                ->withHeaders(['X-Compas-Token' => $config['token']])
                ->post($config['url'], ['token' => $config['token'], 'action' => $action, 'ids' => array_values($ids)]);
            $body = $response->json();
            if (!$response->ok() || !is_array($body) || empty($body['ok'])) {
                return ['items' => [], 'missing' => [], 'error' => 'HTTP ' . $response->status() . ': ' . mb_substr((string) $response->body(), 0, 300)];
            }

            return ['items' => $body['items'] ?? [], 'missing' => $body['missing'] ?? [], 'error' => null];
        } catch (\Throwable $e) {
            return ['items' => [], 'missing' => [], 'error' => $e->getMessage()];
        }
    }

    public static function links(array $ids): array
    {
        return self::read($ids, 'links');
    }

    public static function optionValue(string $field, string $label)
    {
        self::optionLabel($field, '__warmup__');
        $needle = mb_strtolower(trim($label));
        foreach (self::$optionsCache[$field] ?? [] as $value => $text) {
            if (mb_strtolower(trim($text)) === $needle) {
                return is_numeric($value) ? (int) $value : $value;
            }
        }

        return null;
    }

    public static function pushProducts(iterable $products): array
    {
        $payloads = [];
        foreach ($products as $product) {
            $payload = self::payload($product);
            if ($payload) {
                $payloads[] = $payload;
            }
        }

        return self::push($payloads);
    }
}
