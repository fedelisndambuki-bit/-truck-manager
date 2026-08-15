<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blast_environmental_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('blast_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('ground_vibration', 8, 3)->nullable(); // mm/s (PPV)
            $table->decimal('air_overpressure', 8, 2)->nullable(); // dB(L)
            $table->decimal('dust_level', 8, 2)->nullable(); // µg/m³
            $table->decimal('noise_level', 8, 2)->nullable(); // dB(A)
            $table->string('weather_conditions')->nullable();
            $table->text('nearby_complaints')->nullable();
            $table->string('monitoring_equipment')->nullable();
            $table->string('compliance_status')->default('Compliant'); // Compliant, Exceeded, Under Review
            $table->dateTime('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_environmental_readings');
    }
};
