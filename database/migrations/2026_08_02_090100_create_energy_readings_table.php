<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('crushing_plant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('electricity_kwh', 12, 2)->nullable();
            $table->decimal('diesel_litres', 12, 2)->nullable();
            $table->decimal('energy_cost', 12, 2)->default(0);
            $table->decimal('peak_usage_kw', 10, 2)->nullable();
            $table->decimal('power_factor', 4, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_readings');
    }
};
