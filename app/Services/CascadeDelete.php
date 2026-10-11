<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\User;
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

    /** Con $target, los leads (también los de la papelera) pasan a otro origen en vez de eliminarse. */
    public function source(LeadSource $source, ?LeadSource $target = null): void
    {
        DB::transaction(function () use ($source, $target) {
            if ($target) {
                Lead::withTrashed()->where('source_id', $source->id)->update(['source_id' => $target->id]);
            } else {
                $this->leads($source->leads()->get());
            }

            // Libera el slug y revoca la API key: el origen en papelera ya no recibe leads.
            $source->forceFill([
                'slug' => Str::limit($source->slug, 60, '').'-del-'.$source->id,
                'api_key' => 'del_'.$source->id.'_'.Str::random(30),
                'is_active' => false,
            ])->save();
            $source->delete();
        });
    }

    /** Con $target, los leads y propuestas del cliente pasan a otro cliente en vez de eliminarse. */
    public function client(Client $client, ?Client $target = null): void
    {
        DB::transaction(function () use ($client, $target) {
            if ($target) {
                Lead::withTrashed()->where('client_id', $client->id)->update(['client_id' => $target->id]);
                Proposal::withTrashed()->where('client_id', $client->id)->update(['client_id' => $target->id]);
                ClientService::withTrashed()->where('client_id', $client->id)->update(['client_id' => $target->id]);
                Invoice::withTrashed()->where('client_id', $client->id)->update(['client_id' => $target->id]);
                User::where('client_id', $client->id)->update(['client_id' => $target->id]);
            } else {
                $this->leads($client->leads()->get());
                $client->proposals()->get()->each->delete();
                $client->invoices()->get()->each->delete();
                $client->services()->get()->each->delete();
                // Los accesos al portal quedan desactivados (no se borran: se pueden reactivar al restaurar la empresa).
                User::where('client_id', $client->id)->update(['is_active' => false]);
            }
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
