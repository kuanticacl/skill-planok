<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Proposal;
use App\Services\LeadService;
use App\Services\Proposals\ProposalBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class CascadeDeleteTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    private function sourceWithLeadAndProposal(): array
    {
        $source = LeadSource::create(['name' => 'Origen X', 'slug' => 'origen-x', 'api_key' => 'qb_'.str_repeat('a', 40), 'is_active' => true]);
        $lead = app(LeadService::class)->create(['first_name' => 'Ana', 'email' => 'ana@empresa.cl', 'source_id' => $source->id]);
        $proposal = app(ProposalBuilder::class)->save([
            'title' => 'P', 'currency' => 'CLP', 'lead_id' => $lead->id, 'recipient' => [], 'sections' => ProposalBuilder::defaultSections(),
            'discount_type' => 'percent', 'discount_value' => 0, 'tax_rate' => 19, 'contract_months' => 1,
            'items' => [['name' => 'Landing', 'billing' => 'one_time', 'quantity' => 1, 'unit_price' => 100000]],
        ], null, null);

        return [$source, $lead, $proposal];
    }

    public function test_deleting_a_source_with_leads_requires_confirmation_then_trashes_everything(): void
    {
        $admin = $this->userWithRole('admin');
        [$source, $lead, $proposal] = $this->sourceWithLeadAndProposal();

        $this->actingAs($admin)->delete('/crm/sources/'.$source->id)->assertRedirect();
        $this->assertNull($source->fresh()->deleted_at);

        $this->actingAs($admin)->delete('/crm/sources/'.$source->id, ['confirm_related' => 1])->assertRedirect();

        $this->assertSoftDeleted($source);
        $this->assertSoftDeleted($lead);
        $this->assertSoftDeleted($proposal);
        $this->assertNotSame('origen-x', LeadSource::withTrashed()->find($source->id)->slug); // slug liberado
    }

    public function test_deleting_a_lead_trashes_its_proposals_only_after_confirmation(): void
    {
        $admin = $this->userWithRole('admin');
        [, $lead, $proposal] = $this->sourceWithLeadAndProposal();

        $this->actingAs($admin)->delete('/leads/'.$lead->id)->assertRedirect();
        $this->assertNull($lead->fresh()->deleted_at);

        $this->actingAs($admin)->delete('/leads/'.$lead->id, ['confirm_related' => 1])->assertRedirect();
        $this->assertSoftDeleted($lead);
        $this->assertSoftDeleted($proposal);
    }
}
