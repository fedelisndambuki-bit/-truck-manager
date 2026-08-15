<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stockpile_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('reservation_number')->nullable()->unique();
            $table->foreignId('stockpile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('order_number')->nullable();
            $table->decimal('quantity_reserved', 12, 2);
            $table->date('reservation_date');
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('Reserved');
            $table->foreignId('reserved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stockpile_reservations');
    }
};
