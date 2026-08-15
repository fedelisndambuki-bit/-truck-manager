<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('grn_number')->nullable()->unique();
            $table->foreignId('material_delivery_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('received_quantity', 12, 2)->default(0);
            $table->decimal('accepted_quantity', 12, 2)->default(0);
            $table->decimal('rejected_quantity', 12, 2)->default(0);
            $table->decimal('damaged_quantity', 12, 2)->default(0);
            $table->text('inspection_notes')->nullable();
            $table->boolean('quality_approved')->default(false);
            $table->foreignId('inspected_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('Pending'); // Pending, Approved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_received_notes');
    }
};
