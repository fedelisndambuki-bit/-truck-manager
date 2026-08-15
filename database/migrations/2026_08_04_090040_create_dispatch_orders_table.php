<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('dispatch_number')->nullable()->unique();
            $table->string('order_number')->nullable();

            // Customer / transport / vehicle / driver
            $table->foreignId('customer_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('transport_company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vehicle_registration');
            $table->string('trailer_number')->nullable();
            $table->string('fleet_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_national_id')->nullable();
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_expiry')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('driver_photo_path')->nullable();

            // Assignment
            $table->foreignId('weighbridge_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loading_bay_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loader_equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('loader_operator_id')->nullable()->constrained('staff')->nullOnDelete();

            // Product / stock
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_grade')->nullable();
            $table->foreignId('stockpile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('stockpile_reservations')->nullOnDelete();

            // Weights
            $table->decimal('expected_quantity', 12, 2)->nullable();
            $table->decimal('target_net_weight', 12, 2)->nullable();
            $table->decimal('tare_weight', 12, 2)->nullable();
            $table->decimal('gross_weight', 12, 2)->nullable();
            $table->decimal('net_weight', 12, 2)->nullable();
            $table->decimal('required_gross_weight', 12, 2)->nullable();
            $table->decimal('max_allowed_weight', 12, 2)->nullable();
            $table->decimal('min_allowed_weight', 12, 2)->nullable();
            $table->decimal('tolerance_tonnes', 8, 2)->default(0.20);

            $table->string('priority')->default('Normal');

            // Gate / security / inspection
            $table->timestamp('gate_in_at')->nullable();
            $table->decimal('gate_gps_lat', 10, 6)->nullable();
            $table->decimal('gate_gps_lng', 10, 6)->nullable();
            $table->string('gate')->nullable();
            $table->foreignId('security_officer_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->json('security_checklist')->nullable();
            $table->boolean('vehicle_inspection_passed')->nullable();
            $table->text('vehicle_inspection_notes')->nullable();

            // Tare
            $table->timestamp('tare_weighed_at')->nullable();
            $table->foreignId('tare_operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->boolean('tare_locked')->default(false);

            // Loading
            $table->timestamp('loading_instruction_generated_at')->nullable();
            $table->timestamp('loading_started_at')->nullable();
            $table->timestamp('loading_completed_at')->nullable();

            // Gross
            $table->timestamp('gross_weighed_at')->nullable();
            $table->foreignId('gross_operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('weather')->nullable();

            // Validation
            $table->decimal('variance', 12, 2)->nullable();
            $table->decimal('variance_percent', 8, 2)->nullable();
            $table->string('weight_status')->nullable();

            $table->string('status')->default('Checked In');
            $table->unsignedInteger('reweigh_count')->default(0);
            $table->json('axle_weights')->nullable();

            // Exit
            $table->timestamp('exit_at')->nullable();
            $table->string('exit_gate')->nullable();
            $table->foreignId('security_clearance_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('security_clearance_at')->nullable();
            $table->string('delivery_note_path')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('generated_by_id')->nullable()->constrained('staff')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('vehicle_registration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_orders');
    }
};
