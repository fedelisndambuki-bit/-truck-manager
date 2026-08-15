<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blast_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('blast_plan_id')->constrained()->cascadeOnDelete();
            $table->date('actual_date')->nullable();
            $table->time('actual_time')->nullable();
            $table->foreignId('responsible_engineer_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('shot_firer_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('weather_conditions')->nullable();
            $table->decimal('explosives_used', 12, 2)->nullable();
            $table->integer('detonators_used')->nullable();
            $table->text('initiation_sequence')->nullable();
            $table->unsignedInteger('blast_duration_seconds')->nullable();
            $table->string('blast_outcome')->nullable(); // Successful, Partial, Misfire, Delayed
            $table->string('photo_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('safety_incident_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_executions');
    }
};
