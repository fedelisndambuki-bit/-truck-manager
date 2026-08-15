<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('commitment_number')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // purchase_order, subcontract, equipment_hire, rental, labour_contract
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->decimal('invoiced_to_date', 15, 2)->default(0);
            $table->string('status')->default('Active'); // Active, Closed, Cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commitments');
    }
};
