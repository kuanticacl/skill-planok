<?php

namespace App\Support;

/** Formato de montos para correos y textos: CLP «$119.000», UF «UF 12,50». */
class Money
{
    public static function format(float|int|null $amount, string $currency = 'CLP'): string
    {
        $amount = (float) $amount;

        return $currency === 'UF'
            ? 'UF '.number_format($amount, 2, ',', '.')
            : '$'.number_format($amount, 0, ',', '.');
    }
}
