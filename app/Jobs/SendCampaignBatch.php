<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\Email\CampaignRunner;
use App\Services\Email\ProviderException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/** Envía un lote de un boletín y se vuelve a encolar mientras queden pendientes (respeta el límite de Resend). */
class SendCampaignBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 0;       // se controla por maxExceptions y releases
    public int $maxExceptions = 5;
    public int $timeout = 300;

    public function __construct(public int $campaignId) {}

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('campaign-'.$this->campaignId))->releaseAfter(10)->expireAfter(300)];
    }

    public function handle(CampaignRunner $runner): void
    {
        $campaign = Campaign::find($this->campaignId);
        if (! $campaign) {
            return;
        }

        try {
            if ($runner->processBatch($campaign)) {
                static::dispatch($this->campaignId);
            }
        } catch (ProviderException $e) {
            if ($e->retryable) {
                $this->release($e->retryAfter ?: 30);

                return;
            }
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Campaign::whereKey($this->campaignId)->where('status', 'sending')->update(['status' => 'failed']);
    }
}
