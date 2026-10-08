<?php

namespace App\Console\Commands;

use App\Services\Email\LandingKit;
use Illuminate\Console\Command;

class LandingKitInstall extends Command
{
    protected $signature = 'crm:landing-kit';

    protected $description = 'Crea (si faltan) los orígenes, audiencia, campos y plantillas para top-inmobiliario.quiebre.cl';

    public function handle(LandingKit $kit): int
    {
        $created = $kit->install();
        $created ? collect($created)->each(fn ($c) => $this->line("+ $c")) : $this->info('Nada que crear: el kit ya existe.');

        return self::SUCCESS;
    }
}
