<?php

namespace App\Helpers;

class SchemaCache
{
    protected static $tables = [];
    protected static $columns = [];

    protected static function db(): string
    {
        return (string) \DB::connection()->getDatabaseName();
    }

    public static function reset(): void
    {
        self::$tables = [];
        self::$columns = [];
    }

    public static function hasTable(string $table): bool
    {
        $db = self::db();
        if (!isset(self::$tables[$db])) {
            $names = [];
            foreach (\DB::select('SELECT table_name AS t FROM information_schema.tables WHERE table_schema = ?', [$db]) as $row) {
                $names[strtolower((string) $row->t)] = true;
            }
            self::$tables[$db] = $names;
        }
        return isset(self::$tables[$db][strtolower($table)]);
    }

    public static function columns(string $table): array
    {
        $key = self::db() . '.' . strtolower($table);
        if (!isset(self::$columns[$key])) {
            $cols = [];
            if (self::hasTable($table)) {
                foreach (\Schema::getColumnListing($table) as $c) {
                    $cols[strtolower($c)] = true;
                }
            }
            self::$columns[$key] = $cols;
        }
        return self::$columns[$key];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return isset(self::columns($table)[strtolower($column)]);
    }
}
