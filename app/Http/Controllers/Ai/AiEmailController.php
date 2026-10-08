<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiFailed;
use App\Services\Ai\AiNotConfigured;
use App\Services\Ai\EmailAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Endpoints del asistente de IA para mailings (opcional; requiere ai.use y templates.manage). */
class AiEmailController extends Controller
{
    public function design(Request $request, EmailAssistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'brief' => ['required', 'string', 'min:10', 'max:1500'],
            'kind' => ['nullable', 'in:marketing,transactional'],
            'tone' => ['nullable', 'string', 'max:60'],
            'length' => ['nullable', 'in:short,medium,long'],
            'cta_label' => ['nullable', 'string', 'max:40'],
            'cta_url' => ['nullable', 'url:https', 'max:500'],
            'images' => ['nullable', 'array', 'max:6'],
            'images.*' => ['url:https', 'max:500'],
        ], [
            'brief.required' => 'Cuéntale a la IA de qué trata el correo.',
            'brief.min' => 'Describe un poco más el objetivo del correo (mínimo 10 caracteres).',
            'cta_url.url' => 'El enlace del botón debe ser una URL https válida.',
            'images.*.url' => 'Las imágenes deben ser URLs https válidas.',
        ]);

        return $this->guard(fn () => $assistant->design($data));
    }

    public function subjects(Request $request, EmailAssistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:200'],
            'context' => ['required', 'string', 'max:6000'],
        ]);

        return $this->guard(fn () => ['subjects' => $assistant->subjects((string) ($data['subject'] ?? ''), $data['context'])]);
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
