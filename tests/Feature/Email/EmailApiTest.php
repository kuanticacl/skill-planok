<?php

namespace Tests\Feature\Email;

use App\Jobs\SendEmailMessage;
use App\Models\ApiKey;
use App\Models\EmailMessage;
use App\Models\EmailSuppression;
use App\Models\EmailTemplate;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailApiTest extends TestCase
{
    use RefreshDatabase;

    private string $key;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        [, $this->key] = ApiKey::issue('Test', ['emails.send', 'emails.read']);
        EmailTemplate::create([
            'name' => 'Bienvenida', 'slug' => 'bienvenida', 'category' => 'transactional', 'is_active' => true,
            'subject' => 'Hola {{ first_name | default:"amigo" }}', 'html' => '<p>{{ first_name }} · {{ proyecto | default:"Torre Norte" }}</p>',
            'variables' => [['key' => 'proyecto', 'label' => 'Proyecto', 'default' => 'Torre Norte', 'sample' => '']],
        ]);
    }

    private function send(array $data, ?string $key = 'default')
    {
        $key = $key === 'default' ? $this->key : $key;

        return $this->postJson('/api/v1/emails/send', $data, array_filter(['Authorization' => $key ? "Bearer {$key}" : null]));
    }

    public function test_requires_a_valid_key_with_the_right_ability(): void
    {
        $this->send(['template' => 'bienvenida', 'to' => 'a@b.cl'], null)->assertUnauthorized();
        $this->send(['template' => 'bienvenida', 'to' => 'a@b.cl'], 'qbk_invalida')->assertUnauthorized();

        [, $readOnly] = ApiKey::issue('Solo lectura', ['emails.read']);
        $this->send(['template' => 'bienvenida', 'to' => 'a@b.cl'], $readOnly)->assertForbidden();
    }

    public function test_queues_a_template_with_variables(): void
    {
        $this->send(['template' => 'bienvenida', 'to' => ['email' => 'Maria@Ejemplo.cl', 'name' => 'María'], 'variables' => ['first_name' => 'María']])
            ->assertStatus(202)
            ->assertJsonPath('data.0.to', 'maria@ejemplo.cl')
            ->assertJsonPath('data.0.status', 'queued');

        $message = EmailMessage::first();
        $this->assertSame('María', $message->variables['first_name']);
        $this->assertSame('Torre Norte', $message->variables['proyecto']); // valor por defecto de la plantilla
        Queue::assertPushed(SendEmailMessage::class);
    }

    public function test_extra_parameters_become_variables(): void
    {
        $this->postJson('/api/v1/emails/send?template=bienvenida&to=pedro@ejemplo.cl&first_name=Pedro&proyecto=Edificio%20Centro', [], ['Authorization' => "Bearer {$this->key}"])
            ->assertStatus(202);

        $this->assertSame('Edificio Centro', EmailMessage::first()->variables['proyecto']);
    }

    public function test_validates_recipient_and_template(): void
    {
        $this->send(['template' => 'bienvenida'])->assertUnprocessable();
        $this->send(['template' => 'bienvenida', 'to' => 'no-es-correo'])->assertUnprocessable();
        $this->send(['template' => 'no-existe', 'to' => 'a@b.cl'])->assertNotFound();
    }

    public function test_suppressed_addresses_are_not_queued(): void
    {
        EmailSuppression::add('baja@ejemplo.cl', 'unsubscribed');

        $this->send(['template' => 'bienvenida', 'to' => 'baja@ejemplo.cl'])
            ->assertStatus(202)
            ->assertJsonPath('data.0.status', 'suppressed');

        Queue::assertNotPushed(SendEmailMessage::class);
    }

    public function test_idempotency_key_prevents_duplicates(): void
    {
        $headers = ['Authorization' => "Bearer {$this->key}", 'Idempotency-Key' => 'abc-123'];

        $this->postJson('/api/v1/emails/send', ['template' => 'bienvenida', 'to' => 'a@b.cl'], $headers)->assertStatus(202);
        $this->postJson('/api/v1/emails/send', ['template' => 'bienvenida', 'to' => 'a@b.cl'], $headers)->assertStatus(202);

        $this->assertSame(1, EmailMessage::count());
    }

    public function test_webhook_requires_a_valid_svix_signature(): void
    {
        $secret = 'whsec_'.base64_encode('clave-de-prueba');
        Setting::put('mail.webhook_secret', $secret, true);

        $message = EmailMessage::create(['kind' => 'transactional', 'to_email' => 'x@y.cl', 'status' => 'sent', 'provider_id' => 'em_123']);
        $body = json_encode(['type' => 'email.bounced', 'data' => ['email_id' => 'em_123', 'bounce' => ['type' => 'Permanent', 'message' => 'no existe']]]);
        $timestamp = time();
        $signature = 'v1,'.base64_encode(hash_hmac('sha256', "msg_1.{$timestamp}.{$body}", base64_decode(substr($secret, 6)), true));
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_SVIX_ID' => 'msg_1', 'HTTP_SVIX_TIMESTAMP' => (string) $timestamp];

        $this->call('POST', '/webhooks/resend', [], [], [], [...$server, 'HTTP_SVIX_SIGNATURE' => 'v1,AAAA'], $body)->assertUnauthorized();
        $this->assertSame('sent', $message->fresh()->status);

        $this->call('POST', '/webhooks/resend', [], [], [], [...$server, 'HTTP_SVIX_SIGNATURE' => $signature], $body)->assertOk();
        $this->assertSame('bounced', $message->fresh()->status);
        $this->assertTrue(EmailSuppression::isSuppressed('x@y.cl'));
    }
}
