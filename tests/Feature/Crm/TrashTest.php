<?php

namespace Tests\Feature\Crm;

use App\Models\LeadSource;
use App\Services\CascadeDelete;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class TrashTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    public function test_restoring_a_source_brings_back_its_leads_with_a_new_key_and_slug(): void
    {
        $admin = $this->userWithRole('admin');
        $source = LeadSource::create(['name' => 'Origen X', 'slug' => 'origen-x', 'api_key' => 'qb_'.str_repeat('a', 40), 'is_active' => true]);
        $lead = app(LeadService::class)->create(['first_name' => 'Ana', 'email' => 'ana@empresa.cl', 'source_id' => $source->id]);
        app(CascadeDelete::class)->source($source);

        $this->actingAs($admin)->get('/trash?type=sources')->assertOk();
        $this->actingAs($admin)->post('/trash/sources/'.$source->id.'/restore')->assertRedirect();

        $source = $source->fresh();
        $this->assertNull($source->deleted_at);
        $this->assertSame('origen-x', $source->slug);
        $this->assertNotSame('qb_'.str_repeat('a', 40), $source->api_key);
        $this->assertNull($lead->fresh()->deleted_at);
    }

    public function test_a_lead_cannot_be_restored_while_its_source_is_in_the_trash(): void
    {
        $admin = $this->userWithRole('admin');
        $source = LeadSource::create(['name' => 'Origen Y', 'slug' => 'origen-y', 'api_key' => 'qb_'.str_repeat('b', 40), 'is_active' => true]);
        $lead = app(LeadService::class)->create(['first_name' => 'Ana', 'email' => 'ana@empresa.cl', 'source_id' => $source->id]);
        app(CascadeDelete::class)->source($source);

        $this->actingAs($admin)->post('/trash/leads/'.$lead->id.'/restore')->assertStatus(422);
        $this->assertSoftDeleted($lead);
    }

    public function test_only_users_with_the_trash_permission_can_open_it(): void
    {
        $this->actingAs($this->userWithRole('comercial'))->get('/trash')->assertForbidden();
    }
}
