<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('work_order_number')->nullable()->unique();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('workshop_id')->nullable()->constrained('workshops')->nullOnDelete();
            $table->foreignId('workshop_bay_id')->nullable()->constrained('workshop_bays')->nullOnDelete();
            $table->foreignId('preventive_maintenance_plan_id')->nullable()->constrained('preventive_maintenance_plans')->nullOnDelete();
            $table->string('work_order_type')->default('Corrective'); // Preventive, Corrective, Breakdown, Inspection-Followup, Other
            $table->string('priority')->default('Medium'); // Low, Medium, High, Critical
            $table->string('status')->default('Created'); // Created, Assigned, In Progress, Waiting Parts, Completed, Closed
            $table->text('description')->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('actual_hours', 6, 2)->nullable();
            $table->json('required_parts')->nullable(); // [{part_id, quantity}]
            $table->decimal('labor_cost', 12, 2)->nullable();
            $table->decimal('outside_services_cost', 12, 2)->nullable();
            $table->decimal('parts_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_work_orders');
    }
};
