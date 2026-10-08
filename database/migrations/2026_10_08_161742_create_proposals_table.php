<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();               // P-2026-0001
            $table->string('title');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // responsable
            $table->string('status', 12)->default('draft');       // draft|sent|viewed|accepted|rejected|expired
            $table->json('recipient')->nullable();                // datos del destinatario al momento de emitir
            $table->json('sections')->nullable();                 // [{title, body}]
            $table->date('issued_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedSmallInteger('contract_months')->nullable();   // duración de servicios mensuales
            $table->string('discount_type', 8)->default('percent');       // percent | amount
            $table->unsignedBigInteger('discount_value')->default(0);
            $table->unsignedTinyInteger('tax_rate')->default(19);
            $table->unsignedBigInteger('total_one_time')->default(0);     // neto con descuento
            $table->unsignedBigInteger('total_monthly')->default(0);      // neto con descuento
            $table->unsignedBigInteger('total_net')->default(0);          // pago único + mensual × meses
            $table->unsignedBigInteger('total_tax')->default(0);
            $table->unsignedBigInteger('total_gross')->default(0);
            $table->text('internal_notes')->nullable();
            $table->uuid('public_token')->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('responded_at')->nullable();
            $table->string('responded_by')->nullable();
            $table->text('response_note')->nullable();
            $table->string('response_ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lead_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
