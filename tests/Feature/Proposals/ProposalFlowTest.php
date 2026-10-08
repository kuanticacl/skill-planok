<?php

namespace Tests\Feature\Proposals;

use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Services\LeadService;
use App\Services\Proposals\ProposalBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class ProposalFlowTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    private function proposalFor(?Lead $lead = null): Proposal
    {
        return app(ProposalBuilder::class)->save([
            'title' => 'Propuesta de prueba',
            'lead_id' => $lead?->id,
            'recipient' => ['company' => 'Los Robles', 'rut' => '761234560'],
            'sections' => ProposalBuilder::defaultSections(),
            'discount_type' => 'percent', 'discount_value' => 0, 'tax_rate' => 19, 'contract_months' => 3,
            'items' => [
                ['name' => 'Meta Ads', 'billing' => 'monthly', 'quantity' => 1, 'unit_price' => 600000],
                ['name' => 'Landing', 'billing' => 'one_time', 'quantity' => 1, 'unit_price' => 400000],
            ],
        ], null, null);
    }

    public function test_totals_are_recalculated_on_the_server(): void
    {
        $p = $this->proposalFor();

        $this->assertSame(400000 + 600000 * 3, $p->total_net);
        $this->assertSame('76.123.456-0', $p->recipient['rut']);
        $this->assertMatchesRegularExpression('/^P-\d{4}-0001$/', $p->number);
    }

    public function test_public_link_hides_drafts_and_records_view_and_acceptance(): void
    {
        $lead = app(LeadService::class)->create(['first_name' => 'Ana', 'email' => 'ana@empresa.cl']);
        $p = $this->proposalFor($lead);

        $this->get('/p/'.$p->public_token)->assertNotFound(); // borrador: no es público

        $p->update(['status' => 'sent', 'sent_at' => now()]);
        $this->get('/p/'.$p->public_token)->assertOk()->assertSee('Servicios e inversión');
        $this->assertSame('viewed', $p->fresh()->status);

        $this->post('/p/'.$p->public_token.'/respond', ['action' => 'accept', 'name' => 'María Soto'])->assertRedirect();
        $this->assertSame('accepted', $p->fresh()->status);
        $this->assertSame('María Soto', $p->fresh()->responded_by);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'proposal']);

        // Ya respondida: no se puede volver a responder.
        $this->post('/p/'.$p->public_token.'/respond', ['action' => 'reject', 'name' => 'X'])->assertNotFound();
    }

    public function test_commercial_user_only_sees_own_or_assigned_proposals(): void
    {
        $me = $this->userWithRole('comercial');
        $other = $this->userWithRole('comercial');
        $mine = $this->proposalFor();
        $mine->update(['user_id' => $me->id]);
        $theirs = $this->proposalFor();
        $theirs->update(['user_id' => $other->id]);

        $this->actingAs($me)->get('/proposals/'.$mine->id)->assertOk();
        $this->actingAs($me)->get('/proposals/'.$theirs->id)->assertForbidden();
    }

    public function test_moving_a_lead_to_a_stage_that_requires_proposal_reports_it(): void
    {
        $admin = $this->userWithRole('admin');
        $lead = app(LeadService::class)->create(['first_name' => 'Ana', 'email' => 'ana@empresa.cl']);
        $stage = PipelineStage::where('requires_proposal', true)->where('type', 'open')->first();

        $this->actingAs($admin)->putJson("/leads/{$lead->id}/move", ['stage_id' => $stage->id])
            ->assertOk()->assertJsonPath('needs_proposal', true);
    }

    public function test_accepted_proposals_cannot_be_edited(): void
    {
        $admin = $this->userWithRole('admin');
        $p = $this->proposalFor();
        $p->update(['status' => 'accepted']);

        $this->actingAs($admin)->putJson('/proposals/'.$p->id, ['title' => 'x', 'discount_type' => 'percent', 'tax_rate' => 19])->assertStatus(422);
    }

    public function test_pdf_can_be_downloaded_by_staff_and_by_public_link_only_when_sent(): void
    {
        $admin = $this->userWithRole('admin');
        $p = $this->proposalFor();

        $res = $this->actingAs($admin)->get('/proposals/'.$p->id.'/pdf')->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->assertStringContainsString('Propuesta-'.$p->number, $res->headers->get('Content-Disposition'));

        auth()->logout();
        $this->get('/p/'.$p->public_token.'/pdf')->assertNotFound(); // borrador
        $p->update(['status' => 'sent']);
        $this->get('/p/'.$p->public_token.'/pdf')->assertOk();
    }

    public function test_document_shows_holding_brands_and_agency_data(): void
    {
        $p = $this->proposalFor();
        $p->update(['status' => 'sent']);

        $this->get('/p/'.$p->public_token)->assertOk()
            ->assertSee('Parte del holding')->assertSee('bemodular.cl')->assertSee('kuantica.cl')->assertSee('integraleads.cl')
            ->assertSee('76.302.966-2')->assertSee('Av. Apoquindo 7935')->assertSee('Tecnologías con las que trabajamos');
    }
}
