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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_id')->unique();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('settlement_date');
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->string('trucks')->nullable(); // e.g., "2 trucks"
            $table->string('trips')->nullable(); // e.g., "5 trips"
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->enum('status', ['PENDING', 'PAID', 'PROCESSING'])->default('PENDING');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('settlement_date');
            $table->index('status');
            $table->index(['pay_period_start', 'pay_period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
