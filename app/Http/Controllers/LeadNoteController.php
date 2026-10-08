<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadNoteController extends Controller
{
    public function store(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('note', $lead);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'is_private' => ['boolean'],
        ]);

        $lead->notes()->create([...$data, 'user_id' => $request->user()->id]);

        $this->toast($request->boolean('is_private') ? 'Nota privada guardada (solo tú la ves).' : 'Nota guardada.');

        return back();
    }

    public function destroy(Request $request, Lead $lead, LeadNote $note): RedirectResponse
    {
        abort_unless($note->lead_id === $lead->id, 404);
        $this->authorize('view', $lead);
        abort_unless(
            $note->user_id === $request->user()->id || $request->user()->hasPermission('leads.delete'),
            403,
        );

        $note->delete();

        $this->toast('Nota eliminada.');

        return back();
    }
}
