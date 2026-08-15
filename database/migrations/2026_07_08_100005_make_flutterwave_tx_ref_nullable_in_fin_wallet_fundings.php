<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_wallet_fundings', function (Blueprint $table) {
            $table->string('flutterwave_tx_ref')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fin_wallet_fundings', function (Blueprint $table) {
            $table->string('flutterwave_tx_ref')->nullable(false)->change();
        });
    }
};
