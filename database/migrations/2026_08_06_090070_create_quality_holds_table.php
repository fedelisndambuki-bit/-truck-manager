<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('hold_number')->nullable()->unique();
            $table->foreignId('stockpile_id')->nullable()->constrained('stockpiles')->nullOnDelete();
            $table->foreignId('lab_sample_id')->nullable()->constrained('lab_samples')->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->string('reason');
            $table->foreignId('held_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->dateTime('held_at')->nullable();
            $table->string('status')->default('Active');
            $table->string('release_reason')->nullable();
            $table->foreignId('released_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->dateTime('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_holds');
    }
};
