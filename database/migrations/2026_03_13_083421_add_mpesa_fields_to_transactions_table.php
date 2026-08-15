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
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'checkout_request_id')) {
                $table->string('checkout_request_id')->nullable()->after('gateway_reference');
            }
            if (!Schema::hasColumn('transactions', 'merchant_request_id')) {
                $table->string('merchant_request_id')->nullable()->after('checkout_request_id');
            }
            if (!Schema::hasColumn('transactions', 'mpesa_receipt')) {
                $table->string('mpesa_receipt')->nullable()->after('merchant_request_id');
            }
            if (!Schema::hasColumn('transactions', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('mpesa_receipt');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['checkout_request_id', 'merchant_request_id', 'mpesa_receipt', 'phone_number']);
        });
    }
};
