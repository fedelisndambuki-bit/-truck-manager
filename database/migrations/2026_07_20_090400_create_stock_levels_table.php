<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->default(''); // empty string = no batch tracking for this material
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity_on_hand', 12, 2)->default(0);
            $table->decimal('reserved_quantity', 12, 2)->default(0);
            $table->decimal('damaged_quantity', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['material_id', 'warehouse_id', 'batch_number'], 'stock_levels_material_warehouse_batch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
