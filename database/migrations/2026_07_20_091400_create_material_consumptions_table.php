<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('allocation_id')->nullable()->constrained('material_allocations')->nullOnDelete();
            $table->string('activity')->nullable();
            $table->decimal('quantity_used', 12, 2);
            $table->foreignId('issued_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('received_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('date');
            $table->decimal('cost', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_consumptions');
    }
};
