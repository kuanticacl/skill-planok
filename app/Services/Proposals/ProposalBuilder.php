<?php

namespace App\Services\Proposals;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Support\Rut;
use Illuminate\Support\Facades\DB;

/** Arma, valida y persiste propuestas. Los totales siempre se recalculan en el servidor. */
class ProposalBuilder
{
    /** Secciones con las que parte cada propuesta (editables). @return array<int, array{title: string, body: string}> */
    public static function defaultSections(): array
    {
        return [
            ['title' => 'Resumen ejecutivo', 'body' => "Gracias por la oportunidad de presentar esta propuesta a **[CLIENTE]**. En Quiebre ayudamos a inmobiliarias a atraer y cerrar más ventas con datos y gestión comercial: dejar de adivinar y empezar a convertir.\n\nEste documento resume lo que proponemos, cómo lo haremos y la inversión asociada."],
            ['title' => 'Objetivos', 'body' => "- Generar leads calificados para sus proyectos\n- Mejorar la conversión de contacto a visita y de visita a venta\n- Medir el retorno de cada peso invertido con reportes claros"],
            ['title' => 'Alcance de los servicios', 'body' => 'A continuación se detallan los servicios incluidos, sus entregables y la modalidad de cobro de cada uno.'],
            ['title' => 'Plan de trabajo y plazos', 'body' => "- **Semana 1:** kick-off, accesos y diagnóstico\n- **Semana 2:** configuración y puesta en marcha\n- **Desde la semana 3:** optimización continua y reporte mensual"],
            ['title' => 'Condiciones comerciales', 'body' => "- Valores netos en pesos chilenos; se agrega IVA\n- Los servicios mensuales se facturan por mes anticipado\n- Los servicios de pago único se facturan 50% al aceptar y 50% al entregar\n- La inversión publicitaria en plataformas (Meta, Google, etc.) no está incluida y la paga directamente el cliente"],
            ['title' => 'Aceptación', 'body' => 'Para aceptar esta propuesta, ingrese al enlace recibido y presione «Aceptar propuesta», o responda este correo indicando su conformidad.'],
        ];
    }

    /** Destinatario sugerido desde el cliente (prioridad) y el lead. @return array<string, mixed> */
    public static function recipientFor(?Client $client, ?Lead $lead): array
    {
        $company = $client?->name ?: $lead?->company;

        return [
            'company' => $company,
            'legal_name' => $client?->legal_name,
            'rut' => $client?->tax_id,
            'activity' => $client?->activity,
            'address' => collect([$client?->address, $client?->commune, $client?->city])->filter()->implode(', ') ?: null,
            'contact_name' => $client?->contact_name ?: $lead?->full_name,
            'contact_role' => $client?->contact_role ?: $lead?->job_title,
            'email' => $client?->email ?: $lead?->email,
            'phone' => $client?->phone ?: $lead?->phone,
        ];
    }

    /** Propuesta en memoria (sin guardar) a partir de la carga del formulario: sirve para la vista previa. @param array<string, mixed> $data */
    public function fromPayload(array $data, ?Proposal $existing = null): Proposal
    {
        $p = $existing ?? new Proposal(['status' => 'draft']);
        $items = collect($data['items'] ?? [])->values()->map(fn ($i, $n) => new ProposalItem([
            'service_id' => $i['service_id'] ?? null,
            'name' => $i['name'],
            'description' => $i['description'] ?? null,
            'deliverables' => array_values(array_filter($i['deliverables'] ?? [])),
            'billing' => $i['billing'] ?? 'one_time',
            'unit' => $i['unit'] ?? 'servicio',
            'quantity' => $i['quantity'] ?? 1,
            'unit_price' => (int) ($i['unit_price'] ?? 0),
            'discount_pct' => (int) ($i['discount_pct'] ?? 0),
            'sort_order' => $n,
        ]));

        $recipient = collect($data['recipient'] ?? [])->map(fn ($v) => is_string($v) ? trim($v) : $v)->filter(fn ($v) => $v !== '' && $v !== null)->all();
        if (! empty($recipient['rut'])) {
            $recipient['rut'] = Rut::format($recipient['rut']);
        }

        $p->fill([
            'title' => $data['title'] ?? 'Propuesta comercial',
            'client_id' => $data['client_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'recipient' => $recipient,
            'sections' => collect($data['sections'] ?? [])->map(fn ($s) => ['title' => trim((string) ($s['title'] ?? '')), 'body' => trim((string) ($s['body'] ?? ''))])->filter(fn ($s) => $s['title'] !== '' || $s['body'] !== '')->values()->all(),
            'valid_until' => $data['valid_until'] ?? null,
            'contract_months' => $data['contract_months'] ?? null,
            'discount_type' => $data['discount_type'] ?? 'percent',
            'discount_value' => (int) ($data['discount_value'] ?? 0),
            'tax_rate' => (int) ($data['tax_rate'] ?? 19),
            'internal_notes' => $data['internal_notes'] ?? null,
        ]);
        if (! empty($data['user_id'])) {
            $p->user_id = $data['user_id'];
        }

        $p->setRelation('items', $items);
        $this->recalculate($p);

        return $p;
    }

    public function recalculate(Proposal $p): Proposal
    {
        $t = ProposalCalculator::totals(
            $p->items->map(fn (ProposalItem $i) => $i->only(['billing', 'quantity', 'unit_price', 'discount_pct']))->all(),
            (string) $p->discount_type,
            (int) $p->discount_value,
            $p->contract_months,
            (int) $p->tax_rate,
        );
        $p->forceFill([
            'total_one_time' => $t['total_one_time'], 'total_monthly' => $t['total_monthly'],
            'total_net' => $t['total_net'], 'total_tax' => $t['total_tax'], 'total_gross' => $t['total_gross'],
        ]);

        return $p;
    }

    /** @param array<string, mixed> $data */
    public function save(array $data, ?Proposal $existing = null, ?int $userId = null): Proposal
    {
        return DB::transaction(function () use ($data, $existing, $userId) {
            $p = $this->fromPayload($data, $existing);
            $items = $p->items;
            if (! $p->exists) {
                $p->user_id ??= $userId;
            }
            $p->save();

            $p->items()->delete();
            $p->items()->saveMany($items->all());
            $p->setRelation('items', $p->items()->get());

            return $p;
        });
    }

    /** Copia una propuesta como borrador nuevo (nueva versión) para el mismo cliente/lead. */
    public function duplicate(Proposal $source, ?int $userId): Proposal
    {
        $data = [
            'title' => preg_replace('/ \(v\d+\)$/', '', $source->title).' (v'.(1 + Proposal::where('lead_id', $source->lead_id)->where('client_id', $source->client_id)->count()).')',
            'client_id' => $source->client_id,
            'lead_id' => $source->lead_id,
            'recipient' => $source->recipient,
            'sections' => $source->sections,
            'valid_until' => now()->addDays(15)->toDateString(),
            'contract_months' => $source->contract_months,
            'discount_type' => $source->discount_type,
            'discount_value' => $source->discount_value,
            'tax_rate' => $source->tax_rate,
            'items' => $source->items->map(fn ($i) => $i->only(['service_id', 'name', 'description', 'deliverables', 'billing', 'unit', 'quantity', 'unit_price', 'discount_pct']))->all(),
        ];

        return $this->save($data, null, $userId);
    }
}
