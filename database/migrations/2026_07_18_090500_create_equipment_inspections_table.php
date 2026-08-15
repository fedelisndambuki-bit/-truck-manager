<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('Daily'); // Daily, Pre-start, Post-operation
            $table->date('date');
            $table->foreignId('inspector_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('defects')->nullable();
            $table->json('photos')->nullable();
            $table->boolean('supervisor_approved')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inspections');
    }
};
