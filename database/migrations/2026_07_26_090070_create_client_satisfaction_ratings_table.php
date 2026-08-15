<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No client-satisfaction data exists anywhere in the app (Client has no
     * rating/survey field) — this small table gives the Executive Dashboard's
     * "Client Satisfaction" KPI a real, recordable data source instead of a
     * fabricated number.
     */
    public function up(): void
    {
        Schema::create('client_satisfaction_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score'); // 1-5
            $table->text('comment')->nullable();
            $table->foreignId('rated_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('rated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_satisfaction_ratings');
    }
};
