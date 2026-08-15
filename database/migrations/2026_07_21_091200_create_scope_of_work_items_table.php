<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scope_of_work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('scope_of_work_id')->constrained()->cascadeOnDelete();
            $table->string('deliverable');
            $table->string('activity')->nullable();
            $table->string('boq_reference')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit')->nullable();
            $table->decimal('unit_rate', 12, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status')->default('Pending'); // Pending, In Progress, Completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope_of_work_items');
    }
};
