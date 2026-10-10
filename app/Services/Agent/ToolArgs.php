<?php

namespace App\Services\Agent;

use Laravel\Ai\Tools\Request;

/** Argumentos de una herramienta: leer una clave que el modelo no envió devuelve null en vez de lanzar error. */
class ToolArgs extends Request
{
    public function offsetGet(mixed $offset): mixed
    {
        return $this->arguments[$offset] ?? null;
    }

    public static function from(Request $r): self
    {
        return new self($r->all(), $r->toolCallId(), $r->toolInvocationId());
    }
}
