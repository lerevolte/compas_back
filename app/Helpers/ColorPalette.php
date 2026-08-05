<?php

namespace App\Helpers;

class ColorPalette
{
    public const GRADIENTS = [
        'linear-gradient(to bottom, #aeee90 2%, #65dd78 100%)',
        'linear-gradient(to bottom, #ffdc96 2%, #ffc28d)',
        'linear-gradient(to bottom, #f1c3ff 2%, #ee9cff)',
        'linear-gradient(to bottom, #9ce1ff 2%, #6dc2ff 100%)',
        'linear-gradient(to bottom, #a8c7ff 2%, #948fff)',
        'linear-gradient(to bottom, #71d2fc 2%, #9490ff 100%)',
        'linear-gradient(to bottom, #5ef9e2 2%, #50e2d2 100%)',
        'linear-gradient(to bottom, #f1c3ff 2%, #a8c7ff)',
        'linear-gradient(to bottom, #aeee90 2%, #48b85a 99%)',
        'linear-gradient(to bottom, #ffdc96 2%, #ff8d8d 100%)',
        'linear-gradient(to bottom, #ffab8e 2%, #ff8596 100%)',
        'linear-gradient(to bottom, #ee9090 2%, #6765dd 100%)',
        'linear-gradient(to bottom, #ee90d2 4%, #dd6565 100%)',
        'linear-gradient(to bottom, #9390ee 2%, #dd65d5)',
    ];

    public static function random(): string
    {
        return self::GRADIENTS[array_rand(self::GRADIENTS)];
    }
}
