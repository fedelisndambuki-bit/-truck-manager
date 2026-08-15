<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proof_of_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('dispatch_order_id')->constrained()->cascadeOnDelete();
            $table->string('customer_signature_path')->nullable();
            $table->string('driver_signature_path')->nullable();
            $table->decimal('gps_lat', 10, 6)->nullable();
            $table->decimal('gps_lng', 10, 6)->nullable();
            $table->decimal('delivered_quantity', 12, 2)->nullable();
            $table->timestamp('delivery_time')->nullable();
            $table->string('receiver_name')->nullable();
            $table->text('comments')->nullable();
            $table->string('status')->default('Delivered');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proof_of_deliveries');
    }
};
