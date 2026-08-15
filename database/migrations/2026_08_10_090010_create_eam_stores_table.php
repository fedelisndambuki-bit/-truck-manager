<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eam_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('extraction_site_id')->nullable()->constrained('extraction_sites')->nullOnDelete();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('type')->default('Store'); // Store, Depot
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eam_stores');
    }
};
