<?php

namespace App\Services\Agent\Tools;

use App\Services\Agent\AgentContext;
use App\Services\Agent\ToolArgs;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

/**
 * Base de las herramientas del Agent. Cada una actúa COMO el usuario que conversa: respeta sus permisos y
 * la visibilidad de leads/propuestas, y devuelve errores como texto para que el modelo los explique o corrija.
 */
abstract class CrmTool implements Tool
{
    /** Permiso necesario (clave de config/permissions.php) o null si basta con estar autenticado. */
    protected ?string $permission = null;

    public function __construct(protected AgentContext $ctx) {}

    abstract public function name(): string;

    /** @return array<string, mixed>|string datos para el modelo */
    abstract protected function run(Request $request): array|string;

    final public function handle(Request $request): Stringable|string
    {
        if ($this->permission && ! $this->ctx->user->hasPermission($this->permission)) {
            $this->ctx->record($this->name(), 'Sin permiso para esta acción', null, false);

            return 'ERROR: el usuario no tiene permiso para esta acción ('.$this->permission.'). Díselo y sugiere pedir acceso a un administrador.';
        }

        try {
            $out = $this->run(ToolArgs::from($request));

            return is_string($out) ? $out : json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (ValidationException $e) {
            $this->ctx->record($this->name(), 'Datos inválidos', null, false);

            return 'ERROR de validación: '.collect($e->errors())->flatten()->implode(' | ');
        } catch (Throwable $e) {
            report($e);
            $this->ctx->record($this->name(), 'Falló la acción', null, false);

            return 'ERROR: '.$e->getMessage();
        }
    }

    protected function limit(Request $r, int $default = 8, int $max = 20): int
    {
        return max(1, min($max, (int) ($r['limit'] ?? $default)));
    }
}
