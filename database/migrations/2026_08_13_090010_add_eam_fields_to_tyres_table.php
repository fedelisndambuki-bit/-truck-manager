<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tyres', function (Blueprint $table) {
            $table->decimal('cost_per_hour', 8, 2)->nullable()->after('purchase_cost');
            $table->unsignedInteger('expected_life_hours')->nullable()->after('cost_per_hour');
        });
    }

    public function down(): void
    {
        Schema::table('tyres', function (Blueprint $table) {
            $table->dropColumn(['cost_per_hour', 'expected_life_hours']);
        });
    }
};
