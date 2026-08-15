<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_recognitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('period_month');
            $table->string('method')->default('percentage_of_completion'); // percentage_of_completion, milestone, completed_contract
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->decimal('contract_revenue', 15, 2)->default(0);
            $table->decimal('earned_revenue', 15, 2)->default(0);
            $table->decimal('recognized_revenue', 15, 2)->default(0);
            $table->decimal('deferred_revenue', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_recognitions');
    }
};
