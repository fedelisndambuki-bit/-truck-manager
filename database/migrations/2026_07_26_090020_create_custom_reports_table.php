<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('data_source'); // Projects, Sites, Labour, Equipment, Materials, Subcontractors, Safety, Quality, Finance, Documents
            $table->json('fields'); // whitelisted column keys selected by the user
            $table->json('filters')->nullable(); // [{field, operator, value}]
            $table->string('group_by')->nullable();
            $table->string('aggregate_function')->nullable(); // sum, count, avg, min, max
            $table->string('sort_by')->nullable();
            $table->string('sort_direction')->default('asc');
            $table->string('chart_type')->nullable(); // bar, line, doughnut, pie, null = table only
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_reports');
    }
};
