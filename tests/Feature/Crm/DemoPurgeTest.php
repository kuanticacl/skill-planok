<?php

namespace Tests\Feature\Crm;

use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Services\DemoPurge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class DemoPurgeTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    public function test_demo_mode_removes_only_sample_data_and_keeps_configuration(): void
    {
        $demo = $this->makeLead(['email' => 'cliente@ejemplo.cl']);
        $real = $this->makeLead(['email' => 'real@empresa.cl']);
        $sources = LeadSource::count();

        $done = app(DemoPurge::class)->purge('demo');

        $this->assertSame(1, $done['leads']);
        $this->assertNull(Lead::withTrashed()->find($demo->id));
        $this->assertNotNull(Lead::find($real->id));
        $this->assertSame($sources, LeadSource::count());
    }

    public function test_all_mode_clears_leads_and_clients_including_trashed(): void
    {
        $lead = $this->makeLead();
        $lead->delete();
        Client::create(['name' => 'Cliente real']);

        app(DemoPurge::class)->purge('all');

        $this->assertSame(0, Lead::withTrashed()->count());
        $this->assertSame(0, Client::withTrashed()->count());
    }

    public function test_the_page_requires_the_permission_and_the_typed_confirmation(): void
    {
        $admin = $this->userWithRole('admin');
        $this->makeLead(['email' => 'x@ejemplo.cl']);

        $this->actingAs($this->userWithRole('comercial'))->get('/demo-data')->assertForbidden();
        $this->actingAs($admin)->post('/demo-data/purge', ['mode' => 'demo', 'confirm' => 'no'])->assertSessionHasErrors('confirm');
        $this->assertSame(1, Lead::count());

        $this->actingAs($admin)->post('/demo-data/purge', ['mode' => 'demo', 'confirm' => 'LIMPIAR'])->assertRedirect();
        $this->assertSame(0, Lead::count());
    }
}
