<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('auto_renewal')->default(true);
            $table->json('reminders_sent')->nullable(); // track which reminders were sent
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->string('billing_country')->default('KE');
            $table->string('billing_currency')->default('KES');
            $table->string('tax_number')->nullable();
            $table->string('promo_code')->nullable();
            $table->string('flw_transaction_id')->nullable();
            $table->string('receipt_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['auto_renewal', 'reminders_sent']);
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn([
                'plan_id', 'billing_name', 'billing_email', 'billing_phone',
                'billing_country', 'billing_currency', 'tax_number', 'promo_code',
                'flw_transaction_id', 'receipt_url',
            ]);
        });
    }
};
