<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rfi_number')->unique();
            $table->string('subject');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('raised_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('priority')->default('Medium'); // Low, Medium, High, Urgent
            $table->date('due_date')->nullable();
            $table->string('status')->default('Open'); // Open, Answered, Closed, Overdue
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfis');
    }
};
