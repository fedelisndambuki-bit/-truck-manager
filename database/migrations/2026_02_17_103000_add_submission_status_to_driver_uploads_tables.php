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
        Schema::table('driver_fuel_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('driver_fuel_receipts', 'status')) {
                $table->string('status')->default('pending')->after('receipt_path');
            }
            if (! Schema::hasColumn('driver_fuel_receipts', 'failure_reason')) {
                $table->string('failure_reason')->nullable()->after('status');
            }
        });

        Schema::table('driver_pods', function (Blueprint $table) {
            if (! Schema::hasColumn('driver_pods', 'status')) {
                $table->string('status')->default('pending')->after('notes');
            }
            if (! Schema::hasColumn('driver_pods', 'failure_reason')) {
                $table->string('failure_reason')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_fuel_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('driver_fuel_receipts', 'failure_reason')) {
                $table->dropColumn('failure_reason');
            }
            if (Schema::hasColumn('driver_fuel_receipts', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('driver_pods', function (Blueprint $table) {
            if (Schema::hasColumn('driver_pods', 'failure_reason')) {
                $table->dropColumn('failure_reason');
            }
            if (Schema::hasColumn('driver_pods', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
