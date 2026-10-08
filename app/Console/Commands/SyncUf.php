<?php

namespace App\Console\Commands;

use App\Services\UfService;
use Illuminate\Console\Command;

class SyncUf extends Command
{
    protected $signature = 'uf:sync';

    protected $description = 'Descarga el valor de la UF desde findic.cl';

    public function handle(UfService $uf): int
    {
        $n = $uf->sync();
        $today = $uf->today();
        $this->info("{$n} valores sincronizados. UF hoy: ".($today ? number_format($today['value'], 2, ',', '.')." ({$today['date']})" : 'sin datos'));

        return $n > 0 ? self::SUCCESS : self::FAILURE;
    }
}
