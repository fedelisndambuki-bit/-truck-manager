<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('supplier_number')->nullable()->unique()->after('id');
            $table->string('legal_name')->nullable()->after('company_name');
            $table->string('trading_name')->nullable()->after('legal_name');
            $table->string('category')->nullable()->after('trading_name');
            $table->string('registration_number')->nullable()->after('tax_number');
            $table->decimal('credit_limit', 14, 2)->nullable()->after('bank_branch');
            $table->string('payment_terms')->nullable()->after('credit_limit');
            $table->string('risk_rating')->default('Low')->after('quality_rating'); // Low, Medium, High
            $table->date('insurance_expiry')->nullable()->after('contract_end_date');
            $table->json('certificates')->nullable()->after('insurance_expiry');
            $table->json('licenses')->nullable()->after('certificates');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_number', 'legal_name', 'trading_name', 'category', 'registration_number',
                'credit_limit', 'payment_terms', 'risk_rating', 'insurance_expiry', 'certificates', 'licenses',
            ]);
        });
    }
};
