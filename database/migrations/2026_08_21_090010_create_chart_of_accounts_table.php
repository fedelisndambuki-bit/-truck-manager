<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('account_code')->unique();
            $table->string('name');
            $table->enum('type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense', 'CostOfSales']);
            $table->foreignId('parent_account_id')->nullable()->constrained('fin_chart_of_accounts')->nullOnDelete();
            $table->enum('normal_balance', ['Debit', 'Credit']);
            $table->foreignId('cost_centre_id')->nullable()->constrained('cost_centres')->nullOnDelete();
            $table->foreignId('profit_centre_id')->nullable()->constrained('fin_profit_centres')->nullOnDelete();
            $table->string('currency', 3)->default('KES');
            $table->string('tax_code')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_chart_of_accounts');
    }
};
