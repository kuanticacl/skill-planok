<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('email/Api', [
            'keys' => ApiKey::orderByDesc('id')->get()->map(fn (ApiKey $k) => [
                'id' => $k->id,
                'name' => $k->name,
                'prefix' => $k->prefix,
                'abilities' => $k->abilities,
                'is_active' => $k->is_active,
                'last_used_at' => $k->last_used_at?->toIso8601String(),
                'last_used_ip' => $k->last_used_ip,
                'created_at' => $k->created_at?->toIso8601String(),
            ]),
            'abilities' => ApiKey::ABILITIES,
            'endpoint' => url('/api/v1'),
            'templates' => EmailTemplate::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug', 'category', 'variables']),
        ]);
    }

    /** Devuelve la key en claro una única vez (JSON): después solo se guarda su hash. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(array_keys(ApiKey::ABILITIES))],
        ]);

        [$key, $plain] = ApiKey::issue($data['name'], $data['abilities'], $request->user()->id);

        return response()->json(['id' => $key->id, 'key' => $plain]);
    }

    public function update(Request $request, ApiKey $apiKey): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $apiKey->update($data);

        $this->toast($data['is_active'] ? 'API key activada.' : 'API key desactivada.');

        return back();
    }

    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->delete();

        $this->toast('API key eliminada. Las integraciones que la usaban dejarán de funcionar.');

        return back();
    }
}
