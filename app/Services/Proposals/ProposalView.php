<?php

namespace App\Services\Proposals;

use App\Models\Proposal;
use App\Support\Agency;
use App\Support\ProposalText;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Datos ya calculados para dibujar una propuesta (documento web y PDF usan lo mismo). */
class ProposalView
{
    /** @return array<string, mixed> */
    public static function data(Proposal $p): array
    {
        $p->loadMissing(['items', 'owner:id,name,email']);
        $r = $p->recipient ?? [];
        $months = max(1, (int) ($p->contract_months ?: 1));
        $once = $p->items->where('billing', 'one_time')->values();
        $monthly = $p->items->where('billing', 'monthly')->values();
        $subOnce = $once->sum(fn ($i) => $i->lineTotal());
        $subMonthly = $monthly->sum(fn ($i) => $i->lineTotal());
        $date = fn ($d) => $d ? Carbon::parse($d)->locale('es')->translatedFormat('d \d\e F \d\e Y') : null;

        return [
            'p' => $p,
            'r' => $r,
            'status' => $p->effectiveStatus(),
            'company' => $r['company'] ?? $r['legal_name'] ?? 'Cliente',
            'months' => $months,
            'once' => $once,
            'monthly' => $monthly,
            'subOnce' => $subOnce,
            'subMonthly' => $subMonthly,
            'discount' => ($subOnce + $subMonthly * $months) - ($p->total_one_time + $p->total_monthly * $months),
            'sections' => collect($p->sections ?? [])->map(fn ($s) => ['title' => $s['title'], 'body' => ProposalText::html(ProposalText::fill($s['body'], $r))])->values(),
            'servicesAt' => collect($p->sections ?? [])->search(fn ($s) => preg_match('/alcance|servicio/i', $s['title'])),
            'issued' => $date($p->issued_at ?? now()),
            'valid' => $date($p->valid_until),
            'agency' => Agency::profile(),
            'socials' => Agency::socials(),
            'tech' => Agency::tech(),
            'holding' => Agency::holding(),
            'owner' => $p->owner,
            'fileName' => 'Propuesta-'.$p->number.'-'.Str::slug($r['company'] ?? $r['legal_name'] ?? 'cliente').'.pdf',
        ];
    }

    public static function money(int|float|null $n): string
    {
        return '$'.number_format((int) $n, 0, ',', '.');
    }
}
