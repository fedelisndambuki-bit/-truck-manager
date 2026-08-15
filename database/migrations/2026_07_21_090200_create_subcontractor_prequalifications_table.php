<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcontractor_prequalifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('subcontractor_id')->constrained()->cascadeOnDelete();
            $table->text('financial_capacity_notes')->nullable();
            $table->text('technical_capacity_notes')->nullable();
            $table->text('equipment_notes')->nullable();
            $table->text('past_projects_notes')->nullable();
            $table->text('references_notes')->nullable();
            $table->text('certifications_notes')->nullable();
            $table->boolean('insurance_verified')->default(false);
            $table->text('safety_record_notes')->nullable();
            $table->text('quality_certifications_notes')->nullable();
            $table->text('legal_compliance_notes')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('approval_status')->default('Pending'); // Pending, Approved, Rejected
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcontractor_prequalifications');
    }
};
