<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('driver_wallet_id');
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('company_id');
            $table->bigInteger('amount');          // in cents; positive = credit, negative = debit
            $table->string('type');                // salary | bonus | allowance | advance | withdrawal
            $table->string('description')->nullable();
            $table->string('source_expense_id')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'created_at']);
            $table->index('driver_wallet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_wallet_transactions');
    }
};
