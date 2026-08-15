<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_conformances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ncr_number')->nullable()->unique();
            $table->string('source')->nullable();
            $table->foreignId('lab_sample_id')->nullable()->constrained('lab_samples')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('severity')->default('Minor');
            $table->text('root_cause')->nullable();
            $table->text('immediate_action')->nullable();
            $table->foreignId('responsible_person_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('Open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_conformances');
    }
};
