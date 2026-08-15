<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreignId('equipment_category_id')->nullable()->after('equipment_type')->constrained('equipment_categories')->nullOnDelete();
            $table->string('asset_number')->nullable()->unique()->after('equipment_code');
            $table->string('manufacturer')->nullable()->after('asset_number');
            $table->string('brand')->nullable()->after('manufacturer');
            $table->string('model')->nullable()->after('brand');
            $table->unsignedSmallInteger('year')->nullable()->after('model');
            $table->string('engine_number')->nullable()->after('serial_number');
            $table->string('vin')->nullable()->after('engine_number');
            $table->foreignId('department_id')->nullable()->after('company_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('extraction_site_id')->nullable()->after('department_id')->constrained('extraction_sites')->nullOnDelete();
            $table->foreignId('eam_store_id')->nullable()->after('extraction_site_id')->constrained('eam_stores')->nullOnDelete();
            $table->string('current_location')->nullable()->after('eam_store_id');
            $table->decimal('gps_lat', 10, 6)->nullable()->after('current_location');
            $table->decimal('gps_lng', 10, 6)->nullable()->after('gps_lat');
            $table->date('commission_date')->nullable()->after('purchase_date');
            $table->string('supplier')->nullable()->after('commission_date');
            $table->decimal('replacement_cost', 14, 2)->nullable()->after('purchase_cost');
            $table->unsignedSmallInteger('useful_life_years')->nullable()->after('replacement_cost');
            $table->decimal('residual_value', 14, 2)->nullable()->after('useful_life_years');
            $table->date('next_calibration_date')->nullable()->after('warranty_expiry');
            $table->json('covered_components')->nullable()->after('next_calibration_date');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipment_category_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('extraction_site_id');
            $table->dropConstrainedForeignId('eam_store_id');
            $table->dropColumn([
                'asset_number', 'manufacturer', 'brand', 'model', 'year', 'engine_number', 'vin',
                'current_location', 'gps_lat', 'gps_lng', 'commission_date', 'supplier',
                'replacement_cost', 'useful_life_years', 'residual_value', 'next_calibration_date',
                'covered_components',
            ]);
        });
    }
};
