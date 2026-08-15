<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('bin_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->default('');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['bin_location_id', 'material_id', 'batch_number'], 'bin_stock_bin_material_batch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bin_stock');
    }
};
