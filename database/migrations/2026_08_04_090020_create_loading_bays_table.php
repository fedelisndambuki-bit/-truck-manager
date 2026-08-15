<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loading_bays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('extraction_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bay_code')->nullable()->unique();
            $table->string('name');
            $table->foreignId('stockpile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loader_equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->unsignedInteger('max_queue')->default(5);
            $table->string('status')->default('Available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loading_bays');
    }
};
