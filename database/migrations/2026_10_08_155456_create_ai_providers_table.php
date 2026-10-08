<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();            // openai, anthropic, openrouter, minimax, custom…
            $table->string('name');
            $table->string('driver', 40);                 // driver del SDK de Laravel AI
            $table->text('api_key')->nullable();          // cifrada (cast "encrypted")
            $table->string('base_url')->nullable();
            $table->string('model')->nullable();          // modelo de texto por defecto
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('last_test_ok')->nullable();
            $table->string('last_test_message', 500)->nullable();
            $table->unsignedInteger('last_test_ms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
