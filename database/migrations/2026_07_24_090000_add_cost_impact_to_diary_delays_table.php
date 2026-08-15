<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diary_delays', function (Blueprint $table) {
            if (! Schema::hasColumn('diary_delays', 'cost_impact')) {
                // Estimated financial impact of the delay (prolongation costs, idle
                // plant/labour, etc.), entered by site staff when known — feeds the
                // Project Finance Cost Control page via Daily Site Diary integration.
                $table->decimal('cost_impact', 14, 2)->nullable()->after('duration_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('diary_delays', function (Blueprint $table) {
            if (Schema::hasColumn('diary_delays', 'cost_impact')) {
                $table->dropColumn('cost_impact');
            }
        });
    }
};
