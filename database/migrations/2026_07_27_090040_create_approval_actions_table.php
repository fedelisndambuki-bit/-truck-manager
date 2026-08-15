<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('approval_step_id')->constrained()->cascadeOnDelete();
            $table->string('approver_type')->nullable(); // user, role, department, department_head, supervisor_chain, manager, committee
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolved_via')->nullable(); // e.g. "supervisor_chain:staff.42", "role:Finance Director"
            $table->timestamp('assigned_at')->nullable();
            $table->string('action')->nullable(); // Approve, Reject, Return, RequestInfo, Delegate, Escalate, Reassign
            $table->text('comments')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['approval_step_id', 'action']);
            $table->index('approver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
