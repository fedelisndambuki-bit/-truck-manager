<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blast_drill_holes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('blast_plan_id')->constrained()->cascadeOnDelete();
            $table->string('hole_number');
            $table->decimal('diameter', 8, 2)->nullable();
            $table->decimal('depth', 8, 2)->nullable();
            $table->decimal('inclination', 8, 2)->nullable();
            $table->foreignId('drill_rig_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('finish_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_drill_holes');
    }
};
