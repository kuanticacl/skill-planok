<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiFailed;
use App\Services\Ai\AiNotConfigured;
use App\Services\Ai\ProposalAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Endpoints de ayuda de IA para propuestas y servicios (requieren ai.use). */
class AiProposalController extends Controller
{
    public function __construct(private ProposalAssistant $assistant) {}

    public function draft(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'], 'brief' => ['nullable', 'string', 'max:1500'], 'tone' => ['nullable', 'string', 'max:60'],
            ...$this->contextRules(),
        ]);

        return $this->guard(fn () => $this->assistant->draft($data));
    }

    public function improve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => ['nullable', 'string', 'max:6000'], 'title' => ['nullable', 'string', 'max:160'],
            'mode' => ['required', 'in:write,shorter,persuasive,formal,fix,custom'], 'instruction' => ['nullable', 'string', 'max:600'],
            'recipient' => ['nullable', 'array'],
        ]);

        return $this->guard(fn () => ['text' => $this->assistant->improve($data)]);
    }

    public function service(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'category' => ['nullable', 'string', 'max:60'],
            'billing' => ['nullable', 'in:one_time,monthly'], 'hint' => ['nullable', 'string', 'max:1000'],
        ], ['name.required' => 'Primero escribe el nombre del servicio.']);

        return $this->guard(fn () => $this->assistant->service($data));
    }

    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate(['brief' => ['nullable', 'string', 'max:1500'], ...$this->contextRules()]);

        return $this->guard(fn () => ['suggestions' => $this->assistant->suggest($data)]);
    }

    /** @return array<string, mixed> */
    private function contextRules(): array
    {
        return ['lead_id' => ['nullable', 'integer', 'exists:leads,id'], 'recipient' => ['nullable', 'array'], 'items' => ['nullable', 'array', 'max:40'], 'items.*.name' => ['nullable', 'string', 'max:200'], 'items.*.billing' => ['nullable', 'string', 'max:10']];
    }

    private function guard(callable $fn): JsonResponse
    {
        try {
            return response()->json($fn());
        } catch (AiNotConfigured|AiFailed $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
