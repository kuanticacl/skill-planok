<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\ContactList;
use App\Models\ContactListEntry;
use App\Models\EmailSuppression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactListController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('email/lists/Index', [
            'lists' => ContactList::withCount('entries')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:255']]);
        $list = ContactList::create([...$data, 'created_by' => $request->user()->id]);

        $this->toast("Lista «{$list->name}» creada.");

        return to_route('lists.show', $list);
    }

    public function update(Request $request, ContactList $list): RedirectResponse
    {
        $list->update($request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:255']]));

        $this->toast('Lista actualizada.');

        return back();
    }

    public function destroy(ContactList $list): RedirectResponse
    {
        $list->delete();

        $this->toast('Lista eliminada.');

        return to_route('lists.index');
    }

    public function show(Request $request, ContactList $list): Response
    {
        $q = $request->query('q');
        $suppressed = EmailSuppression::pluck('email')->flip();

        return Inertia::render('email/lists/Show', [
            'list' => $list->loadCount('entries'),
            'entries' => $list->entries()
                ->when($q, fn ($query, $v) => $query->where(fn ($w) => $w->where('email', 'like', "%{$v}%")->orWhere('name', 'like', "%{$v}%")))
                ->orderByDesc('id')->paginate(25)->withQueryString()
                ->through(fn (ContactListEntry $e) => [...$e->only(['id', 'email', 'name', 'data']), 'suppressed' => $suppressed->has($e->email)]),
            'filters' => ['q' => $q],
        ]);
    }

    /**
     * Importa contactos pegados desde Excel/CSV: "correo, nombre, empresa…".
     * Si la primera fila trae encabezados (correo/email, nombre/name…), las demás columnas pasan a ser variables.
     */
    public function import(Request $request, ContactList $list): JsonResponse
    {
        $data = $request->validate(['text' => ['required', 'string', 'max:2000000']]);

        $lines = preg_split('/\r\n|\r|\n/', trim($data['text']));
        $delimiter = str_contains($lines[0] ?? '', "\t") ? "\t" : (substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',');

        $header = null;
        $first = str_getcsv($lines[0] ?? '', $delimiter);
        if ($first && ! collect($first)->contains(fn ($c) => filter_var(trim($c), FILTER_VALIDATE_EMAIL))) {
            $header = array_map(fn ($h) => $this->slug($h), $first);
            array_shift($lines);
        }

        $stats = ['added' => 0, 'updated' => 0, 'invalid' => 0];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line, $delimiter);
            $row = $header ? array_combine($header, array_pad($cols, count($header), '')) ?: [] : ['email' => $cols[0] ?? '', 'nombre' => $cols[1] ?? ''];

            $email = strtolower(trim((string) ($row['email'] ?? $row['correo'] ?? ($header ? '' : ($cols[0] ?? '')))));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stats['invalid']++;

                continue;
            }

            $name = trim((string) ($row['nombre'] ?? $row['name'] ?? ''));
            $extra = collect($row)->except(['email', 'correo', 'nombre', 'name'])->filter(fn ($v) => trim((string) $v) !== '')->map(fn ($v) => trim((string) $v))->all();

            $entry = ContactListEntry::updateOrCreate(
                ['contact_list_id' => $list->id, 'email' => $email],
                ['name' => $name ?: null, 'data' => $extra ?: null],
            );
            $entry->wasRecentlyCreated ? $stats['added']++ : $stats['updated']++;
        }

        return response()->json($stats);
    }

    public function destroyEntry(ContactList $list, ContactListEntry $entry): RedirectResponse
    {
        abort_unless($entry->contact_list_id === $list->id, 404);
        $entry->delete();

        $this->toast('Contacto eliminado de la lista.');

        return back();
    }

    private function slug(string $v): string
    {
        return trim(preg_replace('/[^a-z0-9_]+/', '_', strtolower(\Illuminate\Support\Str::ascii(trim($v)))), '_');
    }
}
