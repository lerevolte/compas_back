<?php

namespace App\Services\Saby;

class SabyFieldAccess
{
    public const ENTITY = 'logistic_tasks';
    public const TYPE = 'waybills';

    public static function can(string $mode): bool
    {
        $user = \Auth::user();
        if (!$user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }
        try {
            $settings = get_settings();
            foreach ($settings[self::ENTITY]['fields'] ?? [] as $field) {
                if ($field->type === self::TYPE) {
                    return (bool) ($settings[self::ENTITY]['perms'][$field->field][$mode] ?? 1);
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    public static function denied()
    {
        if (self::can('write')) {
            return null;
        }

        return response()->json(['message' => 'Недостаточно прав для работы с заказами в Саби'], 403);
    }
}
