<?php

namespace App\Services;

class TransferService
{
    public const ORDER = 'transfer_orders';
    public const DOC = 'transfers';
    public const DATE_FIELD = 'transfer_date';
    public const DATE_TITLE = 'Дата перемещения';

    public static function isEntity(string $slug): bool
    {
        return $slug === self::ORDER || $slug === self::DOC;
    }
}
