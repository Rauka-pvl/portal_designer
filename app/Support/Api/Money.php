<?php

namespace App\Support\Api;

class Money
{
    public static function formatMoney(float|string|int|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
