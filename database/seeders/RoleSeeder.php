<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /** Crea los roles base sin pisar cambios hechos desde el CRM. */
    public function run(): void
    {
        foreach (config('permissions.default_roles') as $slug => $data) {
            Role::firstOrCreate(['slug' => $slug], [
                'name' => $data['name'],
                'description' => $data['description'],
                'is_system' => $data['is_system'],
                'permissions' => $data['permissions'] === ['*'] ? Role::allPermissionKeys() : $data['permissions'],
            ]);
        }
    }
}
