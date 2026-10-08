<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\Leads\LeadScorer;
use App\Support\ProposalText;
use App\Models\LeadNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadNoteController extends Controller
{
    public function store(Request $request, Lead $lead): JsonResponse|RedirectResponse
    {
        $this->authorize('note', $lead);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'is_private' => ['boolean'],
        ]);

        $data['body'] = ProposalText::sanitize($data['body']) ?: e(strip_tags($data['body']));
        $lead->notes()->create([...$data, 'user_id' => $request->user()->id]);
        LeadScorer::refresh($lead);

        return $this->respond($request, [], $request->boolean('is_private') ? 'Nota privada guardada (solo tú la ves).' : 'Nota guardada.');
    }

    public function destroy(Request $request, Lead $lead, LeadNote $note): JsonResponse|RedirectResponse
    {
        abort_unless($note->lead_id === $lead->id, 404);
        $this->authorize('view', $lead);
        abort_unless(
            $note->user_id === $request->user()->id || $request->user()->hasPermission('leads.delete'),
            403,
        );

        $note->delete();
        LeadScorer::refresh($lead);

        return $this->respond($request, [], 'Nota eliminada.');
    }
}
