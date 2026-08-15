<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_downtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('production_log_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crushing_plant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason'); // Mechanical Failure, Electrical Failure, Scheduled Maintenance, Fuel Shortage, Weather, Operator Issue, Material Shortage, Safety Incident
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->foreignId('reported_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_downtimes');
    }
};
