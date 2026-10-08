<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_sources', function (Blueprint $table) {
            // Puntos que suma (o resta) a un lead según la calidad histórica del origen: -10 … +20
            $table->tinyInteger('score_weight')->default(0)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('lead_sources', function (Blueprint $table) {
            $table->dropColumn('score_weight');
        });
    }
};
