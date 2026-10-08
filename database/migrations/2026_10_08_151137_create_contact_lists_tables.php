<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contact_list_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_list_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->json('data')->nullable(); // variables extra (empresa, proyecto, etc.)
            $table->timestamps();

            $table->unique(['contact_list_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_list_entries');
        Schema::dropIfExists('contact_lists');
    }
};
