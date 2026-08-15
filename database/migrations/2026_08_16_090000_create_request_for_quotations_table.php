<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rfq_number')->nullable()->unique();
            $table->foreignId('purchase_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->date('closing_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->json('required_documents')->nullable();
            $table->text('terms')->nullable();
            $table->text('conditions')->nullable();
            $table->json('evaluation_criteria')->nullable();
            $table->string('status')->default('Draft'); // Draft, Open, Closed, Awarded, Cancelled
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_for_quotations');
    }
};
