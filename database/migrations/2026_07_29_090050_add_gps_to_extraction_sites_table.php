<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extraction_sites', function (Blueprint $table) {
            $table->decimal('gps_lat', 10, 7)->nullable()->after('location');
            $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
        });
    }

    public function down(): void
    {
        Schema::table('extraction_sites', function (Blueprint $table) {
            $table->dropColumn(['gps_lat', 'gps_lng']);
        });
    }
};
