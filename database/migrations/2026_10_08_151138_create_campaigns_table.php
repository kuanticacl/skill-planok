<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->string('preheader')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->longText('html')->nullable();          // contenido congelado al enviar
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->json('audience')->nullable();          // {lists:[], leads:{...}, clients:{...}}
            $table->json('variables')->nullable();         // valores por defecto de variables de la campaña
            $table->string('status', 15)->default('draft'); // draft, scheduled, sending, sent, paused, cancelled, failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
