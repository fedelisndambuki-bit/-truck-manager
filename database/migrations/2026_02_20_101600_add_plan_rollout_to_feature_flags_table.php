<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_flags', function (Blueprint $table) {
            if (! Schema::hasColumn('feature_flags', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->after('company_id')->constrained('plans')->nullOnDelete();
            }

            if (! Schema::hasColumn('feature_flags', 'rollout_percentage')) {
                $table->unsignedTinyInteger('rollout_percentage')->default(100)->after('enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feature_flags', function (Blueprint $table) {
            if (Schema::hasColumn('feature_flags', 'plan_id')) {
                $table->dropConstrainedForeignId('plan_id');
            }

            if (Schema::hasColumn('feature_flags', 'rollout_percentage')) {
                $table->dropColumn('rollout_percentage');
            }
        });
    }
};
