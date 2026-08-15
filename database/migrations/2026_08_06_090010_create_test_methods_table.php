<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('method_code')->nullable()->unique();
            $table->string('method_name');
            $table->foreignId('quality_standard_id')->nullable()->constrained('quality_standards')->nullOnDelete();
            $table->json('applicable_products')->nullable();
            $table->text('testing_steps')->nullable();
            $table->text('required_equipment')->nullable();
            $table->text('required_materials')->nullable();
            $table->json('acceptance_criteria')->nullable();
            $table->string('revision')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_methods');
    }
};
