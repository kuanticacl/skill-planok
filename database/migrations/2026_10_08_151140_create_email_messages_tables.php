<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                   // token de tracking / baja
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->foreignId('automation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 15)->default('transactional'); // campaign | transactional | test
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('subject')->nullable();
            // queued, sending, sent, delivered, bounced, complained, failed, suppressed
            $table->string('status', 15)->default('queued');
            $table->string('provider', 10)->nullable();
            $table->string('provider_id')->nullable()->index();
            $table->text('error')->nullable();
            $table->json('variables')->nullable();
            $table->longText('html')->nullable();             // solo transaccionales / pruebas (auditoría)
            $table->boolean('track_opens')->default(false);
            $table->boolean('track_clicks')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('first_clicked_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index('to_email');
            $table->index('created_at');
        });

        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_message_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);   // sent, delivered, opened, clicked, bounced, complained, failed, unsubscribed…
            $table->json('data')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['email_message_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_events');
        Schema::dropIfExists('email_messages');
    }
};
