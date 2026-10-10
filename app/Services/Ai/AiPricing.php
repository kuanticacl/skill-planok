<?php

namespace App\Services\Ai;

use App\Models\Setting;

/**
 * Precios (USD por millón de tokens) para estimar el gasto en IA. Los proveedores no devuelven el costo,
 * así que es una ESTIMACIÓN: tokens registrados × precio. Los valores por defecto son de referencia y se
 * pueden corregir por modelo desde la pantalla de consumo (Setting «ai.prices»); un modelo sin precio no suma costo.
 */
class AiPricing
{
    /** Patrón (se busca dentro del nombre del modelo, de lo más específico a lo general) => [entrada, salida]. */
    private const DEFAULTS = [
        'gpt-4o-mini' => [0.15, 0.60],
        'gpt-4o' => [2.50, 10.00],
        'gpt-4.1-nano' => [0.10, 0.40],
        'gpt-4.1-mini' => [0.40, 1.60],
        'gpt-4.1' => [2.00, 8.00],
        'claude-opus-4' => [15.00, 75.00],
        'claude-sonnet-4' => [3.00, 15.00],
        'claude-3-7-sonnet' => [3.00, 15.00],
        'claude-3-5-sonnet' => [3.00, 15.00],
        'claude-haiku-4' => [1.00, 5.00],
        'claude-3-5-haiku' => [0.80, 4.00],
        'gemini-2.5-pro' => [1.25, 10.00],
        'gemini-2.5-flash' => [0.30, 2.50],
        'gemini-2.0-flash' => [0.10, 0.40],
        'deepseek-reasoner' => [0.55, 2.19],
        'deepseek-chat' => [0.27, 1.10],
    ];

    /** @return array<string, array{0: float, 1: float}> precios definidos por el usuario, por clave exacta (proveedor/modelo). */
    public function overrides(): array
    {
        $raw = Setting::get('ai.prices');
        $data = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($data) ? $data : [];
    }

    public static function key(?string $provider, ?string $model): string
    {
        return ($provider ?: '?').'/'.($model ?: 'predeterminado');
    }

    /** @return array{in: float, out: float, source: 'custom'|'default'}|null */
    public function find(?string $provider, ?string $model): ?array
    {
        $own = $this->overrides()[self::key($provider, $model)] ?? null;
        if (is_array($own) && count($own) === 2) {
            return ['in' => (float) $own[0], 'out' => (float) $own[1], 'source' => 'custom'];
        }

        $name = strtolower((string) $model);
        foreach (self::DEFAULTS as $pattern => [$in, $out]) {
            if ($name !== '' && str_contains($name, $pattern)) {
                return ['in' => $in, 'out' => $out, 'source' => 'default'];
            }
        }

        return null;
    }

    public function cost(?string $provider, ?string $model, int $inputTokens, int $outputTokens): ?float
    {
        $p = $this->find($provider, $model);

        return $p ? ($inputTokens * $p['in'] + $outputTokens * $p['out']) / 1_000_000 : null;
    }
}
