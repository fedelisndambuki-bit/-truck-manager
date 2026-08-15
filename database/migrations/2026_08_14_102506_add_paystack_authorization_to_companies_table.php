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
        Schema::table('companies', function (Blueprint $table) {
            // Captured from the customer's first successful subscription
            // payment so later renewals can be auto-charged without asking
            // them to re-enter card details. Only card payments come back
            // "reusable" from Paystack — mobile money/bank transfer don't,
            // so those subscriptions fall back to manual renewal reminders.
            $table->string('paystack_authorization_code')->nullable();
            $table->boolean('paystack_authorization_reusable')->default(false);
            $table->string('paystack_card_last4', 4)->nullable();
            $table->string('paystack_card_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'paystack_authorization_code',
                'paystack_authorization_reusable',
                'paystack_card_last4',
                'paystack_card_type',
            ]);
        });
    }
};
