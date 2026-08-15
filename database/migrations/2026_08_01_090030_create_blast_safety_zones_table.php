<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blast_safety_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('blast_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('safety_radius', 8, 2)->nullable();
            $table->text('restricted_areas')->nullable();
            $table->text('evacuation_routes')->nullable();
            $table->text('assembly_points')->nullable();
            $table->boolean('warning_sirens')->default(false);
            $table->text('road_closures')->nullable();
            $table->text('access_control')->nullable();
            $table->boolean('clearance_confirmed')->default(false);
            $table->foreignId('clearance_confirmed_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_safety_zones');
    }
};
