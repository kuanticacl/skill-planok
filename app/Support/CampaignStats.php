<?php

namespace App\Support;

use App\Models\EmailMessage;
use Illuminate\Support\Collection;

class CampaignStats
{
    /**
     * Métricas agregadas por boletín.
     *
     * @param  array<int, int>  $campaignIds
     * @return Collection<int, array<string, int|float>>
     */
    public static function forCampaigns(array $campaignIds): Collection
    {
        if (! $campaignIds) {
            return collect();
        }

        return EmailMessage::query()
            ->whereIn('campaign_id', $campaignIds)
            ->selectRaw("campaign_id,
                count(*) as total,
                sum(case when status in ('sent','delivered','bounced','complained') then 1 else 0 end) as sent,
                sum(case when status = 'delivered' then 1 else 0 end) as delivered,
                sum(case when first_opened_at is not null then 1 else 0 end) as opened,
                sum(case when first_clicked_at is not null then 1 else 0 end) as clicked,
                sum(case when status = 'bounced' then 1 else 0 end) as bounced,
                sum(case when status = 'complained' then 1 else 0 end) as complained,
                sum(case when unsubscribed_at is not null then 1 else 0 end) as unsubscribed,
                sum(case when status = 'failed' then 1 else 0 end) as failed,
                sum(case when status = 'queued' or status = 'sending' then 1 else 0 end) as pending")
            ->groupBy('campaign_id')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->campaign_id => self::shape($r->toArray())]);
    }

    /** @param array<string, mixed> $r @return array<string, int|float> */
    public static function shape(array $r): array
    {
        $r = array_map(fn ($v) => (int) $v, $r);
        $base = max(1, $r['delivered'] ?: $r['sent']);

        return [
            ...$r,
            'open_rate' => $r['sent'] ? round($r['opened'] / $base * 100, 1) : 0,
            'click_rate' => $r['sent'] ? round($r['clicked'] / $base * 100, 1) : 0,
            'bounce_rate' => $r['sent'] ? round($r['bounced'] / max(1, $r['sent']) * 100, 1) : 0,
        ];
    }

    /** @return array<string, int|float> */
    public static function empty(): array
    {
        return self::shape(['total' => 0, 'sent' => 0, 'delivered' => 0, 'opened' => 0, 'clicked' => 0, 'bounced' => 0, 'complained' => 0, 'unsubscribed' => 0, 'failed' => 0, 'pending' => 0]);
    }
}
