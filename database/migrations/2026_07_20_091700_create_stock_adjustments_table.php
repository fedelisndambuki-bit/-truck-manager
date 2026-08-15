<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('adjustment_type')->default('Positive'); // Positive, Negative
            $table->string('reason')->default('Correction'); // Damaged, Theft, Correction, Other
            $table->decimal('quantity', 12, 2);
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('Pending'); // Pending, Approved
            $table->string('source')->default('Manual'); // Manual, PhysicalCount
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
