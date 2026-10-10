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

    public function test_dashboard_is_a_summary_and_the_kanban_board_lives_on_its_own_page(): void
    {
        $this->seedCrm();
        $admin = $this->userWithRole('admin');
        $this->makeLead(['first_name' => 'Ana']);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertInertia(fn ($p) => $p
            ->component('Dashboard')->has('funnel')->has('daily', 14)->has('followUps')->has('recent')->missing('board'));

        $this->actingAs($admin)->get(route('kanban.index'))->assertOk()->assertInertia(fn ($p) => $p
            ->component('Kanban')->has('board')->has('stages')->where('stats.total', 1));

        $this->actingAs($this->userWithRole('comercial'))->get(route('kanban.index'))->assertOk();
        $this->actingAs($this->userWithPermissions(['clients.view']))->get(route('kanban.index'))->assertForbidden();
    }
}
