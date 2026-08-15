<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Transactions Table
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_id')->unique();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('KES');
                $table->string('payment_method')->default('mpesa');
                $table->string('gateway_reference')->nullable();
                $table->string('status')->default('pending');
                $table->boolean('settled')->default(false);
                $table->timestamps();
            });
        }

        // 2. M-Pesa Callbacks Log
        if (!Schema::hasTable('mpesa_callbacks')) {
            Schema::create('mpesa_callbacks', function (Blueprint $table) {
                $table->id();
                $table->string('checkout_request_id')->index();
                $table->string('merchant_request_id')->nullable();
                $table->integer('result_code');
                $table->string('result_desc')->nullable();
                $table->decimal('amount', 12, 2)->nullable();
                $table->string('phone_number')->nullable();
                $table->string('mpesa_receipt')->nullable()->index();
                $table->timestamp('transaction_date')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();
            });
        }

        // 3. Settlements
        if (!Schema::hasTable('settlements')) {
            Schema::create('settlements', function (Blueprint $table) {
                $table->id();
                $table->string('settlement_id')->unique();
                $table->timestamp('period_start');
                $table->timestamp('period_end');
                $table->decimal('gross_amount', 12, 2)->default(0);
                $table->decimal('fees_total', 12, 2)->default(0);
                $table->decimal('net_amount', 12, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Settlement Items
        if (!Schema::hasTable('settlement_items')) {
            Schema::create('settlement_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
                $table->foreignId('transaction_id')->constrained('transactions');
                $table->decimal('gross_amount', 12, 2);
                $table->decimal('fees', 12, 2);
                $table->decimal('net_amount', 12, 2);
                $table->timestamps();
            });
        }

        // 5. Ledger Entries
        if (!Schema::hasTable('ledger_entries')) {
            Schema::create('ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->string('account_type');
                $table->string('reference_id')->index();
                $table->decimal('debit', 12, 2)->default(0);
                $table->decimal('credit', 12, 2)->default(0);
                $table->string('currency', 3)->default('KES');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('settlement_items');
        // We might not want to drop existing tables if they were there before, 
        // but for this task we assume they are part of the module.
    }
};
