<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('drawing_number')->unique();
            $table->string('type')->default('Drawing'); // Drawing, Shop Drawing
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->string('discipline')->nullable(); // Architectural, Structural, MEP, Civil
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('Draft'); // Draft, Submitted, Under Review, Approved, Rejected, Revision Required, Archived
            $table->string('current_version')->default('1.0');
            $table->string('file_path')->nullable();
            $table->string('disk')->default('public');
            $table->string('preview_type')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consultant_approved_at')->nullable();
            $table->timestamp('client_approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawings');
    }
};
