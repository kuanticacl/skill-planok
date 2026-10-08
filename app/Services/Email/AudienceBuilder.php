<?php

namespace App\Services\Email;

use App\Models\Client;
use App\Models\ContactListEntry;
use App\Models\EmailSuppression;
use App\Models\Lead;
use Generator;

/**
 * Convierte la definición de audiencia de un boletín en destinatarios únicos.
 *
 * audience = {
 *   lists:   [ids de listas],
 *   leads:   {enabled, sources[], stages[], assignees[], priorities[], tags[], created_from, created_to},
 *   clients: {enabled, only_active}
 * }
 */
class AudienceBuilder
{
    /**
     * @param  array<string, mixed>  $audience
     * @return Generator<int, array{email: string, name: ?string, vars: array<string, mixed>, lead_id: ?int, client_id: ?int}>
     */
    public function recipients(array $audience, bool $skipSuppressed = true): Generator
    {
        $seen = [];
        $suppressed = $skipSuppressed ? EmailSuppression::pluck('email')->flip()->all() : [];

        $emit = function (string $email, ?string $name, array $vars, ?int $leadId = null, ?int $clientId = null) use (&$seen, $suppressed) {
            $email = strtolower(trim($email));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email]) || isset($suppressed[$email])) {
                return null;
            }
            $seen[$email] = true;

            return ['email' => $email, 'name' => $name, 'vars' => $vars, 'lead_id' => $leadId, 'client_id' => $clientId];
        };

        // 1) Listas manuales
        foreach ($audience['lists'] ?? [] as $listId) {
            foreach (ContactListEntry::where('contact_list_id', $listId)->cursor() as $entry) {
                $name = $entry->name;
                [$first, $last] = array_pad(explode(' ', (string) $name, 2), 2, '');
                $row = $emit($entry->email, $name, [
                    ...($entry->data ?? []),
                    'email' => $entry->email, 'name' => $name, 'first_name' => $first, 'last_name' => $last,
                ]);
                if ($row) {
                    yield $row;
                }
            }
        }

        // 2) Leads
        $leads = $audience['leads'] ?? [];
        if (! empty($leads['enabled'])) {
            $query = Lead::query()->whereNotNull('email')->where('email', '!=', '')->with(['source:id,name', 'stage:id,name'])
                ->when($leads['sources'] ?? null, fn ($q, $v) => $q->whereIn('source_id', $v))
                ->when($leads['stages'] ?? null, fn ($q, $v) => $q->whereIn('stage_id', $v))
                ->when($leads['assignees'] ?? null, fn ($q, $v) => $q->whereIn('assigned_to', $v))
                ->when($leads['priorities'] ?? null, fn ($q, $v) => $q->whereIn('priority', $v))
                ->when($leads['created_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($leads['created_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));

            foreach ($leads['tags'] ?? [] as $tag) {
                $query->where('tags', 'like', '%"'.str_replace(['%', '_', '"'], '', $tag).'"%');
            }

            foreach ($query->orderBy('id')->cursor() as $lead) {
                $row = $emit($lead->email, $lead->full_name, self::leadVariables($lead), $lead->id);
                if ($row) {
                    yield $row;
                }
            }
        }

        // 3) Clientes
        $clients = $audience['clients'] ?? [];
        if (! empty($clients['enabled'])) {
            $query = Client::query()->whereNotNull('email')->where('email', '!=', '')
                ->when($clients['only_active'] ?? true, fn ($q) => $q->where('is_active', true));

            foreach ($query->orderBy('id')->cursor() as $client) {
                $row = $emit($client->email, $client->name, [
                    'name' => $client->name, 'first_name' => $client->name, 'company' => $client->name, 'email' => $client->email,
                    'phone' => $client->phone, 'client' => ['name' => $client->name, 'city' => $client->city],
                ], null, $client->id);
                if ($row) {
                    yield $row;
                }
            }
        }
    }

    /** @param array<string, mixed> $audience */
    public function count(array $audience): int
    {
        $n = 0;
        foreach ($this->recipients($audience) as $_) {
            $n++;
        }

        return $n;
    }

    /** Variables de una persona (lead) disponibles en las plantillas. @return array<string, mixed> */
    public static function leadVariables(Lead $lead): array
    {
        $data = [
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'full_name' => $lead->full_name,
            'name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'job_title' => $lead->job_title,
            'message' => $lead->message,
            'source' => $lead->source?->name,
            'stage' => $lead->stage?->name,
            'utm_source' => $lead->utm_source,
            'utm_medium' => $lead->utm_medium,
            'utm_campaign' => $lead->utm_campaign,
            ...($lead->meta['extra'] ?? []),
            ...($lead->custom ?? []),
        ];

        return [...$data, 'lead' => $data];
    }
}
