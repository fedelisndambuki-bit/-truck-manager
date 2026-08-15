<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_number')->unique();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_type')->default('Interim');
            $table->string('period_covered')->nullable();
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('retention_amount', 15, 2)->default(0);
            $table->decimal('advance_recovery_amount', 15, 2)->default(0);
            $table->decimal('previous_certified_amount', 15, 2)->default(0);
            $table->decimal('net_amount_due', 15, 2)->default(0);
            $table->decimal('approved_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status')->default('Draft');
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_certificates');
    }
};
