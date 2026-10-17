<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentNumber
{
    public const COUNTER = 'shipment_task';
    public const PREFIX = 'cmps-';
    public const SHARED_ID_COUNTER = 'shipment_source_id';
    public const SHARED_ID_START = 10000;
    public const SHARED_ID_TABLES = ['logistic_tasks', 'pickups'];

    public static function ensureTable($db): void
    {
        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `document_counters` (
  `name` varchar(64) NOT NULL,
  `value` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('document_counters');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function nextSharedId($db = null): ?int
    {
        $db = $db ?: DB::connection();
        try {
            $schema = $db->getSchemaBuilder();
            if (!$schema->hasTable('document_counters')) {
                self::ensureTable($db);
            }
            $max = self::SHARED_ID_START - 1;
            foreach (self::SHARED_ID_TABLES as $table) {
                if ($schema->hasTable($table)) {
                    $max = max($max, (int) $db->table($table)->max('id'));
                }
            }
            $db->table('document_counters')->insertOrIgnore(['name' => self::SHARED_ID_COUNTER, 'value' => 0]);
            $db->statement('UPDATE document_counters SET value = LAST_INSERT_ID(GREATEST(value, ?) + 1) WHERE name = ?', [$max, self::SHARED_ID_COUNTER]);
            $value = (int) $db->getPdo()->lastInsertId();

            return $value > $max ? $value : null;
        } catch (\Throwable $e) {
            \Log::warning('Не удалось получить сквозной id задачи/самовывоза', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public static function next(string $name = self::COUNTER): ?string
    {
        if (!self::ready()) {
            return null;
        }
        try {
            DB::table('document_counters')->insertOrIgnore(['name' => $name, 'value' => 0]);
            DB::statement('UPDATE document_counters SET value = LAST_INSERT_ID(value + 1) WHERE name = ?', [$name]);
            $value = (int) DB::getPdo()->lastInsertId();

            return $value > 0 ? self::PREFIX . $value : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
