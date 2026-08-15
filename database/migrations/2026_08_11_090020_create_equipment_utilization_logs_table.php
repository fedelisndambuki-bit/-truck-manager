<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_utilization_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->decimal('engine_hours', 10, 2)->nullable();
            $table->decimal('operating_hours', 8, 2)->nullable();
            $table->decimal('idle_hours', 8, 2)->nullable();
            $table->decimal('travel_hours', 8, 2)->nullable();
            $table->decimal('standby_hours', 8, 2)->nullable();
            $table->unsignedInteger('trips')->nullable();
            $table->unsignedInteger('cycles')->nullable();
            $table->decimal('tonnes_moved', 10, 2)->nullable();
            $table->decimal('material_loaded', 10, 2)->nullable();
            $table->decimal('fuel_used_litres', 10, 2)->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['equipment_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_utilization_logs');
    }
};
