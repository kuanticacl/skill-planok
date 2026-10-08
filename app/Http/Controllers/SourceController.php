<?php

namespace App\Http\Controllers;

use App\Models\LeadSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SourceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('crm/Sources', [
            'sources' => LeadSource::withCount('leads')
                ->orderBy('sort_order')->orderBy('id')
                ->get()
                ->map(fn (LeadSource $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'slug' => $s->slug,
                    'color' => $s->color,
                    'icon' => $s->icon,
                    'is_active' => $s->is_active,
                    'is_system' => $s->is_system,
                    'leads_count' => $s->leads_count,
                    'api_key' => $s->api_key, // visible solo para quienes gestionan orígenes
                ]),
            'endpoint' => url('/api/v1/leads'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $source = LeadSource::create([
            ...$data,
            'slug' => LeadSource::uniqueSlug($data['name']),
            'api_key' => LeadSource::generateKey(),
            'sort_order' => (int) LeadSource::max('sort_order') + 1,
        ]);

        $this->toast("Origen «{$source->name}» creado.");

        return back();
    }

    public function update(Request $request, LeadSource $source): RedirectResponse
    {
        $data = $this->validated($request, $source);

        if ($source->is_system) {
            unset($data['is_active']); // el origen "Manual" siempre debe estar activo
        }

        $source->update($data);

        $this->toast("Origen «{$source->name}» actualizado.");

        return back();
    }

    public function regenerateKey(LeadSource $source): RedirectResponse
    {
        $source->update(['api_key' => LeadSource::generateKey()]);

        $this->toast('Nueva API key generada. La anterior dejó de funcionar.');

        return back();
    }

    public function destroy(LeadSource $source): RedirectResponse
    {
        if ($source->is_system) {
            $this->toast('Este origen es de sistema y no se puede eliminar.', 'error');

            return back();
        }

        if ($source->leads()->withTrashed()->exists()) {
            $this->toast('El origen tiene leads asociados. Desactívalo en lugar de eliminarlo.', 'error');

            return back();
        }

        $source->delete();

        $this->toast('Origen eliminado.');

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?LeadSource $source = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('lead_sources', 'name')->ignore($source?->id)],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/'],
            'is_active' => ['boolean'],
        ]);
    }
}
