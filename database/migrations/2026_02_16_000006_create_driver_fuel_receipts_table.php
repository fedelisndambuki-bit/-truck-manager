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
        Schema::create('driver_fuel_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor')->nullable();
            $table->decimal('litres', 10, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('receipt_path');
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'captured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_fuel_receipts');
    }
};
