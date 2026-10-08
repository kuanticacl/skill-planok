<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historial de la UF (findic.cl): una fila por día.
        Schema::create('uf_values', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->decimal('value', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uf_values');
    }
};
