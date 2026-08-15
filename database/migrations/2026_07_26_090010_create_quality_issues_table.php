<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('issue_number')->unique();
            $table->string('type'); // NCR, CAPA, Defect, Punch List
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quality_inspection_id')->nullable()->constrained('quality_inspections')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->default('Medium'); // Low, Medium, High, Critical
            $table->string('status')->default('Open'); // Open, In Progress, Closed, Verified
            $table->foreignId('raised_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_issues');
    }
};
