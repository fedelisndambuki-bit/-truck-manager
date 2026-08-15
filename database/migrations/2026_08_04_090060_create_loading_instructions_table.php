<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loading_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('loading_number')->nullable()->unique();
            $table->foreignId('dispatch_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loading_bay_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loader_equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('loader_operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('stockpile_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('target_net_weight', 12, 2)->nullable();
            $table->decimal('required_gross_weight', 12, 2)->nullable();
            $table->string('priority')->default('Normal');
            $table->longText('qr_code')->nullable();
            $table->string('status')->default('Waiting');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('loading_started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('loading_completed_at')->nullable();
            $table->string('rejected_reason')->nullable();
            $table->decimal('estimated_quantity_loaded', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loading_instructions');
    }
};
