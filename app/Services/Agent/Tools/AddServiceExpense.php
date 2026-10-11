<?php

namespace App\Services\Agent\Tools;

use App\Models\ClientService;
use App\Services\Billing\ContractService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Ai\Tools\Request;

class AddServiceExpense extends CrmTool
{
    protected ?string $permission = 'contracts.costs';

    public function name(): string
    {
        return 'add_service_expense';
    }

    public function description(): string
    {
        return 'Registra un costo o gasto INTERNO de un servicio contratado (el cliente nunca lo ve). Úsalo para llevar el margen del servicio.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'contract_id' => $schema->integer()->required(),
            'concept' => $schema->string()->required(),
            'amount' => $schema->number()->required(),
            'currency' => $schema->string()->enum(['CLP', 'UF']),
            'incurred_on' => $schema->string()->description('YYYY-MM-DD (por defecto hoy).'),
            'notes' => $schema->string(),
        ];
    }

    protected function run(Request $r): array|string
    {
        $d = $r->validate([
            'contract_id' => ['required', 'integer', Rule::exists('client_services', 'id')->whereNull('deleted_at')], 'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'], 'currency' => ['nullable', Rule::in(['CLP', 'UF'])], 'incurred_on' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $s = ClientService::findOrFail($d['contract_id']);
        app(ContractService::class)->addExpense($s, ['concept' => $d['concept'], 'amount' => $d['amount'], 'currency' => $d['currency'] ?? 'CLP', 'incurred_on' => $d['incurred_on'] ?? today()->toDateString(), 'notes' => $d['notes'] ?? null], $this->ctx->user);
        $this->ctx->record($this->name(), "Registró un gasto en «{$s->name}»", url('/contracts/'.$s->id));

        return 'OK: gasto registrado.';
    }
}
