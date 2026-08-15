<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('report_name');
            $table->string('report_type')->nullable(); // e.g. financial, project, custom
            $table->string('format'); // pdf, csv, excel, word, json
            $table->foreignId('generated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
