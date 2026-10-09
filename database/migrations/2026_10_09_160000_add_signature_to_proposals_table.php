<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->change(); // cabe "changes_requested"
            $table->mediumText('signature_data')->nullable()->after('response_note'); // firma manuscrita del cliente (PNG en data URL)
            $table->string('signer_rut', 20)->nullable()->after('responded_by');
            $table->string('response_user_agent', 400)->nullable()->after('response_ip');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['signature_data', 'signer_rut', 'response_user_agent']);
        });
    }
};
