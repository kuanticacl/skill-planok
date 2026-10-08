<?php

namespace Tests\Feature\Ai;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Services\LeadService;
use App\Services\Leads\LeadScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class LeadScoringTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    public function test_complete_lead_scores_higher_than_a_bare_one(): void
    {
        $service = app(LeadService::class);
        $source = LeadSource::first();
        $source->update(['score_weight' => 8]);

        $bare = $service->create(['first_name' => 'Ana', 'email' => 'ana@gmail.com', 'source_id' => $source->id]);
        $full = $service->create([
            'first_name' => 'Luis', 'last_name' => 'Soto', 'email' => 'luis@constructora.cl', 'phone' => '+56912345678',
            'company' => 'Constructora Sur', 'job_title' => 'Gerente comercial', 'message' => 'Quiero cotizar una campaña para mi proyecto nuevo',
            'source_id' => $source->id,
        ]);

        $this->assertGreaterThan($bare->fresh()->score, $full->fresh()->score);
        $this->assertGreaterThan($bare->fresh()->profile_completeness, $full->fresh()->profile_completeness);
        $this->assertContains('Teléfono', array_column($bare->fresh()->profile['missing'], 'label'));
    }

    public function test_score_updates_when_a_follow_up_is_logged(): void
    {
        $lead = app(LeadService::class)->create(['first_name' => 'Ana', 'email' => 'ana@empresa.cl']);
        $before = $lead->fresh()->score;

        app(LeadService::class)->log($lead, 'call', null, 'Llamé y pidió propuesta');

        $this->assertGreaterThan($before, $lead->fresh()->score);
    }

    public function test_won_and_lost_leads_have_fixed_scores(): void
    {
        $lead = app(LeadService::class)->create(['first_name' => 'Ana', 'email' => 'ana@empresa.cl']);
        $won = \App\Models\PipelineStage::where('type', 'won')->first();
        $lost = \App\Models\PipelineStage::where('type', 'lost')->first();

        app(LeadService::class)->move($lead, $won->id);
        $this->assertSame(100, $lead->fresh()->score);

        app(LeadService::class)->move($lead->fresh(), $lost->id);
        $this->assertLessThanOrEqual(20, $lead->fresh()->score);
    }

    public function test_grade_thresholds(): void
    {
        $scorer = app(LeadScorer::class);

        $this->assertSame('A', $scorer->grade(70));
        $this->assertSame('B', $scorer->grade(50));
        $this->assertSame('C', $scorer->grade(30));
        $this->assertSame('D', $scorer->grade(29));
    }
}
