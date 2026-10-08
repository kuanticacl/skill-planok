<?php

namespace Tests\Feature\Proposals;

use App\Models\Proposal;
use App\Models\UfValue;
use App\Services\Proposals\ProposalBuilder;
use App\Services\UfService;
use App\Support\ProposalText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class UfProposalTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    private function fakeFindic(float $value = 40000.5): void
    {
        $today = now('America/Santiago')->toDateString();
        Http::fake(['findic.cl/*' => Http::response(['serie' => [['fecha' => $today.'T03:00:00.000Z', 'valor' => $value]]])]);
    }

    private function make(array $over = []): Proposal
    {
        return app(ProposalBuilder::class)->save($over + [
            'title' => 'Propuesta UF',
            'recipient' => ['company' => 'Los Robles'],
            'sections' => ProposalBuilder::defaultSections(),
            'discount_type' => 'percent', 'discount_value' => 0, 'tax_rate' => 19, 'contract_months' => 2,
            'items' => [
                ['name' => 'Meta Ads', 'billing' => 'monthly', 'quantity' => 1, 'unit_price' => 15.5],
                ['name' => 'Landing', 'billing' => 'one_time', 'quantity' => 1, 'unit_price' => 20],
            ],
        ], null, null);
    }

    public function test_sync_stores_the_findic_series(): void
    {
        $this->fakeFindic(41122.74);

        $this->assertSame(1, app(UfService::class)->sync());
        $this->assertSame('41122.74', (string) UfValue::first()->value);
    }

    public function test_proposals_default_to_uf_with_decimals_and_snapshot_the_day_value(): void
    {
        $this->fakeFindic(40000.5);
        $p = $this->make();

        $this->assertSame('UF', $p->currency);
        $this->assertEquals(20 + 15.5 * 2, $p->total_net);
        $this->assertEquals(40000.5, $p->uf_value);
        $this->assertSame(now('America/Santiago')->toDateString(), $p->uf_date->toDateString());
    }

    public function test_uf_is_frozen_once_the_proposal_is_sent(): void
    {
        $this->fakeFindic(40000.5);
        $p = $this->make();

        app(ProposalBuilder::class)->freezeUf($p);
        $p->update(['status' => 'sent', 'sent_at' => now()]);
        UfValue::query()->update(['value' => 99999]);

        $this->assertEquals(40000.5, $p->fresh()->uf_value);
    }

    public function test_clp_proposals_do_not_use_uf_decimals(): void
    {
        $this->fakeFindic();
        $p = $this->make(['currency' => 'CLP', 'items' => [['name' => 'X', 'billing' => 'one_time', 'quantity' => 1, 'unit_price' => 1000.6]]]);

        $this->assertSame('CLP', $p->currency);
        $this->assertSame(1001, (int) $p->total_net);
    }

    public function test_rich_text_is_sanitized(): void
    {
        $html = ProposalText::sanitize('<p onclick="x()">Hola <strong>mundo</strong></p><script>alert(1)</script><a href="javascript:alert(1)">m</a>');

        $this->assertStringContainsString('<strong>mundo</strong>', $html);
        $this->assertStringNotContainsString('script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_legacy_markdown_text_is_rendered_as_html(): void
    {
        $this->assertStringContainsString('<strong>negrita</strong>', ProposalText::html('Esto es **negrita**'));
    }

    public function test_demo_seed_creates_one_client_per_source(): void
    {
        $this->fakeFindic();
        $this->artisan('crm:seed-demo')->assertSuccessful();

        $sources = \App\Models\LeadSource::count();
        $this->assertGreaterThan(0, $sources);
        $this->assertGreaterThanOrEqual($sources, \App\Models\Client::count());
        $this->assertGreaterThanOrEqual($sources, Proposal::count());
    }
}
