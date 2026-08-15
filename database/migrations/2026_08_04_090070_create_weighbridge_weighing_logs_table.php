<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weighbridge_weighing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('dispatch_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('weighing_type');
            $table->decimal('weight', 12, 2);
            $table->foreignId('weighbridge_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->string('reason')->nullable();
            $table->text('comments')->nullable();
            $table->decimal('previous_weight', 12, 2)->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weighbridge_weighing_logs');
    }
};
