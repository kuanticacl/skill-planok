<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\Email\CampaignRunner;
use Illuminate\Console\Command;

class DispatchDueCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch-due';

    protected $description = 'Inicia el envío de los boletines programados cuya hora ya llegó';

    public function handle(CampaignRunner $runner): int
    {
        $due = Campaign::where('status', 'scheduled')->where('scheduled_at', '<=', now())->get();

        foreach ($due as $campaign) {
            $this->info("Enviando «{$campaign->name}»");
            $runner->start($campaign);
        }

        return self::SUCCESS;
    }
}
