<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_health_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->dateTime('recorded_at');
            $table->decimal('engine_temp', 6, 2)->nullable();
            $table->decimal('oil_pressure', 6, 2)->nullable();
            $table->decimal('hydraulic_pressure', 6, 2)->nullable();
            $table->decimal('coolant_temp', 6, 2)->nullable();
            $table->decimal('battery_voltage', 5, 2)->nullable();
            $table->unsignedInteger('rpm')->nullable();
            $table->decimal('engine_hours', 10, 2)->nullable();
            $table->string('transmission_status')->nullable();
            $table->decimal('fuel_level', 5, 2)->nullable();
            $table->decimal('def_level', 5, 2)->nullable();
            $table->decimal('hydraulic_oil_level', 5, 2)->nullable();
            $table->json('error_codes')->nullable();
            $table->unsignedTinyInteger('health_score')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_health_readings');
    }
};
