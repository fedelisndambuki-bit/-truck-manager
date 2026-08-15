<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telematics_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->string('source')->default('Manual'); // Manual, CAT-ProductLink, Komtrax, CareTrack, e-Service, JDLink, LiveLink
            $table->dateTime('recorded_at');
            $table->decimal('gps_lat', 10, 6)->nullable();
            $table->decimal('gps_lng', 10, 6)->nullable();
            $table->decimal('engine_hours', 10, 2)->nullable();
            $table->json('fault_codes')->nullable();
            $table->decimal('fuel_level', 5, 2)->nullable();
            $table->unsignedInteger('idle_minutes')->nullable();
            $table->decimal('speed_kph', 6, 2)->nullable();
            $table->string('geofence_status')->nullable(); // Inside, Outside, N/A
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telematics_readings');
    }
};
