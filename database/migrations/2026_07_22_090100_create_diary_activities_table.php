<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diary_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('daily_site_diary_id')->constrained()->cascadeOnDelete();
            $table->string('task');
            $table->string('area')->nullable();
            $table->time('start_time')->nullable();
            $table->time('finish_time')->nullable();
            $table->decimal('completion_pct', 5, 2)->default(0);
            $table->text('resources_used')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diary_activities');
    }
};
