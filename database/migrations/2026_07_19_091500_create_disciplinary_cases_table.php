<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('type')->default('Verbal Warning'); // Verbal Warning, Written Warning, Suspension, Performance Improvement Plan, Termination
            $table->date('date');
            $table->text('description')->nullable();
            $table->foreignId('issued_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('Open'); // Open, Resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_cases');
    }
};
