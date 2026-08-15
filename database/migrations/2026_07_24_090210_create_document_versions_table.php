<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('versionable_type');
            $table->unsignedBigInteger('versionable_id');
            $table->string('version_number');
            $table->unsignedInteger('revision_number')->default(0);
            $table->string('file_path')->nullable();
            $table->string('disk')->default('public');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_type')->nullable();
            $table->text('change_notes')->nullable();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['versionable_type', 'versionable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
