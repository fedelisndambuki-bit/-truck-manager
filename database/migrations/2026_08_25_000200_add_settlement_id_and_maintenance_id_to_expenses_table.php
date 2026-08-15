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
        // Matches the existing trip_id/fuel_log_id precedent — SettlementObserver
        // and MaintenanceObserver were deduping/syncing their linked Expense by
        // parsing `description LIKE '%...#{id}%'`, which is both ambiguous
        // (id 1 matches id 17) and can't support an update-sync the way
        // FuelLogObserver::syncExpense() does via a real FK.
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('settlement_id')->nullable()->after('fuel_log_id')->constrained('settlements')->nullOnDelete();
            $table->foreignId('maintenance_id')->nullable()->after('settlement_id')->constrained('maintenances')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('settlement_id');
            $table->dropConstrainedForeignId('maintenance_id');
        });
    }
};
