<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->string('method'); // StraightLine, ReducingBalance, UnitsOfProduction, Custom
            $table->decimal('cost_basis', 14, 2);
            $table->decimal('residual_value', 14, 2)->default(0);
            $table->unsignedSmallInteger('useful_life_years')->nullable();
            $table->decimal('rate_percent', 5, 2)->nullable(); // ReducingBalance
            $table->string('units_basis')->nullable(); // e.g. "engine_hours" for UnitsOfProduction
            $table->decimal('total_expected_units', 14, 2)->nullable(); // UnitsOfProduction
            $table->date('start_date');
            $table->string('status')->default('Active'); // Active, Completed, Paused
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciation_schedules');
    }
};
