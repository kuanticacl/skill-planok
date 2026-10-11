<?php

namespace App\Console\Commands;

use App\Services\Billing\ChargeGenerator;
use App\Services\Billing\ReminderRunner;
use Illuminate\Console\Command;

class RunBilling extends Command
{
    protected $signature = 'billing:run';

    protected $description = 'Genera los cobros programados de servicios recurrentes y envía los recordatorios de pago pendientes';

    public function handle(ChargeGenerator $charges, ReminderRunner $reminders): int
    {
        $this->info($charges->run().' cobro(s) programado(s) generado(s).');
        $this->info($reminders->run().' recordatorio(s) de pago enviado(s).');

        return self::SUCCESS;
    }
}
