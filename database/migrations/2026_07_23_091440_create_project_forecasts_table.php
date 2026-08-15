<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('forecast_date');
            $table->decimal('forecast_final_cost', 15, 2)->default(0);
            $table->decimal('forecast_revenue', 15, 2)->default(0);
            $table->decimal('forecast_profit', 15, 2)->default(0);
            $table->date('estimated_completion_date')->nullable();
            $table->decimal('cost_performance_index', 6, 3)->default(1);
            $table->decimal('schedule_performance_index', 6, 3)->default(1);
            $table->text('notes')->nullable();
            $table->foreignId('forecasted_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_forecasts');
    }
};
