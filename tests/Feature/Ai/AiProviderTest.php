<?php

namespace Tests\Feature\Ai;

use App\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    public function test_api_key_is_encrypted_at_rest_and_never_exposed(): void
    {
        $admin = $this->userWithRole('admin');
        AiProvider::create(['slug' => 'openai', 'name' => 'OpenAI', 'driver' => 'openai', 'api_key' => 'sk-secret-123456', 'is_enabled' => true]);

        $raw = DB::table('ai_providers')->where('slug', 'openai')->value('api_key');
        $this->assertStringNotContainsString('sk-secret', $raw);

        $page = $this->actingAs($admin)->get('/ai');
        $page->assertOk();
        $this->assertStringNotContainsString('sk-secret-123456', $page->getContent());
        $this->assertStringContainsString('3456', $page->getContent());
    }

    public function test_manage_permission_required_for_ai_settings(): void
    {
        $this->actingAs($this->userWithRole('comercial'))->get('/ai')->assertForbidden();
    }

    public function test_template_ai_requires_template_management_permission(): void
    {
        $user = $this->userWithRole('comercial');

        $this->actingAs($user)->postJson('/email/ai/design', ['brief' => 'Correo de bienvenida para un lead'])->assertForbidden();
    }

    public function test_transcription_model_is_saved_only_for_providers_that_transcribe(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->put('/ai/providers/openai', ['api_key' => 'sk-test-1234567890', 'transcription_model' => 'gpt-4o-mini-transcribe', 'is_enabled' => true])->assertRedirect();
        $this->assertSame('gpt-4o-mini-transcribe', \App\Models\AiProvider::where('slug', 'openai')->value('transcription_model'));

        $this->actingAs($admin)->put('/ai/providers/anthropic', ['api_key' => 'sk-ant-test-1234567890', 'transcription_model' => 'whisper-1', 'is_enabled' => true])->assertRedirect();
        $this->assertNull(\App\Models\AiProvider::where('slug', 'anthropic')->value('transcription_model'));

        $this->actingAs($admin)->postJson('/ai/providers/anthropic/test-voice')->assertStatus(422);
    }
}
