<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hazards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('hazard_number')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('hazard_type');
            $table->text('description');
            $table->string('likelihood')->default('Possible'); // Rare, Unlikely, Possible, Likely, Almost Certain
            $table->string('impact')->default('Moderate'); // Negligible, Minor, Moderate, Major, Catastrophic
            $table->string('risk_rating')->default('Medium'); // Low, Medium, High, Critical
            $table->text('existing_controls')->nullable();
            $table->text('recommended_controls')->nullable();
            $table->foreignId('responsible_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('target_date')->nullable();
            $table->string('status')->default('Open'); // Open, In Progress, Controlled, Closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hazards');
    }
};
