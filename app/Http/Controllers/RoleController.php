<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('roles/Index', [
            'roles' => Role::withCount('users')->orderBy('name')->get()->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'users_count' => $role->users_count,
                'permissions_count' => count($role->effectivePermissions()),
            ]),
            'totalPermissions' => count(Role::allPermissionKeys()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('roles/Form', [
            'role' => null,
            'groups' => $this->groups(),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->input('name'),
            'slug' => $this->uniqueSlug($request->input('name')),
            'description' => $request->input('description'),
            'permissions' => $this->normalize($request->input('permissions', [])),
        ]);

        $this->toast("Rol «{$role->name}» creado.");

        return to_route('roles.index');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('roles/Form', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'permissions' => $role->effectivePermissions(),
            ],
            'groups' => $this->groups(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->is_system) {
            $this->toast('Los roles de sistema no se pueden modificar.', 'error');

            return back();
        }

        $role->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'permissions' => $this->normalize($request->input('permissions', [])),
        ]);

        $this->toast("Rol «{$role->name}» actualizado.");

        return to_route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            $this->toast('Los roles de sistema no se pueden eliminar.', 'error');

            return back();
        }

        if ($role->users()->exists()) {
            $this->toast('El rol tiene usuarios asignados. Reasígnalos antes de eliminarlo.', 'error');

            return back();
        }

        $role->delete();

        $this->toast('Rol eliminado.');

        return to_route('roles.index');
    }

    /** @return array<int, array<string, mixed>> */
    private function groups(): array
    {
        return collect(config('permissions.groups'))->map(fn (array $group, string $key) => [
            'key' => $key,
            'label' => $group['label'],
            'icon' => $group['icon'],
            'permissions' => collect($group['permissions'])
                ->map(fn (string $label, string $permission) => ['key' => $permission, 'label' => $label])
                ->values(),
        ])->values()->all();
    }

    /** @param array<int, string> $permissions */
    private function normalize(array $permissions): array
    {
        // Ver todos los leads incluye poder ver leads.
        if (in_array('leads.view_all', $permissions, true)) {
            $permissions[] = 'leads.view';
        }

        return array_values(array_unique($permissions));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'rol';
        $slug = $base;
        $i = 2;

        while (Role::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
