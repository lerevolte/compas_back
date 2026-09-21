<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class DocumentNumber
{
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
}
