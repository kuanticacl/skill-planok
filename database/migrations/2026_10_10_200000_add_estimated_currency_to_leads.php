<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El valor estimado sigue guardándose en pesos (estimated_value) para sumar y ordenar;
 * ahora se recuerda además en qué moneda lo ingresó la persona y cuánto era (p. ej. UF 500).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('estimated_currency', 3)->default('CLP')->after('estimated_value');
            $table->decimal('estimated_amount', 14, 2)->nullable()->after('estimated_currency');
        });

        DB::table('leads')->whereNotNull('estimated_value')->update(['estimated_amount' => DB::raw('estimated_value')]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['estimated_currency', 'estimated_amount']);
        });
    }
};
