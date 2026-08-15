<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_code')->nullable()->unique()->after('id');
            $table->string('category')->nullable()->after('name');
            $table->text('description')->nullable()->after('category');
            $table->string('priority')->nullable()->after('description'); // Low, Medium, High, Critical
            $table->string('current_phase')->nullable()->after('status');

            $table->string('country')->nullable()->after('site_address');
            $table->string('county')->nullable()->after('country');
            $table->string('city')->nullable()->after('county');
            $table->decimal('gps_lat', 10, 7)->nullable()->after('city');
            $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
            $table->string('project_zone')->nullable()->after('gps_lng');

            $table->date('tender_date')->nullable()->after('end_date');
            $table->date('contract_signing_date')->nullable()->after('tender_date');
            $table->date('warranty_end_date')->nullable()->after('end_date');
            $table->date('actual_completion_date')->nullable()->after('warranty_end_date');
            $table->unsignedInteger('estimated_duration_days')->nullable()->after('actual_completion_date');

            $table->decimal('estimated_cost', 14, 2)->nullable()->after('contract_value');
            $table->decimal('expected_revenue', 14, 2)->nullable()->after('estimated_cost');
            $table->decimal('retention_percent', 5, 2)->nullable()->after('expected_revenue');
            $table->decimal('advance_payment', 14, 2)->nullable()->after('retention_percent');
            $table->string('currency', 10)->nullable()->after('advance_payment');
            $table->string('payment_terms')->nullable()->after('currency');
            $table->string('cost_centre')->nullable()->after('payment_terms');
            $table->string('funding_source')->nullable()->after('cost_centre');

            $table->string('working_days')->nullable()->after('funding_source');
            $table->string('working_hours')->nullable()->after('working_days');
            $table->string('default_warehouse')->nullable()->after('working_hours');
            $table->string('default_equipment_pool')->nullable()->after('default_warehouse');
            $table->string('default_labour_pool')->nullable()->after('default_equipment_pool');
            $table->string('approval_workflow')->nullable()->after('default_labour_pool');
            $table->string('risk_level')->nullable()->after('approval_workflow'); // Low, Medium, High, Critical
            $table->boolean('weather_monitoring_enabled')->default(false)->after('risk_level');
            $table->boolean('is_archived')->default(false)->after('weather_monitoring_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'project_code', 'category', 'description', 'priority', 'current_phase',
                'country', 'county', 'city', 'gps_lat', 'gps_lng', 'project_zone',
                'tender_date', 'contract_signing_date', 'warranty_end_date', 'actual_completion_date',
                'estimated_duration_days', 'estimated_cost', 'expected_revenue', 'retention_percent',
                'advance_payment', 'currency', 'payment_terms', 'cost_centre', 'funding_source',
                'working_days', 'working_hours', 'default_warehouse', 'default_equipment_pool',
                'default_labour_pool', 'approval_workflow', 'risk_level', 'weather_monitoring_enabled',
                'is_archived',
            ]);
        });
    }
};
