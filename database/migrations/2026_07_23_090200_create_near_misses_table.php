<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('near_misses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('near_miss_number')->nullable();
            $table->date('date');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->text('potential_consequence')->nullable();
            $table->string('risk_level')->default('Medium'); // Low, Medium, High, Critical
            $table->foreignId('reported_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('corrective_action')->nullable();
            $table->string('status')->default('Open'); // Open, Under Review, Closed
            $table->json('photos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('near_misses');
    }
};
