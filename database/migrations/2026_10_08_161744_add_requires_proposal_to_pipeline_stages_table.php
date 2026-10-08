<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->boolean('requires_proposal')->default(false)->after('type');
        });

        // Por defecto: «Propuesta» y las etapas de cierre positivo.
        DB::table('pipeline_stages')->where('name', 'like', 'Propuesta%')->orWhere('type', 'won')->update(['requires_proposal' => true]);
    }

    public function down(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn('requires_proposal');
        });
    }
};
