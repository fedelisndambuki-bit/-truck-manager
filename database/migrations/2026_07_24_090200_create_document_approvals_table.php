<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->unsignedInteger('step_order')->default(1);
            $table->string('approver_role')->nullable(); // Site Engineer, Project Manager, Construction Manager, Consultant, Client Representative, Managing Director
            $table->foreignId('approver_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected, Revision Required
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_approvals');
    }
};
