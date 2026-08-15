<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_payouts', function (Blueprint $table) {
            $table->string('paystack_recipient_code')->nullable()->after('flw_transfer_id');
            $table->string('paystack_transfer_code')->nullable()->after('paystack_recipient_code');
        });
    }

    public function down(): void
    {
        Schema::table('fin_payouts', function (Blueprint $table) {
            $table->dropColumn(['paystack_recipient_code', 'paystack_transfer_code']);
        });
    }
};
