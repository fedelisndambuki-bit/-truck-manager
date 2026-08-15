<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('calibration_number')->nullable()->unique();
            $table->foreignId('lab_equipment_id')->constrained('lab_equipment')->cascadeOnDelete();
            $table->date('calibration_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('performed_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('certificate_path')->nullable();
            $table->string('status')->default('Valid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibrations');
    }
};
