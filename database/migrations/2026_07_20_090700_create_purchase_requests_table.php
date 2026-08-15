<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('pr_number')->nullable()->unique();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->foreignId('requester_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->string('priority')->default('Medium'); // Low, Medium, High, Urgent
            $table->date('required_delivery_date')->nullable();
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected, Converted to PO
            $table->string('source')->default('Manual'); // Auto, Manual
            $table->foreignId('material_request_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
