<?php

namespace App\Http\Controllers;

use App\Services\Agent\CrmAgent;
use App\Services\Agent\DocumentReader;
use App\Services\Ai\AiFailed;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiNotConfigured;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AgentController extends Controller
{
    public function status(AiGateway $ai): JsonResponse
    {
        return response()->json([
            'available' => $ai->isAvailable(),
            'transcription' => $ai->transcriptionProvider() !== null,
            'extensions' => DocumentReader::EXTENSIONS,
        ]);
    }

    public function chat(Request $request, CrmAgent $agent): JsonResponse
    {
        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:40'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:40000'],
            'messages.*.attachments' => ['nullable', 'array', 'max:5'],
            'messages.*.attachments.*.name' => ['required', 'string', 'max:120'],
            'messages.*.attachments.*.text' => ['required', 'string', 'max:'.(DocumentReader::MAX_CHARS + 200)],
        ]);

        abort_if(collect($data['messages'])->sum(fn ($m) => collect($m['attachments'] ?? [])->sum(fn ($a) => mb_strlen($a['text']))) > 400000, 422, 'Los archivos adjuntos son demasiado extensos para esta conversación. Empieza una nueva.');

        $messages = array_values($data['messages']);
        $last = array_pop($messages);
        abort_unless($last['role'] === 'user', 422, 'El último mensaje debe ser del usuario.');

        set_time_limit(150);

        try {
            return response()->json($agent->chat($request->user(), $messages, $last['content'], $last['attachments'] ?? []));
        } catch (AiNotConfigured) {
            return response()->json(['message' => 'Configura un proveedor de IA en Inteligencia artificial → Proveedores para usar el Agent.'], 503);
        } catch (AiFailed $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    /** Lee archivos adjuntos y devuelve su texto (no se guardan). */
    public function attachments(Request $request, DocumentReader $reader): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:5'],
            'files.*' => ['file', 'max:10240'],
        ], ['files.*.max' => 'Cada archivo puede pesar hasta 10 MB.']);

        $out = [];
        foreach ($request->file('files') as $file) {
            try {
                $out[] = ['ok' => true, ...$reader->read($file)];
            } catch (RuntimeException $e) {
                $out[] = ['ok' => false, 'name' => mb_substr($file->getClientOriginalName(), 0, 120), 'error' => $e->getMessage()];
            }
        }

        return response()->json(['files' => $out]);
    }

    /** Transcribe una nota de voz grabada en el navegador. */
    public function transcribe(Request $request, AiGateway $ai): JsonResponse
    {
        $request->validate(['audio' => ['required', 'file', 'max:25600']]);
        set_time_limit(120);

        try {
            $text = $ai->transcribe($request->file('audio'));
        } catch (AiNotConfigured) {
            return response()->json(['message' => 'No hay un proveedor de IA con transcripción (OpenAI, Gemini, Groq o Mistral).'], 503);
        } catch (AiFailed $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['text' => $text]);
    }
}
