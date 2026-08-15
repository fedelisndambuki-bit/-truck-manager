<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_profit_centres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('dimension_type', ['BusinessUnit', 'Region', 'Branch', 'Quarry', 'Project', 'Customer', 'Product']);
            $table->string('dimension_label')->nullable();
            $table->foreignId('manager_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_profit_centres');
    }
};
