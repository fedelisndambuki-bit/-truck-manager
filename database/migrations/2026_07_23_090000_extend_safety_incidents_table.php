<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safety_incidents', function (Blueprint $table) {
            $table->string('incident_number')->nullable()->after('id');
            $table->time('time')->nullable()->after('date');
            $table->foreignId('project_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            $table->string('location')->nullable()->after('project_id');
            $table->string('injured_person_name')->nullable()->after('reported_by_id');
            $table->string('employment_type')->nullable()->after('injured_person_name'); // Staff, Subcontractor, Visitor, Public
            $table->string('incident_category')->nullable()->after('severity');
            $table->string('injury_classification')->nullable()->after('incident_category'); // Lost Time Injury, Medical Treatment Case, First Aid Case, No Injury, Property Damage Only
            $table->text('witnesses')->nullable()->after('description');
            $table->text('immediate_response')->nullable()->after('witnesses');
            $table->string('medical_treatment')->nullable()->after('immediate_response'); // None, First Aid, Medical Treatment, Hospitalization
            $table->string('hospital_name')->nullable()->after('medical_treatment');
            $table->string('investigation_status')->default('Not Started')->after('status'); // Not Started, In Progress, Completed
            $table->text('root_cause')->nullable()->after('investigation_status');
            $table->text('corrective_actions_summary')->nullable()->after('root_cause');
            $table->text('final_outcome')->nullable()->after('corrective_actions_summary');
            $table->foreignId('approved_by_id')->nullable()->after('final_outcome')->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_id');
            $table->foreignId('closed_by_id')->nullable()->after('approved_at')->constrained('staff')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->after('closed_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('safety_incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('approved_by_id');
            $table->dropConstrainedForeignId('closed_by_id');
            $table->dropColumn([
                'incident_number', 'time', 'location', 'injured_person_name', 'employment_type',
                'incident_category', 'injury_classification', 'witnesses', 'immediate_response',
                'medical_treatment', 'hospital_name', 'investigation_status', 'root_cause',
                'corrective_actions_summary', 'final_outcome', 'approved_at', 'closed_at',
            ]);
        });
    }
};
