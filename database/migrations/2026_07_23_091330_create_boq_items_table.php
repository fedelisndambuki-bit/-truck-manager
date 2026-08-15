<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_code_id')->nullable()->constrained('cost_codes')->nullOnDelete();
            $table->string('item_code');
            $table->text('description');
            $table->string('unit')->default('m2');
            $table->decimal('quantity', 14, 2)->default(0);
            $table->decimal('rate', 14, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('completed_quantity', 14, 2)->default(0);
            $table->decimal('variation_quantity', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boq_items');
    }
};
