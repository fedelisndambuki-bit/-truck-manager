<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blast_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('blast_number')->nullable()->unique();
            $table->string('blast_name');
            $table->foreignId('extraction_site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quarry_face_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bench')->nullable();
            $table->foreignId('blast_engineer_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('date_planned');
            $table->string('priority')->default('Medium'); // Low, Medium, High, Urgent
            $table->string('status')->default('Draft'); // Draft, Submitted, Approved, Executed, Closed, Cancelled

            // Blast design (1:1 attributes of the plan itself)
            $table->string('drill_pattern')->nullable();
            $table->decimal('burden', 8, 2)->nullable();
            $table->decimal('spacing', 8, 2)->nullable();
            $table->decimal('bench_height', 8, 2)->nullable();
            $table->decimal('sub_drill', 8, 2)->nullable();

            // Scheduling
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->json('blast_team')->nullable();
            $table->json('equipment_required')->nullable();
            $table->foreignId('safety_officer_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('security_team')->nullable();
            $table->string('emergency_team')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_plans');
    }
};
