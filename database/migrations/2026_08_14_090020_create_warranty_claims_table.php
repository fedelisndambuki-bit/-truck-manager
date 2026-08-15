<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('claim_number')->nullable()->unique();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->string('covered_component')->nullable();
            $table->text('issue_description')->nullable();
            $table->date('filed_at');
            $table->string('status')->default('Filed'); // Filed, Approved, Rejected, Repaired, Closed
            $table->string('supplier_contact')->nullable();
            $table->text('resolution')->nullable();
            $table->decimal('cost_recovered', 14, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
    }
};
