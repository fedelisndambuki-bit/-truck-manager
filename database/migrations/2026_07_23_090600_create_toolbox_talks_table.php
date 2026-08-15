<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toolbox_talks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->foreignId('presenter_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->text('training_materials')->nullable();
            $table->text('comments')->nullable();
            $table->json('photos')->nullable();
            $table->timestamps();
        });

        Schema::create('toolbox_talk_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toolbox_talk_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('attendee_name')->nullable();
            $table->boolean('attended')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toolbox_talk_attendees');
        Schema::dropIfExists('toolbox_talks');
    }
};
