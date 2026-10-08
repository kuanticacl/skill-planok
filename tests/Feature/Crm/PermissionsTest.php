<?php

namespace Tests\Feature\Crm;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    public function test_admin_can_open_administration_screens()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('roles.index'))->assertOk();
        $this->actingAs($admin)->get(route('sources.index'))->assertOk();
    }

    public function test_user_without_permission_gets_403()
    {
        $user = $this->userWithRole('comercial');

        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($user)->get(route('sources.index'))->assertForbidden();
    }

    public function test_permission_granted_through_a_role_is_enforced()
    {
        $user = $this->userWithPermissions(['users.view']);

        $this->actingAs($user)->get(route('users.index'))->assertOk();
        $this->actingAs($user)->get(route('users.create'))->assertForbidden();
    }

    public function test_inactive_users_cannot_log_in()
    {
        $user = $this->userWithRole('admin', ['is_active' => false]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_system_role_cannot_be_modified_or_deleted()
    {
        $admin = $this->userWithRole('admin');
        $role = Role::where('slug', 'admin')->first();

        $this->actingAs($admin)->put(route('roles.update', $role), ['name' => 'Otro', 'permissions' => []]);
        $this->actingAs($admin)->delete(route('roles.destroy', $role));

        $this->assertSame('Administrador', $role->fresh()->name);
        $this->assertNotNull(Role::where('slug', 'admin')->first());
    }

    public function test_role_with_users_cannot_be_deleted()
    {
        $admin = $this->userWithRole('admin');
        $role = Role::where('slug', 'comercial')->first();
        $this->userWithRole('comercial');

        $this->actingAs($admin)->delete(route('roles.destroy', $role));

        $this->assertNotNull($role->fresh());
    }

    public function test_view_all_includes_view()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Supervisor 2',
            'permissions' => ['leads.view_all'],
        ]);

        $this->assertContains('leads.view', Role::where('name', 'Supervisor 2')->first()->permissions);
    }
}
