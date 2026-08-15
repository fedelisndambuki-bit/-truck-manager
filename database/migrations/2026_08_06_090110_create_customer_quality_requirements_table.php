<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_quality_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('max_dust_percent', 6, 2)->nullable();
            $table->decimal('min_strength', 10, 2)->nullable();
            $table->json('particle_size_distribution')->nullable();
            $table->decimal('moisture_limit', 6, 2)->nullable();
            $table->text('custom_testing')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_quality_requirements');
    }
};
