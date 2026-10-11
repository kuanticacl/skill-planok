<?php

namespace App\Services\Billing;

use App\Models\ClientService;
use App\Models\Invoice;
use Carbon\CarbonInterface;

/** Genera los cobros programados de los servicios recurrentes (y extiende la vigencia de los que se renuevan solos). */
class ChargeGenerator
{
    public function __construct(private InvoiceService $invoices) {}

    /** @return int cobros creados */
    public function run(): int
    {
        $horizon = today()->addDays((int) config('portal.charge_ahead_days'));
        $created = 0;

        ClientService::query()
            ->where('status', 'active')
            ->where('billing_cycle', '!=', 'one_time')
            ->whereNotNull('next_charge_on')
            ->whereDate('next_charge_on', '<=', $horizon)
            ->orderBy('id')
            ->each(function (ClientService $s) use ($horizon, &$created) {
                $guard = 0;
                while ($s->next_charge_on && $s->next_charge_on->lte($horizon) && $guard++ < 24) {
                    $due = $s->next_charge_on->copy();

                    // Sin renovación automática no se cobra más allá del término del servicio.
                    if ($s->end_date && $due->gt($s->end_date)) {
                        if (! $s->auto_renew) {
                            $s->next_charge_on = null;
                            break;
                        }
                        $s->end_date = $s->addPeriod($s->end_date->copy())->subDay()->max($due);
                    }

                    if ($this->charge($s, $due)) {
                        $created++;
                    }
                    $s->next_charge_on = $s->addPeriod($due);
                }
                $s->save();
            });

        // Servicios cuyo término pasó y no se renuevan.
        ClientService::where('status', 'active')->where('auto_renew', false)->whereNotNull('end_date')->whereDate('end_date', '<', today())
            ->where(fn ($q) => $q->where('billing_cycle', 'one_time')->orWhereNull('next_charge_on'))
            ->update(['status' => 'ended']);

        return $created;
    }

    private function charge(ClientService $s, CarbonInterface $due): bool
    {
        if (Invoice::withTrashed()->where('client_service_id', $s->id)->whereDate('due_date', $due)->exists()) {
            return false;
        }

        $end = $s->addPeriod($due)->subDay();
        $this->invoices->create([
            'client_id' => $s->client_id,
            'client_service_id' => $s->id,
            'concept' => $s->name.' · '.$this->periodLabel($s, $due),
            'period_start' => $due,
            'period_end' => $end,
            'due_date' => $due,
            'currency' => $s->currency,
            'amount_net' => $s->price,
            'auto_remind' => true,
            'payment_link' => $s->payment_link,
        ]);

        return true;
    }

    private function periodLabel(ClientService $s, CarbonInterface $due): string
    {
        $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        return match ($s->billing_cycle) {
            'yearly' => 'año '.$due->year,
            'quarterly' => 'trimestre desde '.$months[$due->month - 1].' '.$due->year,
            default => $months[$due->month - 1].' '.$due->year,
        };
    }
}
