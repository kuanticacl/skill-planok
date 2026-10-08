<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\Email\CampaignRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Expande la audiencia y arranca el envío (puede tardar con audiencias grandes, por eso va en cola). */
class StartCampaign implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $campaignId) {}

    public function handle(CampaignRunner $runner): void
    {
        $campaign = Campaign::find($this->campaignId);

        if ($campaign) {
            $runner->start($campaign);
        }
    }

    public function failed(\Throwable $e): void
    {
        Campaign::whereKey($this->campaignId)->whereIn('status', ['draft', 'scheduled', 'sending'])->update(['status' => 'failed']);
    }
}
