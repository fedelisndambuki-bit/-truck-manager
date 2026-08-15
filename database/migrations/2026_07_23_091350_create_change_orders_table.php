<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('co_number')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('client_variation'); // client_variation, internal_co
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('reason')->nullable();
            $table->decimal('cost_impact', 15, 2)->default(0); // signed: positive = increase, negative = reduction
            $table->unsignedInteger('time_impact_days')->default(0);
            $table->string('status')->default('Draft'); // Draft, Submitted, Under Review, Approved, Rejected
            $table->foreignId('requested_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('supporting_document_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_orders');
    }
};
