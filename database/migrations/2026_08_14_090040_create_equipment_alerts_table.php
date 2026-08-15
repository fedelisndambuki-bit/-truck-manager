<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type'); // maintenance_due, breakdown_reported, inspection_overdue, warranty_expiring, low_spare_parts, fuel_theft_detected, equipment_offline, engine_fault_detected, calibration_due
            $table->string('severity')->default('Medium'); // Low, Medium, High, Critical
            $table->text('message');
            $table->dateTime('triggered_at');
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_alerts');
    }
};
