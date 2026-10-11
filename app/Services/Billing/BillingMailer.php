<?php

namespace App\Services\Billing;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\User;
use App\Services\Email\DefaultTemplates;
use App\Services\Email\EmailService;
use App\Support\Money;
use App\Support\PortalUrl;

/** Correos del portal y la cobranza: bienvenida con accesos, aviso de nueva factura y recordatorios de pago. */
class BillingMailer
{
    public function __construct(private EmailService $emails, private DefaultTemplates $templates) {}

    public function sendWelcome(User $user, string $plainPassword): void
    {
        $this->emails->queueTemplate($this->templates->ensure('bienvenida-portal'), $user->email, $user->name, [
            'first_name' => $this->firstName($user->name),
            'company' => $user->client?->name ?? '',
            'email' => $user->email,
            'password' => $plainPassword,
            'portal_url' => PortalUrl::login(),
            '_redact' => ['password'],
        ], ['client_id' => $user->client_id]);
    }

    /**
     * Destinatarios de los cobros de una empresa: sus accesos activos al portal; si no hay, el correo de la empresa.
     *
     * @return array<int, array{email: string, name: string}>
     */
    public function recipients(Client $client): array
    {
        $users = $client->portalUsers()->where('is_active', true)->get(['name', 'email'])
            ->map(fn (User $u) => ['email' => $u->email, 'name' => $u->name])->all();

        if (! $users && $client->email) {
            $users = [['email' => $client->email, 'name' => $client->contact_name ?: $client->name]];
        }

        return $users;
    }

    /**
     * Envía el cobro («issued»), un recordatorio automático («auto», con días respecto del vencimiento) o uno manual.
     * Devuelve cuántos correos se pusieron en cola.
     */
    public function sendInvoice(Invoice $invoice, string $kind = 'issued', ?int $offset = null): int
    {
        $invoice->loadMissing('client');
        $recipients = $this->recipients($invoice->client);
        $count = 0;

        foreach ($recipients as $r) {
            $vars = $this->variables($invoice, $r['name'], $kind, $offset);
            $slug = $kind === 'issued' ? 'cobro-factura' : 'recordatorio-pago';
            $this->emails->queueTemplate($this->templates->ensure($slug), $r['email'], $r['name'], $vars, ['client_id' => $invoice->client_id]);
            $count++;
        }

        InvoiceReminder::create(['invoice_id' => $invoice->id, 'kind' => $kind, 'offset_days' => $offset, 'recipients' => $count, 'sent_at' => now()]);
        if ($count) {
            $invoice->forceFill(['sent_at' => now()])->save();
        }

        return $count;
    }

    /** @return array<string, mixed> */
    private function variables(Invoice $i, string $name, string $kind, ?int $offset): array
    {
        $number = $i->number ?: '#'.$i->id;
        $days = (int) today()->diffInDays($i->due_date, false); // >0: faltan; <0: vencida hace
        [$headline, $message, $subject] = $this->wording($number, $i->due_date->format('d-m-Y'), $days, $kind);

        return [
            'first_name' => $this->firstName($name),
            'company' => $i->client?->name ?? '',
            'invoice_number' => $number,
            'concept' => $i->concept,
            'total' => Money::format($i->amount_total, $i->currency),
            'due_date' => $i->due_date->format('d-m-Y'),
            'payment_link' => $i->payment_link ?: ($i->service?->payment_link ?? ''),
            'portal_url' => PortalUrl::to('facturas'),
            'headline' => $headline,
            'message' => $message,
            'subject_line' => $subject,
        ];
    }

    /** @return array{0: string, 1: string, 2: string} título, mensaje y asunto */
    private function wording(string $number, string $due, int $days, string $kind): array
    {
        if ($kind === 'issued') {
            return ["Nueva factura {$number}", "ya tienes disponible la factura {$number}, con vencimiento el {$due}.", "Nueva factura {$number} de ECORTESCL"];
        }
        if ($days > 0) {
            $d = $days === 1 ? '1 día' : "{$days} días";

            return ["Tu factura vence en {$d}", "te recordamos que la factura {$number} vence el {$due}.", "Recordatorio: la factura {$number} vence en {$d}"];
        }
        if ($days === 0) {
            return ['Tu factura vence hoy', "te recordamos que la factura {$number} vence hoy ({$due}).", "Recordatorio: la factura {$number} vence hoy"];
        }
        $d = abs($days) === 1 ? '1 día' : abs($days).' días';

        return ["Tu factura está vencida hace {$d}", "la factura {$number} venció el {$due} y aún figura pendiente de pago.", "Factura {$number} vencida: regulariza tu pago"];
    }

    private function firstName(string $name): string
    {
        return explode(' ', trim($name))[0] ?? '';
    }
}
