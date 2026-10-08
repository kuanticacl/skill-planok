<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Perfilamiento por reglas (se recalcula solo cuando cambian los datos del lead)
            $table->unsignedTinyInteger('score')->default(0)->after('lost_reason');
            $table->string('score_grade', 1)->nullable()->after('score');         // A, B, C, D
            $table->unsignedTinyInteger('profile_completeness')->default(0)->after('score_grade');
            $table->json('score_breakdown')->nullable()->after('profile_completeness');
            $table->json('profile')->nullable()->after('score_breakdown');          // datos derivados (enriquecimiento)
            $table->timestamp('scored_at')->nullable()->after('profile');

            // Análisis con IA (opcional)
            $table->json('ai_analysis')->nullable()->after('scored_at');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_analysis');
            $table->string('ai_input_hash', 40)->nullable()->after('ai_analyzed_at');
            $table->tinyInteger('ai_adjustment')->default(0)->after('ai_input_hash'); // -15..+15

            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['score']);
            $table->dropColumn(['score', 'score_grade', 'profile_completeness', 'score_breakdown', 'profile', 'scored_at', 'ai_analysis', 'ai_analyzed_at', 'ai_input_hash', 'ai_adjustment']);
        });
    }
};
