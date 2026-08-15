<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period_type')->default('Weekly');
            $table->date('date');
            $table->text('completed_activities')->nullable();
            $table->text('remaining_activities')->nullable();
            $table->unsignedTinyInteger('percent_complete')->default(0);
            $table->json('photos')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('supervisor_approved')->default(false);
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_updates');
    }
};
