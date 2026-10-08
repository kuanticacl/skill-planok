<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cotizaciones en UF: montos con decimales, moneda por servicio/propuesta y UF del día guardada en cada propuesta.
 * Lo que ya existía estaba en pesos: queda marcado como CLP (las propuestas) y el catálogo se convierte a UF.
 */
return new class extends Migration
{
    private const UF_REFERENCE = 41000; // solo para convertir el catálogo inicial de pesos a UF

    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('currency', 3)->default('UF')->after('unit');
            $table->decimal('price', 14, 2)->default(0)->change();
        });
        DB::table('services')->get()->each(fn ($s) => DB::table('services')->where('id', $s->id)->update([
            'price' => round(($s->price / self::UF_REFERENCE) * 2) / 2,   // en pasos de 0,5 UF
            'currency' => 'UF',
        ]));

        Schema::table('proposals', function (Blueprint $table) {
            $table->string('currency', 3)->default('UF')->after('title');
            $table->decimal('uf_value', 12, 2)->nullable()->after('currency');
            $table->date('uf_date')->nullable()->after('uf_value');
            $table->decimal('discount_value', 16, 2)->default(0)->change();
            foreach (['total_one_time', 'total_monthly', 'total_net', 'total_tax', 'total_gross'] as $c) {
                $table->decimal($c, 16, 2)->default(0)->change();
            }
        });
        DB::table('proposals')->update(['currency' => 'CLP']); // las existentes estaban en pesos

        Schema::table('proposal_items', function (Blueprint $table) {
            $table->decimal('unit_price', 14, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('proposal_items', fn (Blueprint $t) => $t->unsignedBigInteger('unit_price')->default(0)->change());
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['currency', 'uf_value', 'uf_date']);
        });
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn('currency'));
    }
};
