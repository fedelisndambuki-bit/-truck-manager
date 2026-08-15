<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('movement_number')->unique();
            $table->date('date');
            $table->enum('movement_type', [
                'Receipt', 'Issue', 'Transfer', 'Return', 'Consumption', 'Adjustment', 'PutAway',
            ]);
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_bin_id')->nullable()->constrained('bin_locations')->nullOnDelete();
            $table->foreignId('destination_bin_id')->nullable()->constrained('bin_locations')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->foreignId('operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
