<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('source_type')->default('custom_report'); // existing_report, custom_report
            $table->string('source_key'); // report method label or custom_reports.id (as string)
            $table->foreignId('custom_report_id')->nullable()->constrained('custom_reports')->nullOnDelete();
            $table->string('frequency')->default('Weekly'); // Daily, Weekly, Monthly, Quarterly, Yearly
            $table->string('delivery_method')->default('Email'); // Email, PDF, Excel, Dashboard Link
            $table->string('format')->default('pdf'); // pdf, csv, excel, word, json
            $table->json('recipients')->nullable(); // list of email addresses
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
