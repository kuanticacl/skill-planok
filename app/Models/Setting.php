<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/** Ajustes clave/valor editables desde el CRM. Los secretos (API keys) se guardan cifrados. */
#[Fillable(['key', 'value', 'is_secret'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return ['is_secret' => 'boolean'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn () => static::all()->mapWithKeys(fn (self $s) => [$s->key => [$s->value, $s->is_secret]])->all());

        if (! array_key_exists($key, $all) || $all[$key][0] === null || $all[$key][0] === '') {
            return $default;
        }

        [$value, $secret] = $all[$key];

        if ($secret) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $value;
    }

    public static function put(string $key, mixed $value, bool $secret = false): void
    {
        $stored = $value === null || $value === '' ? null : ($secret ? Crypt::encryptString((string) $value) : (string) $value);

        static::updateOrCreate(['key' => $key], ['value' => $stored, 'is_secret' => $secret]);
        Cache::forget('settings.all');
    }

    public static function has(string $key): bool
    {
        return static::get($key) !== null;
    }
}
