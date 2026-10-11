<?php

namespace Tests\Feature\Agent;

use App\Models\Client;
use App\Models\ClientService;
use App\Services\Agent\AgentActions;
use App\Services\Agent\PendingActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class AgentActionsTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    public function test_an_edit_is_only_proposed_until_the_user_confirms(): void
    {
        $admin = $this->userWithRole('admin');
        $client = Client::create(['name' => 'Oritec SpA', 'is_active' => true]);

        $proposal = (new AgentActions($admin))->propose('update', 'client', $client->id, ['phone' => '+56 9 1111 2222']);

        $this->assertSame('Teléfono: (vacío) → «+56 9 1111 2222»', $proposal['lines'][0]);
        $this->assertNull($client->fresh()->phone); // nada cambió todavía

        $id = PendingActions::store($admin->id, $proposal);
        $this->actingAs($admin)->postJson("/agent/actions/{$id}/confirm")->assertOk()->assertJsonPath('ok', true);
        $this->assertSame('+56 9 1111 2222', $client->fresh()->phone);

        $this->actingAs($admin)->postJson("/agent/actions/{$id}/confirm")->assertStatus(410); // un solo uso
    }

    public function test_a_pending_action_cannot_be_confirmed_by_someone_else_nor_cancelled_ones_applied(): void
    {
        $admin = $this->userWithRole('admin');
        $other = $this->userWithRole('supervisor');
        $client = Client::create(['name' => 'X', 'is_active' => true]);
        $id = PendingActions::store($admin->id, (new AgentActions($admin))->propose('delete', 'client', $client->id));

        $this->actingAs($other)->postJson("/agent/actions/{$id}/confirm")->assertStatus(410);
        $this->actingAs($admin)->postJson("/agent/actions/{$id}/cancel")->assertOk();
        $this->actingAs($admin)->postJson("/agent/actions/{$id}/confirm")->assertStatus(410);
        $this->assertNotNull($client->fresh());
    }

    public function test_permissions_and_invalid_data_are_rejected_when_proposing(): void
    {
        $reader = $this->userWithRole('lectura');
        $admin = $this->userWithRole('admin');
        $client = Client::create(['name' => 'X', 'is_active' => true]);

        try {
            (new AgentActions($reader))->propose('delete', 'client', $client->id);
            $this->fail('Debió rechazar por permisos');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('permiso', $e->errors());
        }

        $this->expectException(ValidationException::class);
        (new AgentActions($admin))->propose('update', 'client', $client->id, ['tax_id' => '11.111.111-9']); // dígito verificador inválido
    }

    public function test_services_with_issued_invoices_are_not_deletable_by_the_agent(): void
    {
        $admin = $this->userWithRole('admin');
        $client = Client::create(['name' => 'X', 'is_active' => true]);
        $service = ClientService::create(['client_id' => $client->id, 'name' => 'S', 'billing_cycle' => 'one_time', 'currency' => 'CLP', 'price' => 1, 'start_date' => today(), 'status' => 'active']);
        $parent = ClientService::create(['client_id' => $client->id, 'name' => 'P', 'billing_cycle' => 'one_time', 'currency' => 'CLP', 'price' => 1, 'start_date' => today(), 'status' => 'active']);
        $service->update(['parent_id' => $parent->id]);

        $this->expectException(ValidationException::class);
        (new AgentActions($admin))->propose('delete', 'contract', $parent->id); // tiene un servicio asociado
    }

    public function test_preview_shows_the_portal_read_only_to_staff_with_permission(): void
    {
        $admin = $this->userWithRole('admin');
        $client = Client::create(['name' => 'Oritec SpA', 'is_active' => true]);

        $this->actingAs($admin)->get('/portal')->assertRedirect('/dashboard'); // sin vista previa, el equipo no entra
        $this->actingAs($admin)->post("/clients/{$client->id}/portal-preview")->assertRedirect('/portal');
        $this->actingAs($admin)->get('/portal')->assertOk();
        $this->actingAs($admin)->putJson('/portal/cuenta', ['name' => 'Hack'])->assertForbidden();
        $this->assertNotSame('Hack', $admin->fresh()->name);

        $this->actingAs($admin)->post('/portal-preview/exit')->assertRedirect("/clients/{$client->id}");
        $this->actingAs($admin)->get('/portal')->assertRedirect('/dashboard');
    }
}
