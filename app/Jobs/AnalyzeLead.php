<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Setting;
use App\Services\Ai\AiGateway;
use App\Services\Leads\LeadAnalyst;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Analiza un lead con IA en segundo plano (solo si «Analizar leads automáticamente» está activo). */
class AnalyzeLead implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public int $leadId) {}

    public static function dispatchIfEnabled(Lead $lead): void
    {
        try {
            if (Setting::get('ai.auto_analyze', false) && app(AiGateway::class)->isAvailable()) {
                // Sin worker también funciona: se ejecuta al terminar la respuesta HTTP.
                static::dispatchAfterResponse($lead->id);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function handle(LeadAnalyst $analyst): void
    {
        $lead = Lead::find($this->leadId);
        if (! $lead || $analyst->hash($lead) === $lead->ai_input_hash) {
            return; // nada nuevo que analizar
        }

        try {
            $analyst->analyze($lead);
        } catch (Throwable $e) {
            report($e); // el error ya queda en ai_runs; un lead nunca debe fallar por la IA
        }
    }
}
