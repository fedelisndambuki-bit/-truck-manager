<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppe_issuances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('item'); // Helmet, Boots, Jacket, Harness, Gloves, Eye Protection, Ear Protection, Respirator
            $table->string('size')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('replacement_due_date')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('status')->default('Issued'); // Issued, Due for Replacement, Returned
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppe_issuances');
    }
};
