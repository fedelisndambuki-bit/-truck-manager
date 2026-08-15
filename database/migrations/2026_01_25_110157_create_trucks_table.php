<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('truck_type'); // Trailer, Lorry, Pickup
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->year('year')->nullable();
            $table->string('engine_number')->unique()->nullable();
            $table->string('chassis_number')->unique()->nullable();
            $table->string('fuel_type')->default('Diesel');
            $table->decimal('fuel_consumption', 8, 2)->nullable(); // km/l
            $table->integer('current_mileage')->default(0);
            $table->decimal('capacity_tons', 8, 2)->nullable();
            $table->string('status')->default('Available'); // Available, In Transit, Maintenance
            $table->string('insurance_provider')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('road_license_expiry')->nullable();
            $table->date('purchase_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
