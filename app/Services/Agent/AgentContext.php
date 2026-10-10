<?php

namespace App\Services\Agent;

use App\Models\User;

/** Usuario que conversa con el Agent y registro de las acciones que ejecutó (para mostrarlas y auditarlas). */
class AgentContext
{
    /** @var array<int, array{tool: string, label: string, url: string|null, ok: bool}> */
    public array $actions = [];

    public function __construct(public readonly User $user) {}

    public function record(string $tool, string $label, ?string $url = null, bool $ok = true): void
    {
        $this->actions[] = ['tool' => $tool, 'label' => $label, 'url' => $url, 'ok' => $ok];
    }
}
