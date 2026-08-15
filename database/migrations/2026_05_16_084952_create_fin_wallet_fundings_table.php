<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fin_wallet_fundings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('wallet_id');
            $table->bigInteger('amount'); // Minor units
            $table->string('currency')->default('KES');
            $table->string('status')->default('pending'); // pending, processing, successful, failed, cancelled
            $table->string('payment_method')->nullable();
            $table->string('flutterwave_tx_ref')->unique();
            $table->string('flutterwave_transaction_id')->nullable()->unique();
            $table->text('payment_link')->nullable();
            $table->unsignedBigInteger('funded_by_user_id');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('flutterwave_tx_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_wallet_fundings');
    }
};
