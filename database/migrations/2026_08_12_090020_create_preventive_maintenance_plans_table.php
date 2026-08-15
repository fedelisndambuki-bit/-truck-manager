<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preventive_maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_type'); // Calendar, EngineHours, Km, FuelUsage, ProductionCycles
            $table->decimal('interval_value', 10, 2); // days for Calendar, hours/km/litres/cycles for the rest
            $table->decimal('last_triggered_at_value', 12, 2)->nullable();
            $table->decimal('next_due_value', 12, 2)->nullable();
            $table->json('checklist')->nullable();
            $table->json('required_parts')->nullable(); // [{part_id, quantity}]
            $table->json('required_tools')->nullable();
            $table->json('required_skills')->nullable();
            $table->decimal('estimated_duration_hours', 6, 2)->nullable();
            $table->string('status')->default('Active'); // Active, Paused
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preventive_maintenance_plans');
    }
};
