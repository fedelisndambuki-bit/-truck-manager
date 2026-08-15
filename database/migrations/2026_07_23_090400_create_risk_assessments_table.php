<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('assessment_number')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('activity');
            $table->text('persons_at_risk')->nullable();
            $table->text('hazards')->nullable();
            $table->unsignedTinyInteger('likelihood')->default(3); // 1-5
            $table->unsignedTinyInteger('severity')->default(3); // 1-5
            $table->unsignedTinyInteger('risk_score')->default(9); // likelihood * severity
            $table->string('risk_level')->default('Medium'); // Low, Medium, High, Critical
            $table->text('control_measures')->nullable();
            $table->unsignedTinyInteger('residual_likelihood')->nullable();
            $table->unsignedTinyInteger('residual_severity')->nullable();
            $table->unsignedTinyInteger('residual_risk_score')->nullable();
            $table->string('residual_risk_level')->nullable();
            $table->foreignId('assessed_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('review_date')->nullable();
            $table->string('status')->default('Draft'); // Draft, Pending Approval, Approved, Under Review, Expired
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
