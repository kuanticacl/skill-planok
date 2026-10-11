<?php

namespace App\Services\Agent;

use App\Models\User;

/** Usuario que conversa con el Agent y registro de las acciones que ejecutó (para mostrarlas y auditarlas). */
class AgentContext
{
    /** @var array<int, array{tool: string, label: string, url: string|null, ok: bool}> */
    public array $actions = [];

    /** @var array<int, array{id: string, title: string, lines: list<string>, destructive: bool, op: string}> Acciones que esperan confirmación de la persona. */
    public array $pending = [];

    public function __construct(public readonly User $user) {}

    /** @param  array<string, mixed>  $action */
    public function propose(array $action): string
    {
        $id = PendingActions::store($this->user->id, $action);
        $this->pending[] = ['id' => $id, 'title' => $action['title'], 'lines' => $action['lines'], 'destructive' => $action['destructive'], 'op' => $action['op']];

        return $id;
    }

    public function record(string $tool, string $label, ?string $url = null, bool $ok = true): void
    {
        $this->actions[] = ['tool' => $tool, 'label' => $label, 'url' => $url, 'ok' => $ok];
    }
}
