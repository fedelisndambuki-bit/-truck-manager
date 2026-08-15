<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable(); // denormalized snapshot of the user's Spatie role at assignment time
            $table->string('status')->default('Active'); // Active, Suspended, Expired
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('can_download_documents')->default(true);
            $table->boolean('can_upload_documents')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_comment')->default(true);
            $table->boolean('can_view_financials')->default(false);
            $table->boolean('can_view_drawings')->default(true);
            $table->foreignId('invited_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_users');
    }
};
