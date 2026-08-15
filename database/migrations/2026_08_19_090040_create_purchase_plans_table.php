<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('plan_number')->nullable()->unique();
            $table->string('financial_year');
            $table->string('period_type')->default('Annual'); // Annual, Quarterly, Monthly, Project-Based
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('extraction_site_id')->nullable()->constrained('extraction_sites')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->decimal('budget', 14, 2)->nullable();
            $table->decimal('estimated_quantity', 12, 2)->nullable();
            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->string('priority')->default('Medium'); // Low, Medium, High
            $table->string('status')->default('Draft'); // Draft, Pending, Approved, Rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_plans');
    }
};
