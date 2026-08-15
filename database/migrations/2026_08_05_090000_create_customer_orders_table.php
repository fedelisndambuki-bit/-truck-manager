<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('order_number')->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('customer_site')->nullable();
            $table->string('sales_contract_number')->nullable();
            $table->string('quotation_number')->nullable();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_grade')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->string('priority')->default('Normal');
            $table->date('requested_delivery_date')->nullable();
            $table->string('status')->default('Draft');
            $table->foreignId('reservation_id')->nullable()->constrained('stockpile_reservations')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('planned_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
