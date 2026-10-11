<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accesos al portal de clientes: un usuario con rol «cliente» queda ligado a una empresa (y opcionalmente a su ficha de cliente).
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->index()->after('role_id');
            $table->unsignedBigInteger('lead_id')->nullable()->after('client_id');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('portal_invited_at')->nullable()->after('last_login_at');
        });

        // Servicios contratados por una empresa.
        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('proposal_id')->nullable()->index();
            $table->unsignedBigInteger('catalog_service_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('billing_cycle', 12)->default('monthly'); // one_time, monthly, quarterly, yearly
            $table->string('currency', 3)->default('CLP');
            $table->decimal('price', 14, 2)->default(0); // neto por período
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->string('status', 12)->default('active'); // pending, active, paused, ended, cancelled
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->date('next_charge_on')->nullable();
            $table->json('reminder_offsets')->nullable();
            $table->string('payment_link', 500)->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'next_charge_on']);
        });

        // Costos y gastos asociados a un servicio: solo uso interno (el cliente nunca los ve).
        Schema::create('service_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_service_id')->index();
            $table->string('concept');
            $table->string('currency', 3)->default('CLP');
            $table->decimal('amount', 14, 2);
            $table->decimal('amount_clp', 14, 0);
            $table->date('incurred_on');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Cobros / facturas. La factura la emite un software externo: aquí se adjunta su PDF.
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('client_service_id')->nullable()->index();
            $table->string('number', 60)->nullable();
            $table->string('concept');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('due_date');
            $table->string('currency', 3)->default('CLP');
            $table->decimal('amount_net', 14, 2);
            $table->decimal('tax_rate', 5, 2)->default(19);
            $table->decimal('amount_total', 14, 2);
            $table->decimal('uf_value', 10, 2)->nullable();
            $table->decimal('net_clp', 14, 0)->nullable();
            $table->decimal('total_clp', 14, 0)->nullable();
            $table->string('status', 12)->default('scheduled'); // scheduled (sin PDF), issued (visible al cliente), paid, cancelled
            $table->string('pdf_path')->nullable();
            $table->string('pdf_name')->nullable();
            $table->boolean('auto_remind')->default(false);
            $table->string('payment_link', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 60)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'due_date']);
        });

        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->string('kind', 10); // issued, auto, manual
            $table->integer('offset_days')->nullable();
            $table->boolean('skipped')->default(false);
            $table->unsignedSmallInteger('recipients')->default(0);
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminders');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('service_expenses');
        Schema::dropIfExists('client_services');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'lead_id', 'must_change_password', 'portal_invited_at']);
        });
    }
};
