<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_site_diaries', function (Blueprint $table) {
            $table->string('diary_number')->nullable()->unique()->after('id');
            $table->foreignId('project_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            $table->string('phase')->nullable()->after('date');
            $table->string('shift')->default('Day')->after('phase'); // Day, Night, Overtime

            // Weather detail
            $table->decimal('temperature_high', 5, 2)->nullable()->after('weather');
            $table->decimal('temperature_low', 5, 2)->nullable()->after('temperature_high');
            $table->string('conditions')->nullable()->after('temperature_low');
            $table->decimal('rainfall_mm', 6, 2)->nullable()->after('conditions');
            $table->decimal('wind_speed_kph', 6, 2)->nullable()->after('rainfall_mm');
            $table->unsignedTinyInteger('humidity_pct')->nullable()->after('wind_speed_kph');
            $table->string('weather_impact')->nullable()->after('humidity_pct'); // None, Minor, Moderate, Severe
            $table->string('weather_source')->default('manual')->after('weather_impact'); // manual, auto
            $table->timestamp('weather_fetched_at')->nullable()->after('weather_source');

            // Workforce summary (workers_on_site already exists = present)
            $table->unsignedInteger('workers_absent')->default(0)->after('workers_on_site');
            $table->unsignedInteger('workers_late')->default(0)->after('workers_absent');
            $table->unsignedInteger('workers_overtime')->default(0)->after('workers_late');

            // Equipment summary
            $table->unsignedInteger('equipment_active_count')->default(0)->after('workers_overtime');
            $table->unsignedInteger('equipment_idle_count')->default(0)->after('equipment_active_count');
            $table->unsignedInteger('equipment_breakdown_count')->default(0)->after('equipment_idle_count');
            $table->decimal('equipment_utilization_pct', 5, 2)->nullable()->after('equipment_breakdown_count');

            // Material summary
            $table->unsignedInteger('material_deliveries_count')->default(0)->after('equipment_utilization_pct');
            $table->unsignedInteger('material_shortages_count')->default(0)->after('material_deliveries_count');

            // Subcontractor summary
            $table->unsignedInteger('subcontractors_on_site_count')->default(0)->after('material_shortages_count');
            $table->text('subcontractor_progress_notes')->nullable()->after('subcontractors_on_site_count');

            // Progress
            $table->decimal('progress_pct', 5, 2)->nullable()->after('subcontractor_progress_notes');

            // Supervisor notes
            $table->text('general_observations')->nullable()->after('work_summary');
            $table->text('challenges')->nullable()->after('general_observations');
            $table->text('risks')->nullable()->after('challenges');
            $table->text('recommendations')->nullable()->after('risks');
            $table->text('next_day_plan')->nullable()->after('recommendations');
            $table->text('additional_comments')->nullable()->after('next_day_plan');

            // Approval workflow
            $table->string('status')->default('Draft')->after('additional_comments'); // Draft, Submitted, Reviewed, Approved, Rejected, Locked
            $table->foreignId('submitted_by_id')->nullable()->after('status')->constrained('staff')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by_id');
            $table->foreignId('reviewed_by_id')->nullable()->after('submitted_at')->constrained('staff')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_id');
            $table->foreignId('approved_by_id')->nullable()->after('reviewed_at')->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_id');
            $table->foreignId('rejected_by_id')->nullable()->after('approved_at')->constrained('staff')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by_id');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->timestamp('locked_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('daily_site_diaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('submitted_by_id');
            $table->dropConstrainedForeignId('reviewed_by_id');
            $table->dropConstrainedForeignId('approved_by_id');
            $table->dropConstrainedForeignId('rejected_by_id');
            $table->dropColumn([
                'diary_number', 'phase', 'shift',
                'temperature_high', 'temperature_low', 'conditions', 'rainfall_mm', 'wind_speed_kph',
                'humidity_pct', 'weather_impact', 'weather_source', 'weather_fetched_at',
                'workers_absent', 'workers_late', 'workers_overtime',
                'equipment_active_count', 'equipment_idle_count', 'equipment_breakdown_count', 'equipment_utilization_pct',
                'material_deliveries_count', 'material_shortages_count',
                'subcontractors_on_site_count', 'subcontractor_progress_notes',
                'progress_pct',
                'general_observations', 'challenges', 'risks', 'recommendations', 'next_day_plan', 'additional_comments',
                'status', 'submitted_at', 'reviewed_at', 'approved_at', 'rejected_at', 'rejection_reason', 'locked_at',
            ]);
        });
    }
};
