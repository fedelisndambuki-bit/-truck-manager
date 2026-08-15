<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_stock_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('count_number')->nullable()->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date')->nullable();
            $table->string('status')->default('Scheduled'); // Scheduled, In Progress, Completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_stock_counts');
    }
};
