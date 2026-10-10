<?php

namespace Tests\Feature\Ai;

use App\Models\AiRun;
use App\Models\Setting;
use App\Services\Ai\AiPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class AiUsageTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    public function test_cost_is_tokens_times_price_and_unknown_models_have_no_cost(): void
    {
        $pricing = app(AiPricing::class);

        $this->assertEqualsWithDelta(0.15 + 0.60, $pricing->cost('openai', 'gpt-4o-mini', 1_000_000, 1_000_000), 0.0001);
        $this->assertNull($pricing->cost('x', 'modelo-desconocido', 1000, 1000));

        // Un precio definido por el usuario manda sobre el de referencia.
        Setting::put('ai.prices', json_encode([AiPricing::key('x', 'modelo-desconocido') => [1, 2]]));
        $this->assertEqualsWithDelta(3.0, $pricing->cost('x', 'modelo-desconocido', 1_000_000, 1_000_000), 0.0001);
    }

    public function test_usage_page_reports_totals_errors_and_requires_permission(): void
    {
        AiRun::create(['feature' => 'agent', 'provider' => 'openai', 'model' => 'gpt-4o-mini', 'status' => 'ok', 'input_tokens' => 1000, 'output_tokens' => 500, 'duration_ms' => 2000]);
        AiRun::create(['feature' => 'agent', 'provider' => 'openai', 'model' => 'gpt-4o-mini', 'status' => 'error', 'duration_ms' => 800, 'error' => 'rate limit']);

        $this->actingAs($this->userWithRole('comercial'))->get('/ai/usage')->assertForbidden();

        $this->actingAs($this->userWithRole('admin'))->get('/ai/usage?days=7')->assertOk()->assertInertia(fn ($page) => $page
            ->component('ai/Usage')
            ->where('totals.calls', 2)->where('totals.errors', 1)->where('totals.input_tokens', 1000)
            ->has('daily', 7)->has('errors', 1)->where('topErrors.0.count', 1));
    }

    public function test_prices_and_budget_can_be_saved_and_reset(): void
    {
        $admin = $this->userWithRole('admin');
        $key = AiPricing::key('openai', 'gpt-5-nano');

        $this->actingAs($admin)->put('/ai/usage/prices', ['key' => $key, 'input' => 0.05, 'output' => 0.4])->assertRedirect();
        $this->assertSame('custom', app(AiPricing::class)->find('openai', 'gpt-5-nano')['source']);

        $this->actingAs($admin)->put('/ai/usage/prices', ['key' => $key, 'input' => null, 'output' => null])->assertRedirect();
        $this->assertNull(app(AiPricing::class)->find('openai', 'gpt-5-nano'));

        $this->actingAs($admin)->put('/ai/usage/budget', ['budget' => 50])->assertRedirect();
        $this->assertSame('50', Setting::get('ai.monthly_budget_usd'));
    }
}
