<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labourers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
        Schema::table('labourers', function (Blueprint $table) {
            $table->foreignId('employee_id')->after('company_id')->constrained('staff')->cascadeOnDelete();
        });

        Schema::table('project_team_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
        Schema::table('project_team_members', function (Blueprint $table) {
            $table->foreignId('employee_id')->after('project_id')->constrained('staff')->cascadeOnDelete();
        });

        Schema::table('site_team_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
        Schema::table('site_team_members', function (Blueprint $table) {
            $table->foreignId('employee_id')->after('site_id')->constrained('staff')->cascadeOnDelete();
        });

        Schema::table('equipment_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_employee_id');
            $table->dropConstrainedForeignId('backup_operator_employee_id');
            $table->dropConstrainedForeignId('supervisor_employee_id');
        });
        Schema::table('equipment_allocations', function (Blueprint $table) {
            $table->foreignId('operator_employee_id')->nullable()->after('allocated_to')->constrained('staff')->nullOnDelete();
            $table->foreignId('backup_operator_employee_id')->nullable()->after('operator_employee_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('supervisor_employee_id')->nullable()->after('backup_operator_employee_id')->constrained('staff')->nullOnDelete();
        });

        Schema::table('site_inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inspector_id');
        });
        Schema::table('site_inspections', function (Blueprint $table) {
            $table->foreignId('inspector_id')->nullable()->after('site_id')->constrained('staff')->nullOnDelete();
        });

        Schema::table('safety_incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reported_by_id');
        });
        Schema::table('safety_incidents', function (Blueprint $table) {
            $table->foreignId('reported_by_id')->nullable()->after('site_id')->constrained('staff')->nullOnDelete();
        });

        Schema::table('quality_inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inspector_id');
        });
        Schema::table('quality_inspections', function (Blueprint $table) {
            $table->foreignId('inspector_id')->nullable()->after('site_id')->constrained('staff')->nullOnDelete();
        });

        Schema::table('daily_site_diaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logged_by_id');
        });
        Schema::table('daily_site_diaries', function (Blueprint $table) {
            $table->foreignId('logged_by_id')->nullable()->after('work_summary')->constrained('staff')->nullOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_manager_id');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_manager_id')->nullable()->after('client_id')->constrained('staff')->nullOnDelete();
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_manager_id');
        });
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('site_manager_id')->nullable()->after('gps_lng')->constrained('staff')->nullOnDelete();
        });

        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inspector_id');
        });
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->foreignId('inspector_id')->nullable()->after('date')->constrained('staff')->nullOnDelete();
        });

        Schema::table('material_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by_id');
        });
        Schema::table('material_deliveries', function (Blueprint $table) {
            $table->foreignId('received_by_id')->nullable()->after('inspection_status')->constrained('staff')->nullOnDelete();
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('host_employee_id');
        });
        Schema::table('visitors', function (Blueprint $table) {
            $table->foreignId('host_employee_id')->nullable()->after('purpose')->constrained('staff')->nullOnDelete();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('employee_id')->after('company_id')->constrained('staff')->cascadeOnDelete();
        });

        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logged_by_id');
        });
        Schema::table('production_logs', function (Blueprint $table) {
            $table->foreignId('logged_by_id')->nullable()->after('extraction_site_id')->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $tables = [
            'labourers' => ['employee_id'],
            'project_team_members' => ['employee_id'],
            'site_team_members' => ['employee_id'],
            'equipment_allocations' => ['operator_employee_id', 'backup_operator_employee_id', 'supervisor_employee_id'],
            'site_inspections' => ['inspector_id'],
            'safety_incidents' => ['reported_by_id'],
            'quality_inspections' => ['inspector_id'],
            'daily_site_diaries' => ['logged_by_id'],
            'projects' => ['project_manager_id'],
            'sites' => ['site_manager_id'],
            'equipment_inspections' => ['inspector_id'],
            'material_deliveries' => ['received_by_id'],
            'visitors' => ['host_employee_id'],
            'attendances' => ['employee_id'],
            'production_logs' => ['logged_by_id'],
        ];

        foreach ($tables as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->dropConstrainedForeignId($column);
                }
            });
        }

        Schema::table('labourers', function (Blueprint $table) {
            $table->foreignId('employee_id')->after('company_id')->constrained('employees')->cascadeOnDelete();
        });
        Schema::table('project_team_members', function (Blueprint $table) {
            $table->foreignId('employee_id')->after('project_id')->constrained('employees')->cascadeOnDelete();
        });
        Schema::table('site_team_members', function (Blueprint $table) {
            $table->foreignId('employee_id')->after('site_id')->constrained('employees')->cascadeOnDelete();
        });
        Schema::table('equipment_allocations', function (Blueprint $table) {
            $table->foreignId('operator_employee_id')->nullable()->after('allocated_to')->constrained('employees')->nullOnDelete();
            $table->foreignId('backup_operator_employee_id')->nullable()->after('operator_employee_id')->constrained('employees')->nullOnDelete();
            $table->foreignId('supervisor_employee_id')->nullable()->after('backup_operator_employee_id')->constrained('employees')->nullOnDelete();
        });
        Schema::table('site_inspections', function (Blueprint $table) {
            $table->foreignId('inspector_id')->nullable()->after('site_id')->constrained('employees')->nullOnDelete();
        });
        Schema::table('safety_incidents', function (Blueprint $table) {
            $table->foreignId('reported_by_id')->nullable()->after('site_id')->constrained('employees')->nullOnDelete();
        });
        Schema::table('quality_inspections', function (Blueprint $table) {
            $table->foreignId('inspector_id')->nullable()->after('site_id')->constrained('employees')->nullOnDelete();
        });
        Schema::table('daily_site_diaries', function (Blueprint $table) {
            $table->foreignId('logged_by_id')->nullable()->after('work_summary')->constrained('employees')->nullOnDelete();
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_manager_id')->nullable()->after('client_id')->constrained('employees')->nullOnDelete();
        });
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('site_manager_id')->nullable()->after('gps_lng')->constrained('employees')->nullOnDelete();
        });
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->foreignId('inspector_id')->nullable()->after('date')->constrained('employees')->nullOnDelete();
        });
        Schema::table('material_deliveries', function (Blueprint $table) {
            $table->foreignId('received_by_id')->nullable()->after('inspection_status')->constrained('employees')->nullOnDelete();
        });
        Schema::table('visitors', function (Blueprint $table) {
            $table->foreignId('host_employee_id')->nullable()->after('purpose')->constrained('employees')->nullOnDelete();
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('employee_id')->after('company_id')->constrained('employees')->cascadeOnDelete();
        });
        Schema::table('production_logs', function (Blueprint $table) {
            $table->foreignId('logged_by_id')->nullable()->after('extraction_site_id')->constrained('employees')->nullOnDelete();
        });
    }
};
