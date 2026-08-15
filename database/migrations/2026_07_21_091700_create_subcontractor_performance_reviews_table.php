<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcontractor_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcontractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('review_date');
            $table->unsignedTinyInteger('quality_of_work_rating')->default(0);
            $table->unsignedTinyInteger('timeliness_rating')->default(0);
            $table->unsignedTinyInteger('safety_rating')->default(0);
            $table->unsignedTinyInteger('communication_rating')->default(0);
            $table->unsignedTinyInteger('cost_control_rating')->default(0);
            $table->unsignedTinyInteger('issue_resolution_rating')->default(0);
            $table->decimal('overall_rating', 3, 2)->default(0);
            $table->foreignId('reviewed_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcontractor_performance_reviews');
    }
};
