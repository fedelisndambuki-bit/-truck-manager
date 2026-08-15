<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Which plan a new company is attached to at signup/trial start.
            // Previously registration set no plan at all, so trial companies'
            // PlanLimitService/getEffectiveModules() checks silently found
            // nothing and fell back to "no limit" / "no modules".
            $table->boolean('is_default')->default(false)->after('is_featured');
        });

        // Seed: mark the cheapest existing plan (Starter) as the default,
        // so registrations immediately have a real plan to attach.
        $cheapestPlanId = \Illuminate\Support\Facades\DB::table('plans')->orderBy('price')->value('id');

        if ($cheapestPlanId) {
            \Illuminate\Support\Facades\DB::table('plans')->where('id', $cheapestPlanId)->update(['is_default' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
