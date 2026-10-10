<?php

namespace App\Http\Controllers;

use App\Services\Agent\CrmAgent;
use App\Services\Ai\AiFailed;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiNotConfigured;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function status(AiGateway $ai): JsonResponse
    {
        return response()->json(['available' => $ai->isAvailable()]);
    }

    public function chat(Request $request, CrmAgent $agent): JsonResponse
    {
        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:40'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:40000'],
        ]);

        $messages = array_values($data['messages']);
        $last = array_pop($messages);
        abort_unless($last['role'] === 'user', 422, 'El último mensaje debe ser del usuario.');

        set_time_limit(150);

        try {
            return response()->json($agent->chat($request->user(), $messages, $last['content']));
        } catch (AiNotConfigured) {
            return response()->json(['message' => 'Configura un proveedor de IA en Inteligencia artificial → Proveedores para usar el Agent.'], 503);
        } catch (AiFailed $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}
