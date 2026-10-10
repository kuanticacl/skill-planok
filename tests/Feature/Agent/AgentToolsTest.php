<?php

namespace Tests\Feature\Agent;

use App\Models\Client;
use App\Models\Proposal;
use App\Services\Agent\AgentContext;
use App\Services\Agent\Tools\AddFollowUp;
use App\Services\Agent\Tools\CreateClient;
use App\Services\Agent\Tools\CreateProposal;
use App\Services\Agent\Tools\FindProposals;
use App\Services\Agent\Tools\SearchLeads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class AgentToolsTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    public function test_the_agent_builds_client_and_proposal_with_server_side_totals(): void
    {
        $ctx = new AgentContext($this->userWithRole('admin'));

        $client = json_decode((new CreateClient($ctx))->handle(new Request(['name' => 'Oritec', 'tax_id' => '76405003-7', 'email' => 'c@oritec.cl'])), true);
        $this->assertSame('76.405.003-7', $client['rut']);

        $proposal = json_decode((new CreateProposal($ctx))->handle(new Request([
            'title' => 'Bot de IA', 'client_id' => $client['id'], 'currency' => 'CLP',
            'items' => [['name' => 'Bot', 'billing' => 'one_time', 'unit_price' => 5000000]],
        ])), true);

        $this->assertSame('draft', $proposal['status']);
        $this->assertEquals(5000000, $proposal['total_net']);
        $this->assertEquals(5950000, $proposal['total_gross']);
        $this->assertNotEmpty(Proposal::find($proposal['id'])->sections); // secciones estándar
        $this->assertCount(2, $ctx->actions);
    }

    public function test_invalid_rut_and_duplicates_are_reported_to_the_model_not_thrown(): void
    {
        $ctx = new AgentContext($this->userWithRole('admin'));
        Client::create(['name' => 'Ya existe', 'tax_id' => '76.405.003-7']);

        $this->assertStringContainsString('RUT no es válido', (new CreateClient($ctx))->handle(new Request(['name' => 'X', 'tax_id' => '11111111-1'])));
        $this->assertStringContainsString('ya existe un cliente', (new CreateClient($ctx))->handle(new Request(['name' => 'X', 'tax_id' => '76405003-7'])));
    }

    public function test_tools_respect_the_permissions_and_visibility_of_the_user(): void
    {
        $lectura = new AgentContext($this->userWithRole('lectura'));
        $this->assertStringContainsString('no tiene permiso', (new CreateProposal($lectura))->handle(new Request(['title' => 'x', 'items' => []])));
        $this->assertStringContainsString('no tiene permiso', (new CreateClient($lectura))->handle(new Request(['name' => 'x'])));

        // Un comercial solo ve sus leads asignados y sus propuestas.
        $me = $this->userWithRole('comercial');
        $mine = $this->makeLead(['assigned_to' => $me->id, 'first_name' => 'Mia']);
        $other = $this->makeLead(['first_name' => 'Ajeno']);
        $ctx = new AgentContext($me);

        $found = (new SearchLeads($ctx))->handle(new Request(['query' => '']));
        $this->assertStringContainsString('Mia', $found);
        $this->assertStringNotContainsString('Ajeno', $found);
        $this->assertStringContainsString('sin acceso', (new AddFollowUp($ctx))->handle(new Request(['lead_id' => $other->id, 'description' => 'x'])));
        $this->assertSame('Sin resultados.', (new FindProposals($ctx))->handle(new Request(['query' => 'zzz'])));
    }

    public function test_follow_up_is_logged_on_the_lead(): void
    {
        $user = $this->userWithRole('admin');
        $lead = $this->makeLead();

        (new AddFollowUp(new AgentContext($user)))->handle(new Request(['lead_id' => $lead->id, 'type' => 'call', 'description' => 'Llamé y quedamos de hablar el viernes', 'next_follow_up_at' => '2026-10-16']));

        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'call']);
        $this->assertSame('2026-10-16', $lead->fresh()->next_follow_up_at->toDateString());
    }

    public function test_chat_endpoint_requires_permission_and_a_configured_provider(): void
    {
        $this->actingAs($this->userWithRole('lectura'))->postJson('/agent/chat', ['messages' => [['role' => 'user', 'content' => 'hola']]])->assertForbidden();
        $this->actingAs($this->userWithRole('admin'))->postJson('/agent/chat', ['messages' => [['role' => 'user', 'content' => 'hola']]])->assertStatus(503);
        $this->actingAs($this->userWithRole('admin'))->postJson('/agent/chat', ['messages' => [['role' => 'assistant', 'content' => 'hola']]])->assertStatus(422);
    }
}
