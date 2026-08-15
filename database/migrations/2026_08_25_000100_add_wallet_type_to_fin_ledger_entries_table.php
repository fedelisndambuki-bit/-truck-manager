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
        // reverseTransaction() used to re-infer wallet_type from account_code,
        // but the same account_code (e.g. ACC_DRIVER_LIABILITY) is reused
        // for lines with different wallet_types (expense pool vs driver
        // pool) by expense.settlement.driver_wallet, so reversal always
        // misattributed both legs to the same wallet. Storing the original
        // wallet_type removes the guesswork entirely.
        Schema::table('fin_ledger_entries', function (Blueprint $table) {
            $table->string('wallet_type')->nullable()->after('credit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fin_ledger_entries', function (Blueprint $table) {
            $table->dropColumn('wallet_type');
        });
    }
};
