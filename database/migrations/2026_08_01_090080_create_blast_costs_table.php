<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blast_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('blast_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('drilling_cost', 12, 2)->default(0);
            $table->decimal('explosive_cost', 12, 2)->default(0);
            $table->decimal('labour_cost', 12, 2)->default(0);
            $table->decimal('equipment_cost', 12, 2)->default(0);
            $table->decimal('fuel_cost', 12, 2)->default(0);
            $table->decimal('security_cost', 12, 2)->default(0);
            $table->decimal('environmental_monitoring_cost', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_costs');
    }
};
