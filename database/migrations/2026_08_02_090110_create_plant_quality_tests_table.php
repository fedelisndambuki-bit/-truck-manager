<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_quality_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('crushing_plant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sample_number')->nullable()->unique();
            $table->string('particle_size_distribution')->nullable();
            $table->decimal('moisture_percent', 5, 2)->nullable();
            $table->decimal('density', 8, 2)->nullable();
            $table->boolean('pass_fail')->default(true);
            $table->boolean('approved_for_dispatch')->default(false);
            $table->foreignId('tested_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_quality_tests');
    }
};
