<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de servicios pre armados de la agencia (con tarifa de referencia en CLP).
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 60)->default('General');
            $table->text('description')->nullable();
            $table->json('deliverables')->nullable();            // lista de entregables
            $table->string('billing', 10)->default('one_time');  // one_time | monthly
            $table->string('unit', 30)->default('servicio');     // servicio, mes, proyecto, hora…
            $table->unsignedBigInteger('price')->default(0);     // neto, CLP
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
