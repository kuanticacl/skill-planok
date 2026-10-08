<?php

namespace App\Http\Controllers\Proposals;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Catálogo de servicios pre armados con tarifa (se usan al armar propuestas). */
class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('proposals/Services', [
            'services' => Service::orderBy('category')->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (Service $s) => $s->only(['id', 'name', 'category', 'description', 'deliverables', 'billing', 'unit', 'price', 'is_active'])),
            'categories' => Service::distinct()->orderBy('category')->pluck('category'),
            'ai' => ['enabled' => $request->user()->hasPermission('ai.use') && app(\App\Services\Ai\AiGateway::class)->isAvailable()],
            'can' => ['manage' => $request->user()->hasPermission('services.manage')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $service = Service::create([...$this->validated($request), 'sort_order' => (int) Service::max('sort_order') + 1]);
        $this->toast("Servicio «{$service->name}» creado.");

        return back();
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validated($request));
        $this->toast("Servicio «{$service->name}» actualizado.");

        return back();
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete(); // las propuestas ya emitidas conservan su copia del servicio
        $this->toast('Servicio eliminado del catálogo.');

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:3000'],
            'deliverables' => ['nullable', 'array', 'max:20'],
            'deliverables.*' => ['string', 'max:200'],
            'billing' => ['required', 'in:one_time,monthly'],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['required', 'integer', 'min:0', 'max:9999999999'],
            'is_active' => ['boolean'],
        ], ['name.required' => 'Indica el nombre del servicio.', 'price.required' => 'Indica la tarifa.']);

        $data['deliverables'] = array_values(array_filter(array_map('trim', $data['deliverables'] ?? []))) ?: null;

        return $data;
    }
}
