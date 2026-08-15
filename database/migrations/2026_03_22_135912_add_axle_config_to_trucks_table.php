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
        Schema::table('trucks', function (Blueprint $table) {
            $table->string('axle_config')->default('2-axle')->after('truck_type'); // e.g., 2-axle, 3-axle, 4-axle
            $table->integer('tyre_count')->default(6)->after('axle_config'); // e.g., 4, 6, 10, 12, 14, 18, 22
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropColumn(['axle_config', 'tyre_count']);
        });
    }
};
