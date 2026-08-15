<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wallets
        Schema::create('fin_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('type'); // revenue, expense, driver_pool
            $table->bigInteger('ledger_balance')->default(0);
            $table->bigInteger('available_balance')->default(0);
            $table->bigInteger('pending_holds')->default(0);
            $table->integer('version')->default(1);
            $table->timestamps();
            
            $table->unique(['company_id', 'type']);
        });

        // Payouts
        Schema::create('fin_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('recipient_id');
            $table->string('recipient_type');
            $table->bigInteger('amount');
            $table->string('currency')->default('KES');
            $table->string('bank_code');
            $table->string('account_number');
            $table->string('status')->default('pending');
            $table->integer('retry_count')->default(0);
            $table->text('last_error')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->string('flw_transfer_id')->nullable();
            $table->timestamps();
        });

        // Ledger Entries
        Schema::create('fin_ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id');
            $table->uuid('company_id');
            $table->integer('account_code');
            $table->string('reference_type');
            $table->uuid('reference_id');
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Webhook Events
        Schema::create('fin_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source');
            $table->string('event_id');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
            
            $table->unique(['source', 'event_id']);
        });
        // Payments (Inbound)
        Schema::create('fin_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('invoice_id');
            $table->string('flw_tx_id')->unique()->nullable();
            $table->string('tx_ref')->unique(); // Idempotency key
            $table->bigInteger('amount');
            $table->string('currency')->default('KES');
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_wallets');
        Schema::dropIfExists('fin_payouts');
        Schema::dropIfExists('fin_ledger_entries');
        Schema::dropIfExists('fin_payments');
        Schema::dropIfExists('fin_webhook_events');
    }
};
