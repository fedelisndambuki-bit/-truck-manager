<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fragmentation_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('blast_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('average_fragment_size', 8, 2)->nullable();
            $table->decimal('maximum_fragment', 8, 2)->nullable();
            $table->decimal('minimum_fragment', 8, 2)->nullable();
            $table->decimal('oversized_percentage', 5, 2)->nullable();
            $table->decimal('fines_percentage', 5, 2)->nullable();
            $table->string('crusher_feed_suitability')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fragmentation_analyses');
    }
};
