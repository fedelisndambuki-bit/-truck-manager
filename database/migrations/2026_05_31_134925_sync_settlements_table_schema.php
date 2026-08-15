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
        Schema::table('settlements', function (Blueprint $table) {
            // Drop old columns if they exist
            $columns = Schema::getColumnListing('settlements');
            
            if (in_array('trip_date', $columns)) $table->dropColumn('trip_date');
            if (in_array('route', $columns)) $table->dropColumn('route');
            if (in_array('income', $columns)) $table->dropColumn('income');
            if (in_array('fuel', $columns)) $table->dropColumn('fuel');
            if (in_array('other_expenses', $columns)) $table->dropColumn('other_expenses');

            // Add new columns if they don't exist
            if (!in_array('settlement_id', $columns)) $table->string('settlement_id')->unique()->after('id');
            if (!in_array('pay_period_start', $columns)) $table->date('pay_period_start')->nullable();
            if (!in_array('pay_period_end', $columns)) $table->date('pay_period_end')->nullable();
            if (!in_array('settlement_date', $columns)) $table->date('settlement_date')->nullable();
            if (!in_array('trucks', $columns)) $table->string('trucks')->nullable();
            if (!in_array('trips', $columns)) $table->string('trips')->nullable();
            if (!in_array('net_amount', $columns)) $table->decimal('net_amount', 12, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            // Basic reversal logic (optional for this fix)
        });
    }
};
