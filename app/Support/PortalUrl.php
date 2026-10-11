<?php

namespace App\Support;

/** URLs públicas del portal de clientes (host propio si está configurado; si no, /portal del CRM). */
class PortalUrl
{
    public static function base(): string
    {
        if ($url = config('portal.url')) {
            return rtrim($url, '/');
        }
        if ($domain = config('portal.domain')) {
            return 'https://'.$domain;
        }

        return rtrim(config('app.url'), '/');
    }

    public static function login(): string
    {
        return self::base().'/login';
    }

    public static function to(string $path = ''): string
    {
        return self::base().'/portal'.($path ? '/'.ltrim($path, '/') : '');
    }
}
