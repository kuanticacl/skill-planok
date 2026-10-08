<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailSuppression;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuppressionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'reason']);

        return Inertia::render('email/Suppressions', [
            'suppressions' => EmailSuppression::query()
                ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('email', 'like', "%{$v}%"))
                ->when($filters['reason'] ?? null, fn ($q, $v) => $q->where('reason', $v))
                ->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'reasons' => EmailSuppression::REASONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc'], 'note' => ['nullable', 'string', 'max:255']]);

        EmailSuppression::add($data['email'], 'manual', $data['note'] ?? null);

        $this->toast('Dirección agregada a la lista de exclusión.');

        return back();
    }

    public function destroy(EmailSuppression $suppression): RedirectResponse
    {
        $suppression->delete();

        $this->toast('Dirección reactivada: volverá a recibir correos.');

        return back();
    }
}
