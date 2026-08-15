<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_wallet_fundings', function (Blueprint $table) {
            $table->string('paystack_reference')->nullable()->unique()->after('flutterwave_tx_ref');
            $table->string('paystack_transaction_id')->nullable()->unique()->after('paystack_reference');
        });
    }

    public function down(): void
    {
        Schema::table('fin_wallet_fundings', function (Blueprint $table) {
            $table->dropColumn(['paystack_reference', 'paystack_transaction_id']);
        });
    }
};
