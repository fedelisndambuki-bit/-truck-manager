<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('contract_number')->nullable()->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('contract_type')->default('Standard'); // Standard, Framework Agreement, Service Agreement
            $table->decimal('contract_value', 14, 2)->nullable();
            $table->string('currency')->default('KES');
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->json('service_levels')->nullable();
            $table->json('kpis')->nullable();
            $table->string('status')->default('Draft'); // Draft, Active, Expired, Terminated, Renewed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_contracts');
    }
};
