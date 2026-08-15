<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('site_code')->nullable()->unique()->after('id');
            $table->string('current_phase')->nullable()->after('name');
            $table->text('description')->nullable()->after('current_phase');
            $table->string('priority')->nullable()->after('description'); // Low, Medium, High, Critical
            $table->unsignedTinyInteger('progress_percent')->default(0)->after('priority');

            $table->string('country')->nullable()->after('location');
            $table->string('county')->nullable()->after('country');
            $table->string('city')->nullable()->after('county');
            $table->string('nearest_landmark')->nullable()->after('city');
            $table->decimal('site_size', 10, 2)->nullable()->after('gps_lng');
            $table->string('site_size_unit')->nullable()->after('site_size'); // Acres, Hectares
            $table->decimal('elevation', 8, 2)->nullable()->after('site_size_unit');

            $table->date('start_date')->nullable()->after('elevation');
            $table->date('expected_completion_date')->nullable()->after('start_date');

            $table->string('working_days')->nullable()->after('expected_completion_date');
            $table->string('working_hours')->nullable()->after('working_days');
            $table->string('shift_pattern')->nullable()->after('working_hours');
            $table->boolean('night_shift_enabled')->default(false)->after('shift_pattern');
            $table->unsignedInteger('max_workforce')->nullable()->after('night_shift_enabled');
            $table->string('default_warehouse')->nullable()->after('max_workforce');
            $table->string('default_equipment_pool')->nullable()->after('default_warehouse');
            $table->boolean('weather_monitoring_enabled')->default(false)->after('default_equipment_pool');
            $table->string('internet_availability')->nullable()->after('weather_monitoring_enabled');
            $table->string('power_source')->nullable()->after('internet_availability');
            $table->string('water_source')->nullable()->after('power_source');

            $table->string('emergency_assembly_point')->nullable()->after('water_source');
            $table->string('nearest_hospital')->nullable()->after('emergency_assembly_point');
            $table->boolean('fire_equipment_available')->default(false)->after('nearest_hospital');
            $table->string('first_aid_station')->nullable()->after('fire_equipment_available');
            $table->text('emergency_contacts')->nullable()->after('first_aid_station');
            $table->string('safety_risk_level')->nullable()->after('emergency_contacts'); // Low, Medium, High, Critical
            $table->text('ppe_requirements')->nullable()->after('safety_risk_level');
            $table->text('restricted_zones')->nullable()->after('ppe_requirements');
            $table->text('visitor_policy')->nullable()->after('restricted_zones');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'site_code', 'current_phase', 'description', 'priority', 'progress_percent',
                'country', 'county', 'city', 'nearest_landmark', 'site_size', 'site_size_unit', 'elevation',
                'start_date', 'expected_completion_date',
                'working_days', 'working_hours', 'shift_pattern', 'night_shift_enabled', 'max_workforce',
                'default_warehouse', 'default_equipment_pool', 'weather_monitoring_enabled',
                'internet_availability', 'power_source', 'water_source',
                'emergency_assembly_point', 'nearest_hospital', 'fire_equipment_available',
                'first_aid_station', 'emergency_contacts', 'safety_risk_level', 'ppe_requirements',
                'restricted_zones', 'visitor_policy',
            ]);
        });
    }
};
