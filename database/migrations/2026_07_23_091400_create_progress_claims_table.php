<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('claim_number')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('previous_certified_amount', 15, 2)->default(0);
            $table->decimal('work_completed_amount', 15, 2)->default(0);
            $table->decimal('amount_claimed', 15, 2)->default(0);
            $table->decimal('retention_percent', 5, 2)->default(10);
            $table->decimal('retention_amount', 15, 2)->default(0);
            $table->decimal('vat_percent', 5, 2)->default(16);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('certified_amount', 15, 2)->default(0);
            $table->string('status')->default('Draft'); // Draft, Submitted, Certified, Paid, Rejected
            $table->foreignId('submitted_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('certified_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('certified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_claims');
    }
};
