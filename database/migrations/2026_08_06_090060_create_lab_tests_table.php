<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('test_number')->nullable()->unique();
            $table->foreignId('lab_sample_id')->constrained('lab_samples')->cascadeOnDelete();
            $table->foreignId('test_method_id')->nullable()->constrained('test_methods')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('lab_equipment_id')->nullable()->constrained('lab_equipment')->nullOnDelete();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->json('measured_values')->nullable();
            $table->json('expected_values')->nullable();
            $table->decimal('tolerance', 10, 4)->nullable();
            $table->string('result')->nullable();
            $table->string('status')->default('Pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
