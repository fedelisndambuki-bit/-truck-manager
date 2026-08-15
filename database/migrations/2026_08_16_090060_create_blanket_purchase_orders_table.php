<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blanket_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('bpo_number')->nullable()->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('currency')->default('KES');
            $table->decimal('maximum_value', 14, 2)->nullable();
            $table->decimal('maximum_quantity', 12, 2)->nullable();
            $table->decimal('released_value', 14, 2)->default(0);
            $table->decimal('released_quantity', 12, 2)->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('status')->default('Active'); // Active, Suspended, Expired, Closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blanket_purchase_orders');
    }
};
