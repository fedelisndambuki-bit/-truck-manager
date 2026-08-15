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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Route Info
            $table->string('route_name');
            $table->string('origin');
            $table->string('destination');
            $table->decimal('distance_km', 10, 2)->nullable();

            // Cargo Info
            $table->string('cargo_type');
            $table->decimal('cargo_weight', 10, 2);

            // Timing
            $table->dateTime('departure_time');
            $table->dateTime('expected_arrival');
            $table->dateTime('actual_arrival')->nullable();

            // Financials & Status
            $table->string('status')->default('Scheduled'); // Scheduled, In Progress, Completed, Cancelled
            $table->decimal('fuel_litres', 10, 2)->default(0);
            $table->decimal('fuel_cost', 15, 2)->default(0);
            $table->decimal('trip_expenses', 15, 2)->default(0);

            // Client Info
            $table->string('client_name');
            $table->string('client_contact')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
