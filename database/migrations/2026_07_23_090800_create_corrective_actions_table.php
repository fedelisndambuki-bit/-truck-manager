<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('action_number')->nullable();
            $table->foreignId('safety_incident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hazard_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('near_miss_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('safety_inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->foreignId('responsible_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('priority')->default('Medium'); // Low, Medium, High, Critical
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('status')->default('Open'); // Open, In Progress, Completed, Verified, Closed
            $table->text('evidence')->nullable();
            $table->string('evidence_file')->nullable();
            $table->foreignId('verified_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('closure_approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('closure_approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrective_actions');
    }
};
