<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edms_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('document_number')->unique();
            $table->string('title');
            $table->string('category'); // Architectural Drawings, Structural Drawings, MEP Drawings, Civil Drawings, Contracts, BOQs, Method Statements, Inspection Reports, Quality Reports, Safety Plans, Permits, Meeting Minutes, Invoices, Certificates, Equipment Manuals, Supplier Documents, Subcontractor Documents, Training Records, Site Photos
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->string('department')->nullable();
            $table->foreignId('owner_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('current_version')->default('1.0');
            $table->string('status')->default('Draft'); // Draft, Submitted, Under Review, Approved, Rejected, Revision Required, Archived
            $table->unsignedInteger('revision_number')->default(0);
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->string('file_path')->nullable();
            $table->string('disk')->default('public');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->string('preview_type')->nullable(); // pdf, image, video, office, cad, other
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approval_date')->nullable();
            $table->date('review_date')->nullable();
            $table->unsignedInteger('retention_period_days')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edms_documents');
    }
};
