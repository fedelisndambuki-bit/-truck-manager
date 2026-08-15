<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('case_number')->nullable();
            $table->foreignId('safety_incident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('visit_date');
            $table->string('hospital')->nullable();
            $table->string('doctor')->nullable();
            $table->text('treatment')->nullable();
            $table->text('diagnosis')->nullable();
            $table->date('return_to_work_date')->nullable();
            $table->text('medical_restrictions')->nullable();
            $table->string('medical_clearance')->default('Pending'); // Pending, Cleared, Restricted, Not Cleared
            $table->string('status')->default('Open'); // Open, Under Treatment, Closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_cases');
    }
};
