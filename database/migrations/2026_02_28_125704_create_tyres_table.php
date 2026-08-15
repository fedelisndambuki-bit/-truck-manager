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
        Schema::create('tyres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->string('brand');
            $table->string('model')->nullable();
            $table->string('size');
            $table->string('type')->default('New'); // New, Retread
            $table->string('position')->nullable(); // Axle position
            $table->string('status')->default('Active'); // Active, Discarded, Spare
            $table->date('installation_date')->nullable();
            $table->integer('installation_mileage')->nullable();
            $table->date('removal_date')->nullable();
            $table->integer('removal_mileage')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tyres');
    }
};
