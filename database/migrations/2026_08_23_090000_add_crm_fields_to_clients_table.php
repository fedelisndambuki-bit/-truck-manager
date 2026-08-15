<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('customer_number')->nullable()->unique()->after('id');
            $table->string('legal_name')->nullable()->after('company_name');
            $table->string('industry')->nullable();
            $table->string('customer_category')->nullable();
            $table->decimal('credit_limit', 14, 2)->nullable();
            $table->foreignId('assigned_account_manager_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('customer_since')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_account_manager_id');
            $table->dropColumn(['customer_number', 'legal_name', 'industry', 'customer_category', 'credit_limit', 'customer_since']);
        });
    }
};
