<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type'); // budget_overrun_risk, declining_productivity, equipment_risk, schedule_slippage, safety_risk, material_waste_anomaly, high_performing_team, cost_saving_recommendation
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('severity')->default('Medium'); // Low, Medium, High, Critical
            $table->string('title');
            $table->text('narrative')->nullable();
            $table->json('facts')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};
