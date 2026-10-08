<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\PipelineStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('crm/Stages', [
            'stages' => PipelineStage::withCount('leads')
                ->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (PipelineStage $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'color' => $s->color,
                    'type' => $s->type,
                    'leads_count' => $s->leads_count,
                ]),
            'types' => PipelineStage::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $stage = PipelineStage::create([
            ...$this->validated($request),
            'sort_order' => (int) PipelineStage::max('sort_order') + 1,
        ]);

        $this->toast("Etapa «{$stage->name}» creada.");

        return back();
    }

    public function update(Request $request, PipelineStage $stage): RedirectResponse
    {
        $stage->update($this->validated($request, $stage));

        $this->toast("Etapa «{$stage->name}» actualizada.");

        return back();
    }

    /** Recibe los ids en el nuevo orden. */
    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:pipeline_stages,id'],
        ])['ids'];

        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $position => $id) {
                PipelineStage::whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return back();
    }

    public function destroy(Request $request, PipelineStage $stage): RedirectResponse
    {
        if (PipelineStage::count() <= 1) {
            $this->toast('Debe existir al menos una etapa.', 'error');

            return back();
        }

        $count = $stage->leads()->withTrashed()->count();

        if ($count > 0) {
            $target = $request->validate([
                'move_to' => ['required', 'integer', Rule::exists('pipeline_stages', 'id'), Rule::notIn([$stage->id])],
            ])['move_to'];

            // Los leads de la etapa eliminada pasan al final de la etapa destino.
            $base = (int) Lead::withTrashed()->where('stage_id', $target)->max('position');
            $stage->leads()->withTrashed()->update(['stage_id' => $target, 'position' => $base + 1]);
        }

        $stage->delete();

        $this->toast('Etapa eliminada.');

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?PipelineStage $stage = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('pipeline_stages', 'name')->ignore($stage?->id)],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'type' => ['required', Rule::in(array_keys(PipelineStage::TYPES))],
        ]);
    }
}
