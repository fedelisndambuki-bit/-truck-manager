<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('transfer_number')->unique();
            $table->foreignId('material_id')->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->foreignId('source_stockpile_id')->constrained('stockpiles')->cascadeOnDelete();
            $table->foreignId('destination_stockpile_id')->constrained('stockpiles')->cascadeOnDelete();
            $table->string('vehicle_registration')->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('Pending'); // Pending, Approved, Completed, Rejected
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_stock_transfers');
    }
};
