<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('extraction_site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('logged_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('date');
            $table->string('material_type');
            $table->decimal('quantity_tonnes', 12, 2);
            $table->string('shift')->default('Day'); // Day, Night
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_logs');
    }
};
