<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger', 30);                  // lead.created | lead.stage_changed
            $table->json('conditions')->nullable();         // {source_ids:[], stage_ids:[]}
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('subject')->nullable();          // reemplaza el asunto de la plantilla
            $table->string('to_mode', 20)->default('lead'); // lead | fixed
            $table->string('to_email')->nullable();         // si to_mode = fixed (aviso interno)
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->json('variables')->nullable();          // variables estáticas extra
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('runs_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
