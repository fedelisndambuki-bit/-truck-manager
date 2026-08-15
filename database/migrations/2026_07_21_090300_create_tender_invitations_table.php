<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('tender_number')->nullable()->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->text('scope_description')->nullable();
            $table->date('closing_date')->nullable();
            $table->string('status')->default('Draft'); // Draft, Open, Closed, Awarded, Cancelled
            $table->decimal('estimated_value', 14, 2)->nullable();
            $table->unsignedBigInteger('awarded_bid_id')->nullable();
            $table->foreignId('award_recommended_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('award_approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_invitations');
    }
};
