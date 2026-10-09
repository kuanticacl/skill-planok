<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Eliminación en cascada «suave»: al borrar un origen, cliente o lead se envían
 * a la papelera (soft delete) también los datos que dependen de él.
 */
class CascadeDelete
{
    /** @return array{leads: int, proposals: int} Datos que se irían con el origen. */
    public function sourceImpact(LeadSource $source): array
    {
        $leads = $source->leads();

        return ['leads' => (clone $leads)->count(), 'proposals' => Proposal::whereIn('lead_id', (clone $leads)->select('id'))->count()];
    }

    public function source(LeadSource $source): void
    {
        DB::transaction(function () use ($source) {
            $this->leads($source->leads()->get());

            // Libera el slug y revoca la API key: el origen en papelera ya no recibe leads.
            $source->forceFill([
                'slug' => Str::limit($source->slug, 60, '').'-del-'.$source->id,
                'api_key' => 'del_'.$source->id.'_'.Str::random(30),
                'is_active' => false,
            ])->save();
            $source->delete();
        });
    }

    public function client(Client $client): void
    {
        DB::transaction(function () use ($client) {
            $this->leads($client->leads()->get());
            $client->proposals()->get()->each->delete();
            $client->delete();
        });
    }

    public function lead(Lead $lead): void
    {
        DB::transaction(function () use ($lead) {
            $this->leads(collect([$lead]));
        });
    }

    /** @param  iterable<Lead>  $leads */
    private function leads(iterable $leads): void
    {
        foreach ($leads as $lead) {
            $lead->proposals()->get()->each->delete();
            $lead->delete();
        }
    }
}
