<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blast_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('blast_plan_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // Blast Design, Blast Maps, Permits, Risk Assessments, Method Statements, Safety Plans, Photos, Videos, Drone Images, Survey Reports
            $table->string('file_path');
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_documents');
    }
};
