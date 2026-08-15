<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('action_number')->nullable()->unique();
            $table->foreignId('non_conformance_id')->constrained('non_conformances')->cascadeOnDelete();
            $table->foreignId('responsible_person_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->text('verification_notes')->nullable();
            $table->text('effectiveness_review')->nullable();
            $table->string('status')->default('Open');
            $table->foreignId('closure_approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->dateTime('closure_approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_corrective_actions');
    }
};
