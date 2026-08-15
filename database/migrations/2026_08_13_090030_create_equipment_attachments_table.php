<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('attachment_code')->nullable()->unique();
            $table->string('name');
            $table->string('attachment_type'); // Bucket, Breaker, Ripper, Fork, Hydraulic Hammer, Quick Coupler, Other
            $table->string('serial_number')->nullable();
            $table->json('compatible_equipment_types')->nullable();
            $table->foreignId('current_equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->string('status')->default('Available'); // Available, In Use, Under Maintenance, Out of Service
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 14, 2)->nullable();
            $table->decimal('total_usage_hours', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_attachments');
    }
};
