<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->decimal('on_time_delivery_score', 4, 1)->nullable();
            $table->decimal('quality_score', 4, 1)->nullable();
            $table->decimal('pricing_score', 4, 1)->nullable();
            $table->decimal('responsiveness_score', 4, 1)->nullable();
            $table->decimal('compliance_score', 4, 1)->nullable();
            $table->decimal('overall_score', 4, 1)->nullable();
            $table->text('comments')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_reviews');
    }
};
