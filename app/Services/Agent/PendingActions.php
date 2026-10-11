<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Acciones propuestas por el Agent que esperan la confirmación de la persona (30 min, de un solo uso, atadas al usuario). */
class PendingActions
{
    private const TTL_MINUTES = 30;

    /** @param  array<string, mixed>  $action */
    public static function store(int $userId, array $action): string
    {
        $id = (string) Str::ulid();
        Cache::put(self::key($userId, $id), $action, now()->addMinutes(self::TTL_MINUTES));

        return $id;
    }

    /** Consume la acción (no se puede confirmar dos veces). @return array<string, mixed>|null */
    public static function take(int $userId, string $id): ?array
    {
        return Cache::pull(self::key($userId, $id));
    }

    private static function key(int $userId, string $id): string
    {
        return "agent.pending.{$userId}.{$id}";
    }
}
