<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('priority', 10)->default('normal')->after('position'); // low, normal, high, urgent
            $table->decimal('estimated_value', 14, 2)->nullable()->after('priority');
            $table->json('tags')->nullable()->after('estimated_value');
            $table->timestamp('next_follow_up_at')->nullable()->after('tags');
            $table->timestamp('stage_changed_at')->nullable()->after('next_follow_up_at');
            $table->timestamp('closed_at')->nullable()->after('stage_changed_at');
            $table->string('lost_reason')->nullable()->after('closed_at');

            $table->index('next_follow_up_at');
            $table->index('priority');
        });

        DB::table('leads')->whereNull('stage_changed_at')->update(['stage_changed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['next_follow_up_at']);
            $table->dropIndex(['priority']);
            $table->dropColumn(['priority', 'estimated_value', 'tags', 'next_follow_up_at', 'stage_changed_at', 'closed_at', 'lost_reason']);
        });
    }
};
