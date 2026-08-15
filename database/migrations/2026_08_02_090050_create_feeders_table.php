<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('production_line_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->nullable(); // Vibrating, Apron, Belt
            $table->decimal('capacity_tph', 10, 2)->nullable();
            $table->decimal('feed_rate', 10, 2)->nullable();
            $table->string('status')->default('Idle');
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeders');
    }
};
