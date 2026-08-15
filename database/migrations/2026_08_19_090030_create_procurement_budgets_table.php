<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('budget_number')->nullable()->unique();
            $table->string('financial_year');
            $table->string('period_type')->default('Annual'); // Annual, Quarterly, Monthly
            $table->foreignId('business_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->constrained('cost_centres')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->decimal('approved_budget', 14, 2);
            $table->string('status')->default('Active'); // Active, Closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_budgets');
    }
};
