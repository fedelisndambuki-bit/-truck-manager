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
        Schema::table('ai_logs', function (Blueprint $table) {
            $table->json('modules_accessed')->nullable()->after('fleet_data');
            $table->json('providers_invoked')->nullable()->after('modules_accessed');
            $table->json('date_range_used')->nullable()->after('providers_invoked');
            $table->string('ai_model_used')->nullable()->after('date_range_used');
        });

        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('response_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_logs', function (Blueprint $table) {
            $table->dropColumn(['modules_accessed', 'providers_invoked', 'date_range_used', 'ai_model_used']);
        });

        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
