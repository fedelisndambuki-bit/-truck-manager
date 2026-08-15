<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('shareable_type');
            $table->unsignedBigInteger('shareable_id');
            $table->string('shared_with_type'); // internal_staff, client, consultant, architect, engineer, subcontractor, supplier, external_auditor
            $table->string('shared_with_name')->nullable();
            $table->string('shared_with_email')->nullable();
            $table->string('access_level')->default('view'); // view, download
            $table->string('share_token')->unique()->nullable();
            $table->foreignId('shared_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['shareable_type', 'shareable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};
