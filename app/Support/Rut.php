<?php

namespace App\Support;

/** Utilidades para el RUT chileno (módulo 11). */
class Rut
{
    /** Cuerpo y dígito verificador normalizados, o null si el formato no es válido. @return array{0: string, 1: string}|null */
    public static function parse(?string $value): ?array
    {
        $clean = strtoupper(preg_replace('/[^0-9kK]/', '', (string) $value));
        if (strlen($clean) < 2 || ! preg_match('/^\d{1,8}[0-9K]$/', $clean)) {
            return null;
        }

        return [substr($clean, 0, -1), substr($clean, -1)];
    }

    public static function dv(string $body): string
    {
        $sum = 0;
        $mult = 2;
        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += (int) $body[$i] * $mult;
            $mult = $mult === 7 ? 2 : $mult + 1;
        }
        $r = 11 - ($sum % 11);

        return $r === 11 ? '0' : ($r === 10 ? 'K' : (string) $r);
    }

    public static function isValid(?string $value): bool
    {
        $p = self::parse($value);

        return $p !== null && self::dv($p[0]) === $p[1];
    }

    /** 76123456-7 → 76.123.456-7 */
    public static function format(?string $value): ?string
    {
        $p = self::parse($value);

        return $p ? number_format((int) $p[0], 0, '', '.').'-'.$p[1] : ($value ?: null);
    }
}
