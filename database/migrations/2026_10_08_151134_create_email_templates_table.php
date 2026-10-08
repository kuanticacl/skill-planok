<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();           // identificador para la API
            $table->string('description')->nullable();
            $table->string('category', 20)->default('marketing'); // marketing | transactional
            $table->string('subject');
            $table->string('preheader')->nullable();
            $table->string('editor', 10)->default('blocks');      // blocks | html
            $table->longText('html')->nullable();                 // HTML final (con {{variables}})
            $table->text('text')->nullable();                     // versión texto plano (opcional)
            $table->json('design')->nullable();                   // bloques del editor visual
            $table->json('variables')->nullable();                // [{key,label,default,sample}]
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
