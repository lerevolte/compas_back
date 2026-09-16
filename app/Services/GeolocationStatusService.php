<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeolocationStatusService
{
    public const FIELD = 'geolocation_status';
    public const ON = 'Данные передаются';
    public const OFF = 'Данные не передаются';
    public const FRESH_SECONDS = 300;

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('employees')
                && Schema::hasColumn('employees', self::FIELD)
                && Schema::hasColumn('users', 'geoposition');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function valueIds(): array
    {
        $typeId = DB::table('data_types')->where('slug', 'employees')->value('id');
        $fieldId = $typeId
            ? DB::table('data_rows')->where('data_type_id', $typeId)->where('field', self::FIELD)->value('id')
            : null;
        if (!$fieldId) {
            return [];
        }
        $values = DB::table('field_values')->where('field_id', $fieldId)->pluck('id', 'value');
        $on = $values[self::ON] ?? null;
        $off = $values[self::OFF] ?? null;

        return $on && $off ? ['on' => (string) $on, 'off' => (string) $off] : [];
    }

    public static function refresh(?array $userIds = null): int
    {
        if (!self::ready()) {
            return 0;
        }
        $ids = self::valueIds();
        if (!count($ids)) {
            return 0;
        }

        $query = DB::table('users')->whereNotNull('geoposition')->where('geoposition', '!=', '');
        if ($userIds !== null) {
            $userIds = array_values(array_filter(array_map('intval', $userIds)));
            if (!count($userIds)) {
                return 0;
            }
            $query->whereIn('id', $userIds);
        }

        $hasRelated = Schema::hasColumn('employees', 'related_user_id');
        $hasEmployeeId = Schema::hasColumn('users', 'employee_id');

        $columns = $hasEmployeeId ? ['id', 'geoposition', 'employee_id'] : ['id', 'geoposition'];
        $updated = 0;
        foreach ($query->get($columns) as $user) {
            $employeeId = null;
            if ($hasRelated) {
                $employeeId = DB::table('employees')->where('related_user_id', $user->id)->value('id');
            }
            if (!$employeeId && $hasEmployeeId) {
                $raw = $user->employee_id ?? null;
                if (is_string($raw) && is_array($decoded = json_decode($raw, true))) {
                    $raw = $decoded[0] ?? null;
                }
                $employeeId = is_numeric($raw) ? (int) $raw : null;
            }
            if (!$employeeId) {
                continue;
            }

            $decoded = json_decode((string) $user->geoposition, true);
            $time = is_array($decoded) && is_numeric($decoded['time'] ?? null) ? (float) $decoded['time'] : 0;
            if ($time > 100000000000) {
                $time = $time / 1000;
            }
            $fresh = $time > 0 && (time() - $time) <= self::FRESH_SECONDS;
            $target = $fresh ? $ids['on'] : $ids['off'];

            $updated += DB::table('employees')->where('id', $employeeId)
                ->where(function ($q) use ($target) {
                    $q->whereNull(self::FIELD)->orWhere(self::FIELD, '!=', $target);
                })
                ->update([self::FIELD => $target]);
        }

        return $updated;
    }
}
