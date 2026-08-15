<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weighbridges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('extraction_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->decimal('capacity_tonnes', 10, 2)->nullable();
            $table->string('scale_type')->default('Dual Direction');
            $table->string('status')->default('Online');
            $table->date('calibration_due_date')->nullable();
            $table->date('certification_expiry')->nullable();
            $table->foreignId('current_operator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weighbridges');
    }
};
