<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stockpile_physical_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('count_number')->nullable()->unique();
            $table->foreignId('stockpile_id')->constrained()->cascadeOnDelete();
            $table->decimal('system_quantity', 12, 2);
            $table->decimal('physical_quantity', 12, 2);
            $table->string('survey_method');
            $table->foreignId('counted_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('count_date');
            $table->string('status')->default('Draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stockpile_physical_counts');
    }
};
