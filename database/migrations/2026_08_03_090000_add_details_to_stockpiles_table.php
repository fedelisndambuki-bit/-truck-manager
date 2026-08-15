<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stockpiles', function (Blueprint $table) {
            $table->string('stockpile_code')->nullable()->unique()->after('id');
            $table->string('stockyard')->nullable()->after('label');
            $table->string('location_type')->default('Stockyard')->after('stockyard');
            $table->decimal('gps_lat', 10, 6)->nullable()->after('location_type');
            $table->decimal('gps_lng', 10, 6)->nullable()->after('gps_lat');
            $table->string('status')->default('Active')->after('unit_value');
            $table->foreignId('crushing_plant_id')->nullable()->after('material_id')->constrained()->nullOnDelete();
            $table->string('quality_status')->default('Approved')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('stockpiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('crushing_plant_id');
            $table->dropColumn(['stockpile_code', 'stockyard', 'location_type', 'gps_lat', 'gps_lng', 'status', 'quality_status']);
        });
    }
};
