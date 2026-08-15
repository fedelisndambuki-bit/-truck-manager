<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('workflow_version')->default(1); // snapshot — live definition edits don't retroactively affect in-flight requests
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->string('reference_number')->unique();
            $table->string('title')->nullable();
            $table->string('current_node_key')->nullable();
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected, Returned, Cancelled, Escalated, Suspended
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->json('metadata')->nullable(); // snapshot of key source-record fields (e.g. amount) for condition evaluation + reporting
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
