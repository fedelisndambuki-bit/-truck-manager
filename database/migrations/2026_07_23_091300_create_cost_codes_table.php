<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable(); // Site Establishment, Earthworks, Concrete, Steel, Masonry, Roofing, Electrical, Mechanical, Plumbing, Finishes, Equipment, Labour, Subcontractors, Overheads
            $table->foreignId('parent_id')->nullable()->constrained('cost_codes')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_codes');
    }
};
