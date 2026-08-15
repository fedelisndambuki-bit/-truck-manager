<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('sample_number')->nullable()->unique();
            $table->string('sample_type');
            $table->foreignId('extraction_site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crushing_plant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stockpile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_grade')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('production_number')->nullable();
            $table->string('blast_number')->nullable();
            $table->foreignId('sampling_request_id')->nullable()->constrained('sampling_requests')->nullOnDelete();
            $table->foreignId('collected_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('collection_date')->nullable();
            $table->string('collection_time')->nullable();
            $table->string('collection_location')->nullable();
            $table->decimal('gps_lat', 10, 6)->nullable();
            $table->decimal('gps_lng', 10, 6)->nullable();
            $table->string('status')->default('Collected');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_samples');
    }
};
