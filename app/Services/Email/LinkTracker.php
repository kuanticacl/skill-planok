<?php

namespace App\Services\Email;

use App\Models\EmailMessage;

/** Reescribe enlaces para medir clics y agrega el pixel de aperturas. */
class LinkTracker
{
    public static function sign(string $uuid, string $url): string
    {
        return hash_hmac('sha256', $uuid.'|'.$url, config('app.key'));
    }

    public static function clickUrl(string $uuid, string $url): string
    {
        return route('track.click', ['uuid' => $uuid, 'u' => rtrim(strtr(base64_encode($url), '+/', '-_'), '='), 's' => substr(self::sign($uuid, $url), 0, 32)]);
    }

    /** @return string|null URL original si la firma es válida */
    public static function resolve(string $uuid, string $encoded, string $signature): ?string
    {
        $url = base64_decode(strtr($encoded, '-_', '+/'), true);

        if (! $url || ! preg_match('#^https?://#i', $url)) {
            return null;
        }

        return hash_equals(substr(self::sign($uuid, $url), 0, 32), $signature) ? $url : null;
    }

    public static function apply(string $html, EmailMessage $message): string
    {
        if ($message->track_clicks) {
            $html = preg_replace_callback(
                '/(<a\b[^>]*?\shref\s*=\s*)(["\'])(https?:\/\/[^"\']+)\2/i',
                function (array $m) use ($message) {
                    $url = html_entity_decode($m[3]);

                    // no se rastrean los enlaces de baja ni de seguimiento
                    if (str_contains($url, '/unsubscribe/') || str_contains($url, '/t/c/')) {
                        return $m[0];
                    }

                    return $m[1].$m[2].e(self::clickUrl($message->uuid, $url)).$m[2];
                },
                $html,
            );
        }

        if ($message->track_opens) {
            $pixel = '<img src="'.e(route('track.open', ['uuid' => $message->uuid])).'" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0" />';
            $html = stripos($html, '</body>') !== false
                ? preg_replace('/<\/body>/i', $pixel.'</body>', $html, 1)
                : $html.$pixel;
        }

        return $html;
    }
}
