<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stockpiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('extraction_site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable(); // e.g. "Stockyard A", "Crusher 2 Output Pile"
            $table->decimal('quantity_available', 12, 2)->default(0);
            $table->decimal('quantity_reserved', 12, 2)->default(0);
            $table->decimal('min_level', 12, 2)->default(0);
            $table->decimal('max_level', 12, 2)->nullable();
            $table->decimal('unit_value', 10, 2)->nullable(); // value per tonne, for stock valuation
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stockpiles');
    }
};
