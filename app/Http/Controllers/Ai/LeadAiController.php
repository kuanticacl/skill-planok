<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\Ai\AiFailed;
use App\Services\Ai\AiNotConfigured;
use App\Services\LeadService;
use App\Services\Leads\LeadAnalyst;
use App\Services\Leads\LeadScorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Puntaje, perfilamiento y análisis con IA de un lead individual. */
class LeadAiController extends Controller
{
    /** Recalcula el puntaje (sin IA). */
    public function rescore(Lead $lead, LeadScorer $scorer): JsonResponse
    {
        $this->authorize('view', $lead);
        $scorer->score($lead);

        return response()->json(['message' => 'Puntaje actualizado.']);
    }

    /** Análisis con IA: resumen, próximos pasos, mensaje sugerido y datos por completar. */
    public function analyze(Lead $lead, LeadAnalyst $analyst): JsonResponse
    {
        $this->authorize('view', $lead);

        try {
            $analyst->analyze($lead);
        } catch (AiNotConfigured|AiFailed $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Análisis actualizado.']);
    }

    /** Aplica una sugerencia de perfil que la persona aceptó (prioridad, etiquetas, empresa o cargo). */
    public function apply(Request $request, Lead $lead, LeadService $leads): JsonResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validate([
            'field' => ['required', Rule::in(LeadAnalyst::APPLICABLE)],
            'value' => ['required', 'string', 'max:120'],
        ]);

        $attrs = match ($data['field']) {
            'priority' => array_key_exists($data['value'], Lead::PRIORITIES) ? ['priority' => $data['value']] : abort(422, 'Prioridad no válida.'),
            'tags' => ['tags' => collect([...($lead->tags ?? []), ...explode(',', $data['value'])])->map(fn ($t) => mb_substr(trim($t), 0, 30))->filter()->unique()->take(15)->values()->all()],
            default => [$data['field'] => $data['value']],
        };

        $leads->update($lead, $attrs, $request->user());

        // La sugerencia aplicada deja de ofrecerse.
        $analysis = $lead->ai_analysis;
        if (is_array($analysis)) {
            $analysis['profile_suggestions'] = collect($analysis['profile_suggestions'] ?? [])
                ->reject(fn ($s) => $s['field'] === $data['field'] && $s['value'] === $data['value'])->values()->all();
            $lead->forceFill(['ai_analysis' => $analysis])->saveQuietly();
        }

        return response()->json(['message' => 'Perfil actualizado.']);
    }
}
