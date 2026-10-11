<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Setting;

/**
 * Recordatorios automáticos de pago para facturas por pagar de cobros recurrentes.
 * Los días (respecto del vencimiento; negativo = antes) salen del servicio o, si no, del ajuste global.
 * Si el sistema estuvo detenido, solo se envía el recordatorio más reciente que corresponda y los anteriores se marcan como omitidos.
 */
class ReminderRunner
{
    public function __construct(private BillingMailer $mailer) {}

    /** @return list<int> */
    public static function defaultOffsets(): array
    {
        $raw = Setting::get('billing.reminder_offsets');
        $offsets = $raw !== null ? array_map('intval', array_filter(explode(',', (string) $raw), fn ($v) => trim($v) !== '')) : config('portal.reminder_offsets');

        return self::clean($offsets);
    }

    /** @param  array<int, mixed>  $offsets @return list<int> */
    public static function clean(array $offsets): array
    {
        $offsets = array_values(array_unique(array_map('intval', $offsets)));
        sort($offsets);

        return array_values(array_filter($offsets, fn ($o) => $o >= -60 && $o <= 90));
    }

    /** @return int recordatorios enviados */
    public function run(): int
    {
        $sent = 0;
        $default = self::defaultOffsets();

        Invoice::query()->with(['service', 'client'])
            ->where('status', 'issued')->where('auto_remind', true)
            ->each(function (Invoice $i) use ($default, &$sent) {
                $offsets = $i->service?->reminder_offsets !== null ? self::clean($i->service->reminder_offsets) : $default;
                $done = $i->reminders()->where('kind', 'auto')->pluck('offset_days')->all();

                $due = collect($offsets)->reject(fn ($o) => in_array($o, $done, true))
                    ->filter(fn ($o) => $i->due_date->copy()->addDays($o)->lte(today()))->values();
                if ($due->isEmpty()) {
                    return;
                }

                $latest = $due->last();
                foreach ($due->slice(0, -1) as $o) {
                    $i->reminders()->create(['kind' => 'auto', 'offset_days' => $o, 'skipped' => true, 'sent_at' => now()]);
                }
                $this->mailer->sendInvoice($i, 'auto', $latest);
                // El registro de «auto» con su offset lo crea sendInvoice con kind=auto.
                $sent++;
            });

        return $sent;
    }
}
