<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('breakdown_number')->nullable()->unique();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('maintenance_work_order_id')->nullable()->constrained('maintenance_work_orders')->nullOnDelete();
            $table->string('location')->nullable();
            $table->string('gps_coordinates')->nullable();
            $table->dateTime('occurred_at');
            $table->string('severity')->default('Medium'); // Low, Medium, High, Critical
            $table->text('description')->nullable();
            $table->json('photos')->nullable();
            $table->json('videos')->nullable();
            $table->text('root_cause')->nullable();
            $table->string('status')->default('Reported'); // Reported, Assigned, Resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breakdowns');
    }
};
