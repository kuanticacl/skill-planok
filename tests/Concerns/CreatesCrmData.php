<?php

namespace Tests\Concerns;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RoleSeeder;

trait CreatesCrmData
{
    protected function seedCrm(): void
    {
        $this->seed([RoleSeeder::class, CrmSeeder::class]);
    }

    protected function userWithRole(string $slug, array $attributes = []): User
    {
        return User::factory()->create([...$attributes, 'role_id' => Role::where('slug', $slug)->value('id')]);
    }

    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'Rol '.uniqid(), 'slug' => 'rol-'.uniqid(), 'permissions' => $permissions]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function makeLead(array $attributes = []): Lead
    {
        return Lead::create([
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.com',
            'source_id' => LeadSource::first()->id,
            'stage_id' => PipelineStage::initial()->id,
            ...$attributes,
        ]);
    }
}
