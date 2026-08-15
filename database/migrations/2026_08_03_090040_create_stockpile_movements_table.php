<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stockpile_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('movement_number')->nullable()->unique();
            $table->string('movement_type');
            $table->date('date');
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->foreignId('source_stockpile_id')->nullable()->constrained('stockpiles')->nullOnDelete();
            $table->foreignId('destination_stockpile_id')->nullable()->constrained('stockpiles')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('vehicle_registration')->nullable();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('reference');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stockpile_movements');
    }
};
