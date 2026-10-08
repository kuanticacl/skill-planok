<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Lista los modelos disponibles de un proveedor (para elegirlos en lugar de escribirlos de memoria). */
class ModelLister
{
    /** @return array{models: array<int, array{id: string, name: string}>, error: ?string} */
    public function list(AiProvider $p): array
    {
        $catalog = AiCatalog::get($p->slug) ?? [];
        $base = rtrim($p->base_url ?: ($catalog['url'] ?? ''), '/');

        try {
            $response = match ($p->driver) {
                'anthropic' => Http::withHeaders(['x-api-key' => (string) $p->api_key, 'anthropic-version' => '2023-06-01'])->get($base.'/models', ['limit' => 100]),
                'gemini' => Http::get($base.'/models', ['key' => $p->api_key, 'pageSize' => 200]),
                'openrouter' => Http::get($base.'/models'),
                'ollama' => Http::get($base.'/api/tags'),
                default => Http::withToken((string) $p->api_key)->get($base.'/models'),
            };
        } catch (Throwable) {
            return ['models' => [], 'error' => 'No se pudo conectar para listar los modelos.'];
        }

        if (! $response->successful()) {
            return ['models' => [], 'error' => $response->status() === 401 || $response->status() === 403
                ? 'La API key fue rechazada.'
                : 'Este proveedor no permite listar modelos (escribe el nombre a mano).'];
        }

        $json = $response->json();
        $items = match ($p->driver) {
            'gemini' => collect($json['models'] ?? [])->map(fn ($m) => ['id' => str_replace('models/', '', $m['name'] ?? ''), 'name' => $m['displayName'] ?? ($m['name'] ?? '')]),
            'ollama' => collect($json['models'] ?? [])->map(fn ($m) => ['id' => $m['name'] ?? '', 'name' => $m['name'] ?? '']),
            default => collect($json['data'] ?? [])->map(fn ($m) => ['id' => $m['id'] ?? '', 'name' => $m['display_name'] ?? $m['name'] ?? ($m['id'] ?? '')]),
        };

        $models = $items->filter(fn ($m) => $m['id'] !== '')
            ->reject(fn ($m) => preg_match('/embed|whisper|tts|dall-e|image|moderation|rerank|audio|realtime|transcribe/i', $m['id']))
            ->sortBy('id')->values()->take(400)->all();

        return ['models' => $models, 'error' => $models ? null : 'El proveedor no devolvió modelos de texto.'];
    }
}
