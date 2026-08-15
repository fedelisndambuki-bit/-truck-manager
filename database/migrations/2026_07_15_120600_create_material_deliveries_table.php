<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->date('delivered_at')->nullable();
            $table->string('purchase_order_ref')->nullable();
            $table->string('status')->default('Pending'); // Pending, Delivered
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_deliveries');
    }
};
