<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weighbridge_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('extraction_site_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('direction')->default('Outbound'); // Inbound, Outbound
            $table->string('vehicle_registration')->nullable(); // free text — often third-party hire trucks
            $table->string('driver_name')->nullable();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('tare_weight', 10, 2)->nullable();
            $table->decimal('net_weight', 10, 2)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamp('gate_in_at')->nullable();
            $table->timestamp('gate_out_at')->nullable();
            $table->string('status')->default('Pending'); // Pending, Weighed, Rejected
            $table->boolean('is_manual_override')->default(false);
            $table->foreignId('operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['extraction_site_id', 'direction', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weighbridge_tickets');
    }
};
