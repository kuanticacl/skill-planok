<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/** Proveedor de IA configurado en el CRM. La API key se guarda cifrada y nunca se envía al navegador. */
#[Fillable(['slug', 'name', 'driver', 'api_key', 'base_url', 'model', 'is_enabled', 'is_default', 'last_tested_at', 'last_test_ok', 'last_test_message', 'last_test_ms', 'created_by'])]
#[Hidden(['api_key'])]
class AiProvider extends Model
{
    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'last_test_ok' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function hasKey(): bool
    {
        return filled($this->api_key);
    }

    /** "••••abcd": solo los últimos 4 caracteres. */
    public function keyHint(): ?string
    {
        return $this->hasKey() ? '••••'.substr($this->api_key, -4) : null;
    }

    public function isUsable(): bool
    {
        return $this->is_enabled && ($this->hasKey() || $this->driver === 'ollama' || ($this->driver === 'openai-compatible' && filled($this->base_url)));
    }
}
