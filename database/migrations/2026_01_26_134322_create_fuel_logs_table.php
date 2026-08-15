<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); // The Driver
            $table->string('fuel_station');
            $table->decimal('quantity_litres', 10, 2);
            $table->decimal('cost_per_litre', 10, 2);
            $table->decimal('total_cost', 15, 2);
            $table->integer('mileage_at_fueling');
            $table->date('date');
            $table->string('receipt_upload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_logs');
    }
};
