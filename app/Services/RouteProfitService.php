<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class RouteProfitService
{
    public const FIELD = 'profit_percent';
    public const TITLE = '% прибыли';
    public const PLANNED = 'reserve_for_delivery';
    public const ACTUAL = 'delivery_price';

    private static array $ready = [];

    public static function ready(): bool
    {
        $key = (string) (function_exists('tenant') ? tenant('id') : '');
        if (!array_key_exists($key, self::$ready)) {
            try {
                self::$ready[$key] = Schema::hasColumn('routes', self::FIELD);
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

    public static function compute($planned, $actual): ?float
    {
        $planned = self::number($planned);
        $actual = self::number($actual);
        if ($planned === null || $actual === null || $planned <= 0) {
            return null;
        }

        return round(($planned - $actual) / $planned * 100, 2);
    }

    public static function backfillSql(): string
    {
        $planned = '`' . self::PLANNED . '`';
        $actual = '`' . self::ACTUAL . '`';

        return 'UPDATE `routes` SET `' . self::FIELD . "` = CASE WHEN {$planned} REGEXP '^-?[0-9]+([.,][0-9]+)?$' AND {$actual} REGEXP '^-?[0-9]+([.,][0-9]+)?$' AND REPLACE({$planned}, ',', '.') + 0 > 0"
            . " THEN ROUND((REPLACE({$planned}, ',', '.') - REPLACE({$actual}, ',', '.')) / REPLACE({$planned}, ',', '.') * 100, 2) ELSE NULL END";
    }

    private static function number($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $value = str_replace([',', ' '], ['.', ''], trim((string) $value));

        return $value !== '' && is_numeric($value) ? (float) $value : null;
    }
}
