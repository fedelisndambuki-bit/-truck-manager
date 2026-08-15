<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_payments', function (Blueprint $table) {
            $table->string('paystack_reference')->nullable()->after('flw_tx_id');
            $table->string('paystack_transaction_id')->nullable()->after('paystack_reference');
        });
    }

    public function down(): void
    {
        Schema::table('fin_payments', function (Blueprint $table) {
            $table->dropColumn(['paystack_reference', 'paystack_transaction_id']);
        });
    }
};
