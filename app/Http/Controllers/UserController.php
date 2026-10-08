<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Email\UserMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'role', 'status']);

        $users = User::query()
            ->with('role:id,name,slug')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role_id', $role))
            ->when(($filters['status'] ?? null) !== null && ($filters['status'] ?? '') !== '', fn ($q) => $q->where('is_active', $filters['status'] === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => $this->present($user));

        return Inertia::render('users/Index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/Form', [
            'user' => null,
            'roles' => $this->roles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = new User($request->validated());
        $user->email_verified_at = now(); // los usuarios los crea un administrador
        $user->save();

        try {
            app(UserMailer::class)->sendWelcome($user->load('role:id,name'), (string) $request->validated('password'));
            $this->toast("Usuario «{$user->name}» creado. Enviamos un correo de bienvenida a {$user->email}.");
        } catch (\Throwable $e) {
            report($e);
            $this->toast("Usuario «{$user->name}» creado, pero no pudimos enviar el correo de bienvenida.", 'error');
        }

        return to_route('users.index');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('users/Form', [
            'user' => $this->present($user->load('role:id,name,slug')),
            'roles' => $this->roles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($request->user()->is($user)) {
            // Evita quedarse sin acceso por error.
            $data['role_id'] = $user->role_id;
            $data['is_active'] = true;
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        $this->toast("Usuario «{$user->name}» actualizado.");

        return to_route('users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            $this->toast('No puedes eliminar tu propio usuario.', 'error');

            return back();
        }

        $user->delete();

        $this->toast('Usuario eliminado.');

        return to_route('users.index');
    }

    /** @return array<string, mixed> */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'job_title' => $user->job_title,
            'role_id' => $user->role_id,
            'role' => $user->role?->only(['id', 'name', 'slug']),
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    private function roles()
    {
        return Role::orderBy('name')->get(['id', 'name', 'description']);
    }
}
