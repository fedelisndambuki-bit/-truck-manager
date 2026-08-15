<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('crushing_plant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('capacity_tph', 10, 2)->nullable();
            $table->string('status')->default('Idle'); // Running, Idle, Maintenance, Breakdown
            $table->foreignId('operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_lines');
    }
};
