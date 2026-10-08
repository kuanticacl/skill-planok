<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_users_with_permission_can_visit_the_dashboard()
    {
        $this->seedCrm();
        $this->makeLead();

        $response = $this->actingAs($this->userWithRole('admin'))->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_users_without_permission_cannot_visit_the_dashboard()
    {
        $this->seedCrm();

        $response = $this->actingAs($this->userWithPermissions(['clients.view']))->get(route('dashboard'));

        $response->assertForbidden();
    }
}
