<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crushers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('production_line_id')->constrained()->cascadeOnDelete();
            $table->string('crusher_code')->nullable()->unique();
            $table->string('type'); // Primary Jaw Crusher, Secondary Cone Crusher, Impact Crusher, VSI, Hammer Crusher
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->decimal('capacity_tph', 10, 2)->nullable();
            $table->decimal('current_throughput', 10, 2)->nullable();
            $table->decimal('operating_hours', 10, 2)->default(0);
            $table->string('wear_status')->nullable(); // Good, Monitor, Replace Soon, Critical
            $table->string('status')->default('Idle'); // Running, Idle, Maintenance, Breakdown
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crushers');
    }
};
