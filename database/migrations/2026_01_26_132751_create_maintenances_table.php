<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->string('maintenance_type'); // Service, Repair, Inspection
            $table->text('description');
            $table->string('service_provider');
            $table->decimal('cost', 15, 2);
            $table->integer('mileage_at_service');
            $table->integer('next_service_mileage')->nullable();
            $table->date('maintenance_date');
            $table->string('status')->default('Pending'); // Pending, Completed
            $table->string('invoice_upload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
