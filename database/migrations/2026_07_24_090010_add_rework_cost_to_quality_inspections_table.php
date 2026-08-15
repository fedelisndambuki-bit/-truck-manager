<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_inspections', function (Blueprint $table) {
            if (! Schema::hasColumn('quality_inspections', 'rework_cost')) {
                // Cost of rework/remediation for a failed inspection, entered by the
                // inspector/QS when known — feeds the Project Finance Cost Control
                // page via Quality Control integration.
                $table->decimal('rework_cost', 14, 2)->nullable()->after('result');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quality_inspections', function (Blueprint $table) {
            if (Schema::hasColumn('quality_inspections', 'rework_cost')) {
                $table->dropColumn('rework_cost');
            }
        });
    }
};
