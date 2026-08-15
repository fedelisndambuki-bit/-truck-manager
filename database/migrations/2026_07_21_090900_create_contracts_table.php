<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('contract_number')->nullable()->unique();
            $table->foreignId('subcontractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tender_invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->text('scope_summary')->nullable();
            $table->decimal('contract_value', 14, 2);
            $table->decimal('retention_percent', 5, 2)->default(10);
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->decimal('performance_bond_amount', 14, 2)->nullable();
            $table->string('performance_bond_provider')->nullable();
            $table->boolean('insurance_required')->default(true);
            $table->string('status')->default('Draft'); // Draft, Active, Suspended, Completed, Terminated
            $table->date('signed_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
