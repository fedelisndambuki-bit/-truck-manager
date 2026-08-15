<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create payment_settlements (Renamed from settlements to avoid conflict)
        if (!Schema::hasTable('payment_settlements')) {
            Schema::create('payment_settlements', function (Blueprint $table) {
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

        // 2. Create payment_settlement_items
        if (!Schema::hasTable('payment_settlement_items')) {
            Schema::create('payment_settlement_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('settlement_id')->constrained('payment_settlements')->cascadeOnDelete();
                $table->foreignId('transaction_id')->constrained('transactions');
                $table->decimal('gross_amount', 12, 2);
                $table->decimal('fees', 12, 2);
                $table->decimal('net_amount', 12, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settlement_items');
        Schema::dropIfExists('payment_settlements');
    }
};
