<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('run_number')->nullable()->unique();
            $table->foreignId('crushing_plant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('input_quantity', 12, 2)->default(0);
            $table->decimal('output_quantity', 12, 2)->default(0);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('finish_time')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('Draft'); // Draft, Submitted, Approved, Rejected, Completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_runs');
    }
};
