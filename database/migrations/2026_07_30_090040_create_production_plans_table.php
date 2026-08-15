<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('plan_number')->unique();
            $table->foreignId('extraction_site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crushing_plant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('target_quantity', 12, 2)->default(0);
            $table->date('planned_date');
            $table->string('priority')->default('Medium'); // Low, Medium, High, Urgent
            $table->foreignId('supervisor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->json('assigned_crew')->nullable();
            $table->string('status')->default('Planned'); // Planned, In Progress, Completed, Cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plans');
    }
};
