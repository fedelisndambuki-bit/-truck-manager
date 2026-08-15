<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'fuel_log_id')) {
                $table->foreignId('fuel_log_id')
                    ->nullable()
                    ->after('trip_id')
                    ->constrained('fuel_logs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'fuel_log_id')) {
                $table->dropForeignIdFor(\App\Models\FuelLog::class);
                $table->dropColumn('fuel_log_id');
            }
        });
    }
};
