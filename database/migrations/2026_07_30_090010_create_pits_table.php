<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('extraction_site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('depth', 10, 2)->nullable();
            $table->string('bench_level')->nullable();
            $table->string('material_type')->nullable();
            $table->decimal('reserve_estimate', 12, 2)->nullable();
            $table->decimal('current_production', 12, 2)->default(0);
            $table->string('safety_status')->default('Safe'); // Safe, Caution, Restricted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pits');
    }
};
