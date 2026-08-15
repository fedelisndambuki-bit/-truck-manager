<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_blast_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('blast_plan_id')->constrained()->cascadeOnDelete();
            $table->string('rock_fragmentation')->nullable();
            $table->decimal('oversized_rocks', 8, 2)->nullable();
            $table->boolean('fly_rock')->default(false);
            $table->text('fly_rock_notes')->nullable();
            $table->decimal('back_break', 8, 2)->nullable();
            $table->string('toe_condition')->nullable();
            $table->string('bench_stability')->nullable();
            $table->string('diggability')->nullable();
            $table->string('production_readiness')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_blast_analyses');
    }
};
