<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quarry_faces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('extraction_site_id')->constrained()->cascadeOnDelete();
            $table->string('face_code');
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('material_type')->nullable();
            $table->decimal('estimated_reserve', 12, 2)->nullable();
            $table->string('status')->default('Active'); // Active, Inactive, Closed
            $table->text('safety_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarry_faces');
    }
};
