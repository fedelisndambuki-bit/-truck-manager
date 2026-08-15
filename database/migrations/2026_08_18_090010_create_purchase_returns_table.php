<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('return_number')->nullable()->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('goods_received_note_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason'); // Damaged, Wrong Item, Over Delivery, Under Delivery, Expired, Quality Failure
            $table->text('description')->nullable();
            $table->json('photos')->nullable();
            $table->string('replacement_status')->default('Pending'); // Pending, Replaced, Credited, Not Applicable
            $table->string('credit_note_reference')->nullable();
            $table->string('status')->default('Draft'); // Draft, Submitted, Approved, Rejected, Closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
