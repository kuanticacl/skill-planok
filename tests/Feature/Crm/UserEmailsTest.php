<?php

namespace Tests\Feature\Crm;

use App\Models\EmailMessage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class UserEmailsTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
        Bus::fake();
    }

    public function test_creating_a_user_queues_the_welcome_email_with_access_data()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Ana Pérez', 'email' => 'ana@quiebre.cl', 'role_id' => Role::first()->id, 'is_active' => true,
            'password' => 'Qb-Clave-Segura-1', 'password_confirmation' => 'Qb-Clave-Segura-1',
        ])->assertRedirect();

        $m = EmailMessage::where('to_email', 'ana@quiebre.cl')->firstOrFail();
        $this->assertSame('Qb-Clave-Segura-1', $m->variables['password']);
        $this->assertStringEndsWith('/login', $m->variables['login_url']);
        $this->assertSame('bienvenida-usuario', $m->template->slug);
        $this->assertSame('blocks', $m->template->editor); // editable en el editor visual
        $this->assertNotEmpty($m->template->design['blocks']);
    }

    public function test_password_reset_request_queues_the_branded_email()
    {
        $user = User::factory()->create(['email' => 'beto@quiebre.cl']);

        $this->post('/forgot-password', ['email' => 'beto@quiebre.cl']);

        $m = EmailMessage::where('to_email', 'beto@quiebre.cl')->firstOrFail();
        $this->assertSame('recuperar-password', $m->template->slug);
        $this->assertStringContainsString('/reset-password/', $m->variables['reset_url']);
        $this->assertStringContainsString('email=beto%40quiebre.cl', $m->variables['reset_url']);
    }

    public function test_every_email_gets_the_holding_footer_once()
    {
        $composer = app(\App\Services\Email\EmailComposer::class);
        $html = $composer->renderContent('Hola', '<html><body><p>Hola</p></body></html>', [])['html'];

        $this->assertSame(1, substr_count($html, 'Parte del holding'));
        foreach (['bemodular', 'kuantica', 'integraleads'] as $logo) {
            $this->assertStringContainsString("/brand/partners/{$logo}.png", $html);
        }
        $this->assertSame($html, $composer->withHoldingFooter($html)); // idempotente
    }

    public function test_landing_kit_creates_one_origin_per_holding_brand()
    {
        (new \App\Services\Email\LandingKit)->install();

        foreach (['formulario-landing-kuantica', 'formulario-landing-be-modular', 'formulario-landing-integraleads'] as $slug) {
            $this->assertTrue(\App\Models\LeadSource::where('slug', $slug)->exists(), $slug);
        }
    }
}
