<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('activity')->nullable()->after('legal_name');   // giro
            $table->string('commune', 120)->nullable()->after('address');
            $table->string('contact_name')->nullable()->after('website');
            $table->string('contact_role')->nullable()->after('contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['activity', 'commune', 'contact_name', 'contact_role']);
        });
    }
};
