<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crushing_plant_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('crushing_plant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('shift')->default('Day'); // Day, Night
            $table->decimal('throughput_tonnes', 12, 2)->default(0); // material fed into the crusher
            $table->decimal('output_tonnes', 12, 2)->default(0); // crushed product out
            $table->decimal('fuel_usage_litres', 10, 2)->nullable();
            $table->decimal('power_consumption_kwh', 10, 2)->nullable();
            $table->unsignedInteger('downtime_minutes')->default(0);
            $table->foreignId('operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crushing_plant_logs');
    }
};
