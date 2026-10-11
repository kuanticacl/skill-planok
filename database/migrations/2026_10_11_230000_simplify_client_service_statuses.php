<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Estados de un servicio: Activo, Pendiente de pago, Bloqueado y Cancelado. «Por iniciar» y «Finalizado» se deducen de las fechas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('client_services')->where('status', 'pending')->update(['status' => 'active']); // «Por iniciar» se deduce de la fecha de inicio
        DB::table('client_services')->where('status', 'paused')->update(['status' => 'blocked']);
        DB::table('client_services')->where('status', 'ended')->update(['status' => 'cancelled']);
    }

    public function down(): void {}
};
