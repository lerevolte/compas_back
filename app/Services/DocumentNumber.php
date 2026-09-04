<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentNumber
{
    public const COUNTER = 'shipment_task';

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('document_counters');
        } catch (\Throwable $e) {
            return false;
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

            return $value > 0 ? (string) $value : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
