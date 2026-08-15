<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('method_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->string('title');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('risk_assessment_id')->nullable()->constrained('risk_assessments')->nullOnDelete();
            $table->text('related_activities')->nullable();
            $table->string('status')->default('Draft'); // Draft, Submitted, Under Review, Approved, Rejected, Revision Required, Archived
            $table->string('current_version')->default('1.0');
            $table->string('file_path')->nullable();
            $table->string('disk')->default('public');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('method_statements');
    }
};
