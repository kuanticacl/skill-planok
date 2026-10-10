<?php

namespace App\Services\Agent\Tools;

use App\Models\Client;
use App\Support\Rut;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

class CreateClient extends CrmTool
{
    protected ?string $permission = 'clients.create';

    public function name(): string
    {
        return 'create_client';
    }

    public function description(): string
    {
        return 'Crea una EMPRESA (organización con RUT; no una persona: para personas usa create_lead). Busca antes con search_clients; si ya existe, usa ese. El RUT se valida (dígito verificador) y se formatea.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('Nombre de fantasía / comercial.'),
            'legal_name' => $schema->string()->description('Razón social.'),
            'tax_id' => $schema->string()->description('RUT de la empresa (ej. 76.405.003-7).'),
            'activity' => $schema->string()->description('Giro.'),
            'contact_name' => $schema->string(),
            'contact_role' => $schema->string(),
            'email' => $schema->string(),
            'phone' => $schema->string(),
            'website' => $schema->string(),
            'address' => $schema->string(),
            'commune' => $schema->string(),
            'city' => $schema->string(),
            'notes' => $schema->string(),
        ];
    }

    protected function run(Request $r): array|string
    {
        $d = $r->validate([
            'name' => ['required', 'string', 'max:255'], 'legal_name' => ['nullable', 'string', 'max:255'], 'activity' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20', fn ($a, $v, $fail) => $v && ! Rut::isValid($v) ? $fail('El RUT no es válido (revisa el dígito verificador).') : null],
            'contact_name' => ['nullable', 'string', 'max:255'], 'contact_role' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'], 'commune' => ['nullable', 'string', 'max:120'], 'city' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:20000'],
        ]);
        if (! empty($d['tax_id'])) {
            $d['tax_id'] = Rut::format($d['tax_id']);
            if ($dup = Client::where('tax_id', $d['tax_id'])->first()) {
                return "ERROR: ya existe una empresa con ese RUT (id {$dup->id}, {$dup->name}). Úsalo en vez de crear otro.";
            }
        }

        $client = Client::create([...$d, 'is_active' => true, 'created_by' => $this->ctx->user->id]);
        $this->ctx->record($this->name(), "Creó la empresa {$client->name}", url('/clients/'.$client->id));

        return ['id' => $client->id, 'name' => $client->name, 'rut' => $client->tax_id, 'url' => url('/clients/'.$client->id)];
    }
}
