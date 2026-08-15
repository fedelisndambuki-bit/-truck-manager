<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sampling_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('request_number')->nullable()->unique();
            $table->string('trigger');
            $table->string('priority')->default('Normal');
            $table->foreignId('requested_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->foreignId('assigned_technician_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('Pending');
            // Soft reference only (no FK constraint) - lab_samples doesn't exist yet at
            // this point in migration order, and lab_samples.sampling_request_id holds
            // the reverse, hard-constrained FK back to this table.
            $table->unsignedBigInteger('lab_sample_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sampling_requests');
    }
};
