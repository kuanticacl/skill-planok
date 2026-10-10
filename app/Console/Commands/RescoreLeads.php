<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\Leads\LeadScorer;
use Illuminate\Console\Command;

class RescoreLeads extends Command
{
    protected $signature = 'leads:rescore {--open : Solo clientes en etapas en curso}';

    protected $description = 'Recalcula el puntaje y el perfil de los clientes (refleja el paso del tiempo y datos nuevos)';

    public function handle(LeadScorer $scorer): int
    {
        $query = Lead::query()->with(['source', 'stage']);
        if ($this->option('open')) {
            $query->whereHas('stage', fn ($q) => $q->where('type', 'open'));
        }

        $n = 0;
        $query->chunkById(200, function ($leads) use ($scorer, &$n) {
            foreach ($leads as $lead) {
                $scorer->score($lead);
                $n++;
            }
        });

        $this->info("{$n} clientes recalculados.");

        return self::SUCCESS;
    }
}
