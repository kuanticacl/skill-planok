<?php

namespace Tests\Feature\Crm;

use App\Models\LeadSource;
use App\Models\UfValue;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class EstimatedValueTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
        UfValue::updateOrCreate(['date' => now('America/Santiago')->toDateString()], ['value' => 40000]);
    }

    private function lead(): \App\Models\Lead
    {
        $source = LeadSource::create(['name' => 'Manual', 'slug' => 'manual-v', 'api_key' => 'qb_'.str_repeat('b', 40), 'is_active' => true]);

        return app(LeadService::class)->create(['first_name' => 'Ana', 'email' => 'ana@empresa.cl', 'source_id' => $source->id]);
    }

    public function test_a_value_in_uf_is_stored_with_its_clp_equivalent(): void
    {
        $lead = app(LeadService::class)->update($this->lead(), ['estimated_amount' => 500, 'estimated_currency' => 'UF']);

        $this->assertSame('UF', $lead->estimated_currency);
        $this->assertEquals(500, $lead->estimated_amount);
        $this->assertEquals(20000000, $lead->estimated_value);
    }

    public function test_a_plain_estimated_value_is_understood_as_pesos(): void
    {
        $lead = app(LeadService::class)->update($this->lead(), ['estimated_value' => 1500000]);

        $this->assertSame('CLP', $lead->estimated_currency);
        $this->assertEquals(1500000, $lead->estimated_amount);
    }

    public function test_quick_edit_accepts_currency_and_clearing_the_value(): void
    {
        $admin = $this->userWithRole('admin');
        $lead = $this->lead();

        $this->actingAs($admin)->patchJson("/leads/{$lead->id}/quick", ['estimated_amount' => 100, 'estimated_currency' => 'UF'])->assertOk();
        $this->assertEquals(4000000, $lead->fresh()->estimated_value);

        $this->actingAs($admin)->patchJson("/leads/{$lead->id}/quick", ['estimated_amount' => null, 'estimated_currency' => 'CLP'])->assertOk();
        $this->assertNull($lead->fresh()->estimated_value);

        $this->actingAs($admin)->patchJson("/leads/{$lead->id}/quick", ['estimated_amount' => 5, 'estimated_currency' => 'USD'])->assertStatus(422);
    }
}
